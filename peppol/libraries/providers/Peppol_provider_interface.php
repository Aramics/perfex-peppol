<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Interface
 * 
 * Defines the standard methods that all PEPPOL access point providers must implement.
 * This ensures consistency and allows for easy provider switching.
 */
interface Peppol_provider_interface
{
    /**
     * Send a document via the PEPPOL network
     * 
     * @param string $ubl_content The UBL XML content to send
     * @param object $invoice The Perfex CRM invoice object
     * @param object $client The client object with PEPPOL identifier
     * @return array Array with 'success' boolean and 'message' string, plus optional 'document_id' and 'response'
     */
    public function send_document($ubl_content, $invoice, $client);

    /**
     * Test connection to the provider's API
     * 
     * @param string|null $environment Optional environment to test (sandbox/live)
     * @return array Array with 'success' boolean and 'message' string
     */
    public function test_connection($environment = null);

    /**
     * Get the delivery status of a sent document
     * 
     * @param string $document_id The provider's document ID
     * @return array Array with 'success' boolean, 'status' string, and optional 'delivered_at' and 'message'
     */
    public function get_delivery_status($document_id);

    /**
     * Handle incoming webhook from the provider
     * 
     * @return array|null Document data array if this is a document receipt, null for status updates
     */
    public function handle_webhook();
}

/**
 * Abstract base class for PEPPOL providers
 * 
 * Provides common functionality that can be shared across providers
 */
abstract class Abstract_peppol_provider implements Peppol_provider_interface
{
    protected $CI;
    protected $environment;
    
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->environment = get_option('peppol_environment', 'sandbox');
    }

    /**
     * Validate that required configuration is present
     * 
     * @param array $required_fields Array of required configuration field names
     * @return bool True if all required fields are configured
     */
    protected function validate_configuration($required_fields)
    {
        foreach ($required_fields as $field) {
            if (empty(get_option($field))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Log provider activity
     * 
     * @param string $action The action being performed
     * @param string $status The status (success, error, info, warning)
     * @param string $message The log message
     * @param array $additional_data Optional additional data to log
     */
    protected function log_activity($action, $status, $message, $additional_data = [])
    {
        $this->CI->load->model('peppol/peppol_model');
        
        $log_data = array_merge([
            'provider' => $this->get_provider_name(),
            'action' => $action,
            'status' => $status,
            'message' => $message
        ], $additional_data);
        
        $this->CI->peppol_model->log_activity($log_data);
    }

    /**
     * Get the name of this provider
     * 
     * @return string Provider name
     */
    protected function get_provider_name()
    {
        $class_name = get_class($this);
        return strtolower(str_replace('_provider', '', $class_name));
    }

    /**
     * Standardize response format
     * 
     * @param bool $success Whether the operation was successful
     * @param string $message Response message
     * @param mixed $data Optional response data
     * @param string|null $document_id Optional document ID
     * @return array Standardized response array
     */
    protected function create_response($success, $message, $data = null, $document_id = null)
    {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['response'] = $data;
        }
        
        if ($document_id !== null) {
            $response['document_id'] = $document_id;
        }
        
        return $response;
    }

    /**
     * Extract PEPPOL identifiers from UBL content
     * 
     * @param string $ubl_content UBL XML content
     * @return array Array with 'sender' and 'receiver' identifiers
     */
    protected function extract_peppol_identifiers($ubl_content)
    {
        $dom = new DOMDocument();
        if (!$dom->loadXML($ubl_content)) {
            return ['sender' => null, 'receiver' => null];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $sender = $this->get_xpath_value($xpath, '//cac:AccountingSupplierParty/cac:Party/cbc:EndpointID');
        $receiver = $this->get_xpath_value($xpath, '//cac:AccountingCustomerParty/cac:Party/cbc:EndpointID');

        return ['sender' => $sender, 'receiver' => $receiver];
    }

    /**
     * Get XPath value safely
     * 
     * @param DOMXPath $xpath XPath object
     * @param string $expression XPath expression
     * @return string Value or empty string
     */
    protected function get_xpath_value($xpath, $expression)
    {
        $nodes = $xpath->query($expression);
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    /**
     * Validate UBL content basic structure
     * 
     * @param string $ubl_content UBL XML content
     * @return bool True if valid basic UBL structure
     */
    protected function validate_ubl_structure($ubl_content)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$dom->loadXML($ubl_content)) {
            return false;
        }

        // Check for basic PEPPOL elements
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        
        $customization_id = $xpath->query('//cbc:CustomizationID')->item(0);
        if (!$customization_id) {
            return false;
        }

        // Check if it's a PEPPOL document
        return strpos($customization_id->textContent, 'urn:fdc:peppol.eu') !== false;
    }

    /**
     * Generate a unique document reference
     * 
     * @param string $prefix Prefix for the reference
     * @return string Unique document reference
     */
    protected function generate_document_reference($prefix = 'DOC')
    {
        return $prefix . '-' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 8);
    }
}