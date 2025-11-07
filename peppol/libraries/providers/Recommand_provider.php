<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(__DIR__ . '/Peppol_provider_interface.php');

class Recommand_provider extends Abstract_peppol_provider
{
    private $api_key;
    private $endpoint_url;
    private $company_id;

    public function __construct()
    {
        parent::__construct();
        $this->load_config();
    }

    /**
     * Load provider configuration
     */
    private function load_config()
    {
        $this->api_key = get_option('peppol_recommand_api_key');
        $this->company_id = get_option('peppol_recommand_company_id');
        
        if ($this->environment === 'sandbox') {
            $this->endpoint_url = get_option('peppol_recommand_sandbox_endpoint', 'https://sandbox-peppol.recommand.eu/api');
        } else {
            $this->endpoint_url = get_option('peppol_recommand_endpoint_url', 'https://peppol.recommand.eu/api');
        }
    }

    /**
     * Send document via Recommand PEPPOL API
     */
    public function send_document($ubl_content, $invoice, $client)
    {
        try {
            if (empty($this->api_key) || empty($this->company_id)) {
                throw new Exception('Recommand API credentials not configured');
            }

            // Recommand accepts simplified JSON format and converts to UBL
            $document_data = [
                'documentType' => 'invoice',
                'format' => 'json', // Recommand prefers JSON over UBL
                'senderId' => get_option('peppol_company_identifier'),
                'senderScheme' => get_option('peppol_company_scheme', '0088'),
                'receiverId' => $client->peppol_identifier,
                'receiverScheme' => $client->peppol_scheme ?: '0088',
                'reference' => format_invoice_number($invoice->id),
                'companyId' => $this->company_id,
                'invoice' => $this->convert_invoice_to_json($invoice, $client)
            ];

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'Accept: application/json'
            ];

            $response = $this->make_api_request('/peppol/' . $this->company_id . '/sendDocument', 'POST', $document_data, $headers);

            if ($response['success']) {
                return [
                    'success' => true,
                    'document_id' => $response['data']['documentId'] ?? $response['data']['id'] ?? null,
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
     * Convert Perfex CRM invoice to Recommand JSON format
     */
    private function convert_invoice_to_json($invoice, $client)
    {
        // Get invoice items
        $this->CI->load->model('invoices_model');
        $invoice_items = $this->CI->invoices_model->get_invoice_items($invoice->id);

        // Convert to Recommand's simplified JSON format
        $json_invoice = [
            'invoiceNumber' => format_invoice_number($invoice->id),
            'issueDate' => to_sql_date($invoice->date),
            'dueDate' => to_sql_date($invoice->duedate),
            'currency' => $invoice->currency_name ?: get_base_currency()->name,
            'supplier' => [
                'name' => get_option('company_name'),
                'address' => get_option('company_address'),
                'city' => get_option('company_city'),
                'postalCode' => get_option('company_zip'),
                'country' => $this->get_country_code(get_option('company_country')),
                'vatNumber' => get_option('company_vat'),
                'peppolId' => get_option('peppol_company_identifier'),
                'peppolScheme' => get_option('peppol_company_scheme', '0088')
            ],
            'customer' => [
                'name' => $client->company ?: ($client->firstname . ' ' . $client->lastname),
                'address' => $client->address ?: '',
                'city' => $client->city ?: '',
                'postalCode' => $client->zip ?: '',
                'country' => $this->get_country_code($client->country),
                'vatNumber' => $client->vat ?: '',
                'peppolId' => $client->peppol_identifier,
                'peppolScheme' => $client->peppol_scheme ?: '0088'
            ],
            'lines' => [],
            'totals' => [
                'netAmount' => (float) $invoice->subtotal,
                'taxAmount' => (float) ($invoice->total - $invoice->subtotal),
                'grossAmount' => (float) $invoice->total
            ]
        ];

        // Add invoice lines
        foreach ($invoice_items as $item) {
            $json_invoice['lines'][] = [
                'description' => $item['description'],
                'quantity' => (float) $item['qty'],
                'unitPrice' => (float) $item['rate'],
                'netAmount' => (float) ($item['qty'] * $item['rate']),
                'taxRate' => 0, // Simplified - should calculate actual tax
                'taxAmount' => 0
            ];
        }

        // Add terms if available
        if (!empty($invoice->terms)) {
            $json_invoice['notes'] = $invoice->terms;
        }

        return $json_invoice;
    }

    /**
     * Get country ISO code from country ID
     */
    private function get_country_code($country_id)
    {
        if (!$country_id) {
            return '';
        }

        $this->CI->load->model('countries_model');
        $country = $this->CI->countries_model->get($country_id);
        
        return $country ? $country->iso2 : '';
    }

    /**
     * Test connection to Recommand API
     */
    public function test_connection($environment = null)
    {
        try {
            if ($environment) {
                $old_env = $this->environment;
                $this->environment = $environment;
                $this->load_config();
            }

            if (empty($this->api_key)) {
                throw new Exception('API key not configured');
            }

            // Note: Recommand uses static API key, no token caching needed

            $headers = [
                'Authorization: Bearer ' . $this->api_key,
                'Accept: application/json'
            ];

            // Recommand has a health check endpoint
            $response = $this->make_api_request('/health', 'GET', null, $headers);

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
            $headers = [
                'Authorization: Bearer ' . $this->api_key,
                'Accept: application/json'
            ];

            $response = $this->make_api_request('/peppol/' . $this->company_id . '/documents/' . $document_id, 'GET', null, $headers);

            if ($response['success']) {
                $status_data = $response['data'];
                
                // Map Recommand status to internal status
                $internal_status = $this->map_status($status_data['status'] ?? 'unknown');
                
                return [
                    'success' => true,
                    'status' => $internal_status,
                    'delivered_at' => $status_data['deliveredAt'] ?? null,
                    'message' => $status_data['statusMessage'] ?? 'Status retrieved',
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
     * Handle webhook from Recommand
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

            // Verify webhook signature if available
            if (isset($_SERVER['HTTP_X_RECOMMAND_SIGNATURE'])) {
                $signature = $_SERVER['HTTP_X_RECOMMAND_SIGNATURE'];
                $expected_signature = hash_hmac('sha256', $input, $this->api_key);
                
                if (!hash_equals($signature, $expected_signature)) {
                    throw new Exception('Invalid webhook signature');
                }
            }

            // Process different webhook events
            switch ($data['eventType'] ?? '') {
                case 'document.received':
                    return $this->process_document_received($data);
                case 'document.delivered':
                    return $this->process_document_delivered($data);
                case 'document.failed':
                    return $this->process_document_failed($data);
                case 'document.processed':
                    return $this->process_document_processed($data);
                default:
                    throw new Exception('Unknown webhook event type: ' . ($data['eventType'] ?? 'none'));
            }

        } catch (Exception $e) {
            log_message('error', 'Recommand webhook error: ' . $e->getMessage());
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
        
        // Download document content from Recommand
        $content = $this->download_document($document['documentId']);
        
        return [
            'document_id' => $document['documentId'],
            'document_type' => $document['documentType'] ?? 'invoice',
            'sender_identifier' => $document['senderId'] ?? null,
            'receiver_identifier' => $document['receiverId'] ?? null,
            'content' => $content
        ];
    }

    /**
     * Process document delivered webhook
     */
    private function process_document_delivered($data)
    {
        $this->CI->load->model('peppol/peppol_model');
        
        $document_id = $data['document']['documentId'] ?? null;
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
        
        return null;
    }

    /**
     * Process document failed webhook
     */
    private function process_document_failed($data)
    {
        $this->CI->load->model('peppol/peppol_model');
        
        $document_id = $data['document']['documentId'] ?? null;
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
        
        return null;
    }

    /**
     * Process document processed webhook
     */
    private function process_document_processed($data)
    {
        // Document has been successfully processed by Recommand
        log_message('info', 'Recommand: Document processed successfully: ' . ($data['document']['documentId'] ?? 'unknown'));
        return null;
    }

    /**
     * Download document content from Recommand
     */
    private function download_document($document_id)
    {
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Accept: application/xml'
        ];

        $response = $this->make_api_request('/peppol/' . $this->company_id . '/documents/' . $document_id . '/content', 'GET', null, $headers);

        if ($response['success']) {
            return $response['raw_data'];
        } else {
            throw new Exception('Failed to download document content');
        }
    }

    /**
     * Map Recommand status to internal status
     */
    private function map_status($recommand_status)
    {
        $status_map = [
            'sent' => 'sent',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'pending' => 'pending',
            'processing' => 'sending',
            'received' => 'received',
            'processed' => 'processed'
        ];

        return $status_map[strtolower($recommand_status)] ?? 'unknown';
    }

    /**
     * Make API request to Recommand
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
            'message' => $decoded_response['message'] ?? $decoded_response['error'] ?? null,
            'http_code' => $http_code,
            'raw_response' => $response
        ];
    }
}