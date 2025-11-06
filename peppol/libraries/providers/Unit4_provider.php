<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(__DIR__ . '/Peppol_provider_interface.php');

class Unit4_provider extends Abstract_peppol_provider
{
    private $CI;
    private $username;
    private $password;
    private $endpoint_url;
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
        $this->username = get_option('peppol_unit4_username');
        $this->password = get_option('peppol_unit4_password');
        $this->environment = get_option('peppol_environment', 'sandbox');
        
        if ($this->environment === 'sandbox') {
            $this->endpoint_url = get_option('peppol_unit4_sandbox_endpoint', 'https://test-ap.unit4.com');
        } else {
            $this->endpoint_url = get_option('peppol_unit4_endpoint_url', 'https://ap.unit4.com');
        }
    }

    /**
     * Send document via Unit4 PEPPOL API
     */
    public function send_document($ubl_content, $invoice, $client)
    {
        try {
            if (empty($this->username) || empty($this->password)) {
                throw new Exception('Unit4 credentials not configured');
            }

            // Unit4 API expects multipart/form-data for document upload
            $boundary = '----' . md5(time());
            
            $document_data = $this->build_multipart_data([
                'document_type' => 'invoice',
                'document_format' => 'ubl',
                'sender_id' => get_option('peppol_company_identifier'),
                'sender_scheme' => get_option('peppol_company_scheme', '0088'),
                'receiver_id' => $client->peppol_identifier,
                'receiver_scheme' => $client->peppol_scheme ?: '0088',
                'reference' => format_invoice_number($invoice->id),
                'document' => $ubl_content
            ], $boundary);

            $headers = [
                'Content-Type: multipart/form-data; boundary=' . $boundary,
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
                'Accept: application/json'
            ];

            $response = $this->make_api_request('/rest/outbound/documents', 'POST', $document_data, $headers, true);

            if ($response['success']) {
                return [
                    'success' => true,
                    'document_id' => $response['data']['documentId'] ?? $response['data']['messageId'] ?? null,
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
     * Test connection to Unit4 API
     */
    public function test_connection($environment = null)
    {
        try {
            if ($environment) {
                $old_env = $this->environment;
                $this->environment = $environment;
                $this->load_config();
            }

            if (empty($this->username) || empty($this->password)) {
                throw new Exception('Username and password not configured');
            }

            $headers = [
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
                'Accept: application/json'
            ];

            // Unit4 has a status endpoint for testing
            $response = $this->make_api_request('/rest/status', 'GET', null, $headers);

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
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
                'Accept: application/json'
            ];

            $response = $this->make_api_request('/rest/outbound/documents/' . $document_id . '/status', 'GET', null, $headers);

            if ($response['success']) {
                $status_data = $response['data'];
                
                // Map Unit4 status to internal status
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
     * Handle webhook from Unit4
     */
    public function handle_webhook()
    {
        try {
            // Get the raw POST data
            $input = file_get_contents('php://input');
            
            // Unit4 can send both JSON and XML webhooks
            $content_type = $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($content_type, 'application/json') !== false) {
                $data = json_decode($input, true);
                if (!$data) {
                    throw new Exception('Invalid JSON webhook data');
                }
                return $this->process_json_webhook($data);
            } else {
                // Assume XML format for document delivery
                return $this->process_xml_webhook($input);
            }

        } catch (Exception $e) {
            log_message('error', 'Unit4 webhook error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process JSON webhook (status updates)
     */
    private function process_json_webhook($data)
    {
        switch ($data['eventType'] ?? '') {
            case 'DocumentDelivered':
                return $this->process_document_delivered($data);
            case 'DocumentFailed':
                return $this->process_document_failed($data);
            case 'DocumentReceived':
                return $this->process_document_received_notification($data);
            default:
                log_message('info', 'Unit4 webhook: Unknown event type: ' . ($data['eventType'] ?? 'none'));
                return null;
        }
    }

    /**
     * Process XML webhook (incoming documents)
     */
    private function process_xml_webhook($xml_content)
    {
        // Parse the incoming UBL document
        $dom = new DOMDocument();
        if (!$dom->loadXML($xml_content)) {
            throw new Exception('Invalid XML document received');
        }

        // Extract document ID and metadata
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        
        $document_id = $this->getXPathValue($xpath, '//cbc:ID');
        
        return [
            'document_id' => $document_id ?: 'UNIT4-' . md5($xml_content),
            'document_type' => 'invoice',
            'sender_identifier' => $this->extractSenderIdentifier($xpath),
            'receiver_identifier' => $this->extractReceiverIdentifier($xpath),
            'content' => $xml_content
        ];
    }

    /**
     * Process document delivered webhook
     */
    private function process_document_delivered($data)
    {
        $this->CI->load->model('peppol/peppol_model');
        
        $document_id = $data['documentId'] ?? null;
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
        
        $document_id = $data['documentId'] ?? null;
        $error_message = $data['errorMessage'] ?? 'Delivery failed';
        
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
     * Process document received notification
     */
    private function process_document_received_notification($data)
    {
        // This is just a notification, actual document comes via XML webhook
        log_message('info', 'Unit4: Document received notification for ID: ' . ($data['documentId'] ?? 'unknown'));
        return null;
    }

    /**
     * Extract sender identifier from UBL
     */
    private function extractSenderIdentifier($xpath)
    {
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        return $this->getXPathValue($xpath, '//cac:AccountingSupplierParty/cac:Party/cbc:EndpointID');
    }

    /**
     * Extract receiver identifier from UBL
     */
    private function extractReceiverIdentifier($xpath)
    {
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        return $this->getXPathValue($xpath, '//cac:AccountingCustomerParty/cac:Party/cbc:EndpointID');
    }

    /**
     * Get XPath value safely
     */
    private function getXPathValue($xpath, $expression)
    {
        $nodes = $xpath->query($expression);
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    /**
     * Map Unit4 status to internal status
     */
    private function map_status($unit4_status)
    {
        $status_map = [
            'DELIVERED' => 'delivered',
            'SENT' => 'sent',
            'FAILED' => 'failed',
            'PENDING' => 'pending',
            'PROCESSING' => 'sending',
            'RECEIVED' => 'received'
        ];

        return $status_map[strtoupper($unit4_status)] ?? 'unknown';
    }

    /**
     * Build multipart form data
     */
    private function build_multipart_data($fields, $boundary)
    {
        $data = '';
        
        foreach ($fields as $key => $value) {
            $data .= "--{$boundary}\r\n";
            
            if ($key === 'document') {
                $data .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"invoice.xml\"\r\n";
                $data .= "Content-Type: application/xml\r\n\r\n";
            } else {
                $data .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
            }
            
            $data .= $value . "\r\n";
        }
        
        $data .= "--{$boundary}--\r\n";
        
        return $data;
    }

    /**
     * Make API request to Unit4
     */
    private function make_api_request($endpoint, $method = 'GET', $data = null, $headers = [], $is_multipart = false)
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
            if ($is_multipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
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