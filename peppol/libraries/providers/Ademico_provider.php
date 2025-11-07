<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(__DIR__ . '/Peppol_provider_interface.php');

class Ademico_provider extends Abstract_peppol_provider
{
    private $client_id;
    private $client_secret;
    private $endpoint_url;
    private $token_url;

    // Ademico API endpoints mapping
    const ENDPOINTS = [
        'connectivity' => '/api/peppol/v1/tools/connectivity',
        'invoices' => '/api/peppol/v1/invoices/ubl-submissions',
        'notifications' => '/api/peppol/v1/notifications',
        'legal_entities' => '/api/peppol/v1/legal-entities',
        'legal_entity' => '/api/peppol/v1/legal-entities/%s', // %s for entity ID
        'documents' => '/documents/%s/content' // %s for document ID
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->load_config();
    }

    /**
     * Load provider configuration
     */
    private function load_config()
    {
        $this->client_id = get_option('peppol_ademico_oauth2_client_identifier');
        $this->client_secret = get_option('peppol_ademico_oauth2_client_secret');

        // Set OAuth2 URLs based on environment
        $this->endpoint_url = ($this->environment === 'sandbox')
            ? 'https://test-peppol-api.ademico-software.com'
            : 'https://peppol-api.ademico-software.com';
        $this->token_url = ($this->environment === 'sandbox')
            ? 'https://test-peppol-oauth2.ademico-software.com/oauth2/token'
            : 'https://peppol-oauth2.ademico-software.com/oauth2/token';
    }

    /**
     * Get OAuth2 JWT token with caching
     */
    private function get_oauth_token()
    {
        return $this->get_or_refresh_token('ademico_oauth', function () {
            return $this->refresh_oauth_token();
        });
    }


    /**
     * Refresh OAuth2 token
     */
    private function refresh_oauth_token()
    {
        // Base64 encode client_id:client_secret for Authorization header
        $credentials = base64_encode($this->client_id . ':' . $this->client_secret);

        $data = [
            'grant_type' => 'client_credentials',
            'scope' => 'peppol/document'
        ];

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $response = $this->make_form_request($this->token_url, $data, $headers);

        if (!$response['success']) {
            throw new Exception('OAuth2 authentication failed: ' . $response['message']);
        }

        $response_data = json_decode($response['response'], true);
        if (!isset($response_data['access_token'])) {
            throw new Exception('Invalid OAuth2 response: missing access_token');
        }

        return [
            'access_token' => $response_data['access_token'],
            'expires_in' => $response_data['expires_in'] ?? 3600
        ];
    }

    /**
     * Get OAuth2 authentication headers
     */
    private function get_auth_headers()
    {
        $jwt_token = $this->get_oauth_token();
        // Per Ademico API specification: Pass JWT token directly without 'Bearer' prefix
        return ['Authorization: ' . $jwt_token];
    }

    /**
     * Get standardized JSON headers with authentication
     */
    private function get_json_headers()
    {
        return array_merge([
            'Accept: application/json',
            'Content-Type: application/json'
        ], $this->get_auth_headers());
    }

    /**
     * Get read-only headers with authentication (no Content-Type)
     */
    private function get_read_headers()
    {
        return array_merge([
            'Accept: application/json'
        ], $this->get_auth_headers());
    }

    /**
     * Get endpoint URL by key
     */
    private function get_endpoint($key, $params = [])
    {
        if (!isset(self::ENDPOINTS[$key])) {
            throw new Exception("Unknown endpoint key: {$key}");
        }

        $endpoint = self::ENDPOINTS[$key];

        // Replace placeholders if params provided
        if (!empty($params)) {
            $endpoint = vsprintf($endpoint, $params);
        }

        return $endpoint;
    }

    /**
     * Validate OAuth2 configuration
     */
    private function validate_auth_config()
    {
        if (empty($this->client_id) || empty($this->client_secret)) {
            throw new Exception('OAuth2 client ID and client secret are required');
        }
    }

