<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(__DIR__ . '/Peppol_provider_interface.php');

class Ademico_provider extends Abstract_peppol_provider
{
    private $CI;
    private $client_id;
    private $client_secret;
    private $jwt_token;
    private $endpoint_url;
    private $token_url;
    private $environment;

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
        $this->environment = get_option('peppol_environment', 'sandbox');
        
        // Set OAuth2 URLs based on environment
        $this->endpoint_url = ($this->environment === 'sandbox') 
            ? 'https://test-peppol-api.ademico-software.com'
            : 'https://peppol-api.ademico-software.com';
        $this->token_url = ($this->environment === 'sandbox')
            ? 'https://test-peppol-oauth2.ademico-software.com/oauth2/token'
            : 'https://peppol-oauth2.ademico-software.com/oauth2/token';
    }

    /**
     * Get OAuth2 JWT token
     */
    private function get_oauth_token()
    {
        if ($this->jwt_token) {
            return $this->jwt_token;
        }

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

        $token_data = json_decode($response['response'], true);
        if (!isset($token_data['access_token'])) {
            throw new Exception('Invalid OAuth2 response: missing access_token');
        }

        $this->jwt_token = $token_data['access_token'];
        return $this->jwt_token;
    }

    /**
     * Get OAuth2 authentication headers
     */
    private function get_auth_headers()
    {
        $jwt_token = $this->get_oauth_token();
        // Note: Ademico API documentation says NOT to use 'Bearer' prefix for JWT
        return ['Authorization: ' . $jwt_token];
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

            // Prepare the request data
            $document_data = [
                'document_type' => 'invoice',
                'document_format' => 'ubl',
                'sender_identifier' => get_option('peppol_company_identifier'),
                'sender_scheme' => get_option('peppol_company_scheme', '0088'),
                'receiver_identifier' => $client->peppol_identifier,
                'receiver_scheme' => $client->peppol_scheme ?: '0088',
                'document_content' => base64_encode($ubl_content),
                'reference' => format_invoice_number($invoice->id)
            ];

            $auth_headers = $this->get_auth_headers();
            $headers = array_merge([
                'Content-Type: application/json',
                'Accept: application/json'
            ], $auth_headers);

            $response = $this->make_api_request('/documents/send', 'POST', $document_data, $headers);

            if ($response['success']) {
                return [
                    'success' => true,
                    'document_id' => $response['data']['document_id'] ?? null,
                    'message' => 'Document sent successfully',
                    'response' => $response['data']
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

            $this->validate_auth_config();

            $auth_headers = $this->get_auth_headers();
            $headers = array_merge([
                'Accept: application/json'
            ], $auth_headers);

            $response = $this->make_api_request('/ping', 'GET', null, $headers);

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
     * Get delivery status of a document
     */
    public function get_delivery_status($document_id)
    {
        try {
            $auth_headers = $this->get_auth_headers();
            $headers = array_merge([
                'Accept: application/json'
            ], $auth_headers);

            $response = $this->make_api_request('/documents/' . $document_id . '/status', 'GET', null, $headers);

            if ($response['success']) {
                $status_data = $response['data'];
                
                // Map Ademico status to internal status
                $internal_status = $this->map_status($status_data['status'] ?? 'unknown');
                
                return [
                    'success' => true,
                    'status' => $internal_status,
                    'delivered_at' => $status_data['delivered_at'] ?? null,
                    'message' => $status_data['message'] ?? 'Status retrieved',
                    'response' => $status_data
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

            // Process different webhook events
            switch ($data['event_type'] ?? '') {
                case 'document_received':
                    return $this->process_document_received($data);
                case 'document_delivered':
                    return $this->process_document_delivered($data);
                case 'document_failed':
                    return $this->process_document_failed($data);
                default:
                    throw new Exception('Unknown webhook event type');
            }

        } catch (Exception $e) {
            log_message('error', 'Ademico webhook error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process received document webhook
     */
    private function process_document_received($data)
    {
        if (!isset($data['document'])) {
            throw new Exception('Document data missing from webhook');
        }

        $document = $data['document'];
        
        // Download document content
        $content = $this->download_document($document['document_id']);
        
        return [
            'document_id' => $document['document_id'],
            'document_type' => $document['document_type'] ?? 'invoice',
            'sender_identifier' => $document['sender_identifier'] ?? null,
            'receiver_identifier' => $document['receiver_identifier'] ?? null,
            'content' => $content
        ];
    }

    /**
     * Process document delivered webhook
     */
    private function process_document_delivered($data)
    {
        // Update document status in database
        $this->CI->load->model('peppol/peppol_model');
        
        $document_id = $data['document']['document_id'] ?? null;
        if ($document_id) {
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_document_id($document_id);
            if ($peppol_invoice) {
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id,
                    'delivered',
                    ['received_at' => date('Y-m-d H:i:s')]
                );
            }
        }
        
        return null; // No document to process
    }

    /**
     * Process document failed webhook
     */
    private function process_document_failed($data)
    {
        // Update document status in database
        $this->CI->load->model('peppol/peppol_model');
        
        $document_id = $data['document']['document_id'] ?? null;
        $error_message = $data['error']['message'] ?? 'Delivery failed';
        
        if ($document_id) {
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_document_id($document_id);
            if ($peppol_invoice) {
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id,
                    'failed',
                    ['error_message' => $error_message]
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
     * Download document content from Ademico
     */
    private function download_document($document_id)
    {
        $auth_headers = $this->get_auth_headers();
        $headers = array_merge([
            'Accept: application/xml'
        ], $auth_headers);

        $response = $this->make_api_request('/documents/' . $document_id . '/content', 'GET', null, $headers);

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
            'sent' => 'sent',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'pending' => 'pending',
            'processing' => 'sending'
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
}