    /**
     * Send document via Ademico PEPPOL API
     */
    public function send_document($ubl_content, $invoice, $client)
    {
        try {
            $this->validate_auth_config();

            $endpoint = $this->get_endpoint('invoices');
            $auth_headers = $this->get_auth_headers();

            // Ademico API expects multipart/form-data with UBL XML file
            $response = $this->make_multipart_request($endpoint, $ubl_content, $auth_headers);

            if ($response['success']) {
                $result = $response['data'];
                return [
                    'success' => true,
                    'document_id' => $result['documentId'] ?? null,
                    'transmission_id' => $result['transmissionId'] ?? null,
                    'sbdh_instance_id' => $result['sbdhInstanceIdentifier'] ?? null,
                    'message' => 'Document sent successfully',
                    'response' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Unknown error',
                    'response' => $response
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Test connection to Ademico API
     */
    public function test_connection($environment = null)
    {
        try {
            if ($environment) {
                $old_env = $this->environment;
                $this->environment = $environment;
                $this->load_config();
            }

            // Clear cached token to force fresh authentication for testing
            $this->clear_token_cache();

            $this->validate_auth_config();

            $endpoint = $this->get_endpoint('connectivity');
            $response = $this->make_api_request($endpoint, 'GET', null, $this->get_read_headers());

            if (isset($environment)) {
                $this->environment = $old_env;
                $this->load_config();
            }

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'response' => $response['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Connection failed',
                    'response' => $response
                ];
            }
        } catch (Exception $e) {
            if (isset($environment)) {
                $this->environment = $old_env;
                $this->load_config();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Get delivery status of a document using Ademico notifications API
     */
    public function get_delivery_status($transmission_id)
    {
        try {
            // Use Ademico's unified notifications API with query parameter
            $endpoint = $this->get_endpoint('notifications') . '?transmissionId=' . urlencode($transmission_id);
            $response = $this->make_api_request($endpoint, 'GET', null, $this->get_read_headers());

            if ($response['success']) {
                $notifications = $response['data'];

                if (empty($notifications)) {
                    return [
                        'success' => false,
                        'message' => 'No notifications found for transmission ID',
                        'response' => null
                    ];
                }

                // Get the latest notification
                $latest_notification = $notifications[0];

                // Map Ademico status to internal status
                $internal_status = $this->map_status($latest_notification['documentStatus'] ?? 'unknown');

                return [
                    'success' => true,
                    'status' => $internal_status,
                    'event_type' => $latest_notification['eventType'] ?? null,
                    'notification_date' => $latest_notification['notificationDate'] ?? null,
                    'document_id' => $latest_notification['documentId'] ?? null,
                    'message' => 'Status retrieved successfully',
                    'response' => $latest_notification
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to get status',
                    'response' => $response
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Handle webhook from Ademico
     */
    public function handle_webhook()
    {
        try {
            // Get the raw POST data
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid webhook data');
            }

            // Verify webhook signature if provided
            if (isset($_SERVER['HTTP_X_ADEMICO_SIGNATURE'])) {
                $signature = $_SERVER['HTTP_X_ADEMICO_SIGNATURE'];
                // Use client_secret as webhook signing key for OAuth2
                $expected_signature = hash_hmac('sha256', $input, $this->client_secret);

                if (!hash_equals($signature, $expected_signature)) {
                    throw new Exception('Invalid webhook signature');
                }
            }

            // Process different Ademico webhook events based on specification
            switch ($data['eventType'] ?? '') {
                case 'DOCUMENT_SENT':
                    return $this->process_document_sent($data);
                case 'DOCUMENT_SEND_FAILED':
                    return $this->process_document_send_failed($data);
                case 'MLR_RECEIVED':
                    return $this->process_mlr_received($data);
                case 'INVOICE_RESPONSE_RECEIVED':
                    return $this->process_invoice_response_received($data);
                default:
                    throw new Exception('Unknown webhook event type: ' . ($data['eventType'] ?? 'none'));
            }
        } catch (Exception $e) {
            log_message('error', 'Ademico webhook error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process document sent webhook
     */
    private function process_document_sent($data)
    {
        $this->CI->load->model('peppol/peppol_model');

        $transmission_id = $data['transmissionId'] ?? null;
        if ($transmission_id) {
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_transmission_id($transmission_id);
            if ($peppol_invoice) {
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id,
                    'sent',
                    [
                        'sent_at' => $data['notificationDate'] ?? date('Y-m-d H:i:s'),
                        'document_status' => $data['documentStatus'] ?? 'SENT'
                    ]
                );
            }
        }

        return null; // No document to process
    }

    /**
     * Process document send failed webhook
     */
    private function process_document_send_failed($data)
    {
        $this->CI->load->model('peppol/peppol_model');

        $transmission_id = $data['transmissionId'] ?? null;
        $error_details = [];

        if (isset($data['details']) && is_array($data['details'])) {
            foreach ($data['details'] as $detail) {
                $error_details[] = $detail['message'] ?? 'Unknown error';
            }
        }

        if ($transmission_id) {
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_transmission_id($transmission_id);
            if ($peppol_invoice) {
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id,
                    'failed',
                    [
                        'error_message' => implode('; ', $error_details),
                        'document_status' => $data['documentStatus'] ?? 'SEND_FAILED'
                    ]
                );
            }
        }

        return null; // No document to process
    }

    /**
     * Process MLR (Message Level Response) received webhook
     */
    private function process_mlr_received($data)
    {
        $this->CI->load->model('peppol/peppol_model');

        $transmission_id = $data['transmissionId'] ?? null;
        if ($transmission_id) {
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_transmission_id($transmission_id);
            if ($peppol_invoice) {
                // MLR indicates technical acceptance/rejection
                $status = ($data['documentStatus'] === 'ACCEPTED') ? 'delivered' : 'rejected';
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id,
                    $status,
                    [
                        'received_at' => $data['notificationDate'] ?? date('Y-m-d H:i:s'),
                        'document_status' => $data['documentStatus'] ?? 'UNKNOWN'
                    ]
                );
            }
        }

        return null; // No document to process
    }

    /**
     * Process Invoice Response received webhook
     */
    private function process_invoice_response_received($data)
    {
        $this->CI->load->model('peppol/peppol_model');

        $transmission_id = $data['transmissionId'] ?? null;
        if ($transmission_id) {
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_transmission_id($transmission_id);
            if ($peppol_invoice) {
                // Invoice Response indicates business acceptance/rejection
                $status = ($data['documentStatus'] === 'ACCEPTED') ? 'acknowledged' : 'rejected';
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id,
                    $status,
                    [
                        'processed_at' => $data['notificationDate'] ?? date('Y-m-d H:i:s'),
                        'document_status' => $data['documentStatus'] ?? 'UNKNOWN'
                    ]
                );
            }
        }

        return null; // No document to process
    }

    /**
     * Make form-encoded HTTP request (for OAuth)
     */
    private function make_form_request($url, $data, $headers = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'cURL error: ' . $curl_error,
                'response' => null
            ];
        }

        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'response' => $response,
                'http_code' => $http_code
            ];
        } else {
            return [
                'success' => false,
                'message' => 'HTTP error: ' . $http_code,
                'response' => $response,
                'http_code' => $http_code
            ];
        }
    }

    /**
     * Make multipart form request for document submission
     */
    private function make_multipart_request($endpoint, $ubl_content, $auth_headers)
    {
        $url = rtrim($this->endpoint_url, '/') . $endpoint;

        // Create a temporary file for the UBL content
        $temp_file = tempnam(sys_get_temp_dir(), 'peppol_ubl_');
        file_put_contents($temp_file, $ubl_content);

        $ch = curl_init();

        $post_fields = [
            'file' => new CURLFile($temp_file, 'application/xml', 'invoice.xml')
        ];

        $headers = array_merge([
            'Accept: application/json'
        ], $auth_headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // Clean up temp file
        unlink($temp_file);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        $decoded_response = json_decode($response, true);

        return [
            'success' => $http_code >= 200 && $http_code < 300,
            'data' => $decoded_response,
            'message' => $decoded_response['message'] ?? null,
            'http_code' => $http_code,
            'raw_response' => $response
        ];
    }

    /**
     * Download document content from Ademico
     */
    private function download_document($document_id)
    {
        $endpoint = $this->get_endpoint('documents', [urlencode($document_id)]);
        $headers = array_merge([
            'Accept: application/xml'
        ], $this->get_auth_headers());

        $response = $this->make_api_request($endpoint, 'GET', null, $headers);

        if ($response['success']) {
            return $response['raw_data']; // Return raw XML content
        } else {
            throw new Exception('Failed to download document content');
        }
    }

    /**
     * Map Ademico status to internal status
     */
    private function map_status($ademico_status)
    {
        $status_map = [
            'QUEUED' => 'pending',
            'SENT' => 'sent',
            'SEND_FAILED' => 'failed',
            'TECHNICAL_ACCEPTANCE' => 'delivered',
            'REJECTED' => 'rejected',
            'ACCEPTED' => 'acknowledged',
            'UNDER_QUERY' => 'pending',
            'FULLY_PAID' => 'processed'
        ];

        return $status_map[$ademico_status] ?? 'unknown';
    }

    /**
     * Make API request to Ademico
     */
    private function make_api_request($endpoint, $method = 'GET', $data = null, $headers = [])
    {
        $url = rtrim($this->endpoint_url, '/') . $endpoint;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        // For XML content requests, return raw data
        if (strpos(implode(' ', $headers), 'application/xml') !== false) {
            return [
                'success' => $http_code >= 200 && $http_code < 300,
                'raw_data' => $response,
                'http_code' => $http_code
            ];
        }

        $decoded_response = json_decode($response, true);

        return [
            'success' => $http_code >= 200 && $http_code < 300,
            'data' => $decoded_response,
            'message' => $decoded_response['message'] ?? null,
            'http_code' => $http_code,
            'raw_response' => $response
        ];
    }

    /**
     * Clear cached OAuth token (useful for testing or when token is invalid)
     */
    public function clear_token_cache($provider_key = "ademico_oauth")
    {
        parent::clear_token_cache($provider_key);
    }

    /**
     * Get token expiration info for debugging
     */
    public function get_token_info($provider_key = "ademico_oauth")
    {
        return parent::get_token_info($provider_key);
    }

    // ========================================
    // LEGAL ENTITY MANAGEMENT
    // ========================================

    /**
     * Get list of legal entities available for the account
     */
    public function get_legal_entities()
    {
        try {
            $endpoint = $this->get_endpoint('legal_entities');
            $response = $this->make_api_request($endpoint, 'GET', null, $this->get_read_headers());

            if ($response['success']) {
                $entities = $response['data'] ?? [];
                return [
                    'success' => true,
                    'entities' => $entities,
                    'message' => 'Legal entities retrieved successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to retrieve legal entities',
                    'entities' => []
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'entities' => []
            ];
        }
    }

    /**
     * Create a new legal entity
     */
    public function create_legal_entity($entity_data)
    {
        try {
            $this->validate_auth_config();
            $endpoint = $this->get_endpoint('legal_entities');
            $ademico_entity = $this->prepare_legal_entity_data($entity_data);

            $response = $this->make_api_request($endpoint, 'POST', $ademico_entity, $this->get_json_headers());

            if ($response['success']) {
                $created_entity = $response['data'] ?? [];
                return [
                    'success' => true,
                    'entity_id' => $created_entity['id'] ?? $created_entity['entityId'] ?? null,
                    'message' => 'Legal entity created successfully',
                    'entity' => $created_entity
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to create legal entity'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing legal entity
     */
    public function update_legal_entity($entity_id, $entity_data)
    {
        try {
            $this->validate_auth_config();
            $endpoint = $this->get_endpoint('legal_entity', [urlencode($entity_id)]);
            $ademico_entity = $this->prepare_legal_entity_data($entity_data);

            $response = $this->make_api_request($endpoint, 'PUT', $ademico_entity, $this->get_json_headers());

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Legal entity updated successfully',
                    'entity' => $response['data'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to update legal entity'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a legal entity
     */
    public function delete_legal_entity($entity_id)
    {
        try {
            $this->validate_auth_config();
            $endpoint = $this->get_endpoint('legal_entity', [urlencode($entity_id)]);

            $response = $this->make_api_request($endpoint, 'DELETE', null, $this->get_read_headers());

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Legal entity deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to delete legal entity'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get details of a specific legal entity
     */
    public function get_legal_entity($entity_id)
    {
        try {
            $endpoint = $this->get_endpoint('legal_entity', [urlencode($entity_id)]);
            $response = $this->make_api_request($endpoint, 'GET', null, $this->get_read_headers());

            if ($response['success']) {
                return [
                    'success' => true,
                    'entity' => $response['data'] ?? [],
                    'message' => 'Legal entity retrieved successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to retrieve legal entity',
                    'entity' => null
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'entity' => null
            ];
        }
    }

    /**
     * Prepare legal entity data for Ademico API format
     */
    private function prepare_legal_entity_data($entity_data)
    {
        // Map common fields to Ademico API format
        $ademico_entity = [];

        // Required fields
        if (isset($entity_data['name'])) {
            $ademico_entity['name'] = $entity_data['name'];
        }

        if (isset($entity_data['identifier'])) {
            $ademico_entity['identifier'] = $entity_data['identifier'];
        }

        if (isset($entity_data['scheme_id'])) {
            $ademico_entity['schemeId'] = $entity_data['scheme_id'];
        }

        // Company registration details
        if (isset($entity_data['registration_number'])) {
            $ademico_entity['registrationNumber'] = $entity_data['registration_number'];
        }

        if (isset($entity_data['vat_number'])) {
            $ademico_entity['vatNumber'] = $entity_data['vat_number'];
        }

        // Address information
        if (isset($entity_data['address'])) {
            $ademico_entity['address'] = [
                'street' => $entity_data['address']['street'] ?? '',
                'city' => $entity_data['address']['city'] ?? '',
                'postalCode' => $entity_data['address']['postal_code'] ?? '',
                'country' => $entity_data['address']['country'] ?? '',
                'countryCode' => $entity_data['address']['country_code'] ?? ''
            ];
        }

        // Contact information
        if (isset($entity_data['contact'])) {
            $ademico_entity['contact'] = [
                'name' => $entity_data['contact']['name'] ?? '',
                'email' => $entity_data['contact']['email'] ?? '',
                'phone' => $entity_data['contact']['phone'] ?? ''
            ];
        }

        // PEPPOL specific settings
        if (isset($entity_data['peppol_identifier'])) {
            $ademico_entity['peppolIdentifier'] = $entity_data['peppol_identifier'];
        }

        if (isset($entity_data['peppol_scheme'])) {
            $ademico_entity['peppolScheme'] = $entity_data['peppol_scheme'];
        }

        // Document types this entity can send/receive
        if (isset($entity_data['document_types'])) {
            $ademico_entity['documentTypes'] = $entity_data['document_types'];
        } else {
            // Default to invoice if not specified
            $ademico_entity['documentTypes'] = ['invoice'];
        }

        // Additional settings
        if (isset($entity_data['settings'])) {
            $ademico_entity['settings'] = $entity_data['settings'];
        }

        return $ademico_entity;
    }
}