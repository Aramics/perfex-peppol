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

    /**
     * Get list of legal entities available for the account
     * 
     * @return array Array with 'success' boolean and 'entities' array of legal entity data
     */
    public function get_legal_entities();

    /**
     * Create a new legal entity
     * 
     * @param array $entity_data Legal entity information (name, identifier, address, etc.)
     * @return array Array with 'success' boolean, 'message' string, and optional 'entity_id'
     */
    public function create_legal_entity($entity_data);

    /**
     * Update an existing legal entity
     * 
     * @param string $entity_id The entity ID to update
     * @param array $entity_data Updated legal entity information
     * @return array Array with 'success' boolean and 'message' string
     */
    public function update_legal_entity($entity_id, $entity_data);

    /**
     * Delete a legal entity
     * 
     * @param string $entity_id The entity ID to delete
     * @return array Array with 'success' boolean and 'message' string
     */
    public function delete_legal_entity($entity_id);

    /**
     * Get details of a specific legal entity
     * 
     * @param string $entity_id The entity ID
     * @return array Array with 'success' boolean and 'entity' data
     */
    public function get_legal_entity($entity_id);
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
    protected $auth_token;
    protected $token_expires_at;
    
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

    // ========================================
    // TOKEN CACHING FUNCTIONALITY
    // ========================================

    /**
     * Check if current token is valid
     */
    protected function is_token_valid()
    {
        return $this->auth_token && 
               $this->token_expires_at && 
               $this->token_expires_at > time();
    }

    /**
     * Get cached token from session
     * 
     * @param string $provider_key Provider-specific key for caching
     */
    protected function get_cached_token($provider_key)
    {
        $cache_key = $provider_key . '_token_' . $this->environment . '_' . md5(get_staff_user_id());
        return $this->CI->session->userdata($cache_key);
    }

    /**
     * Check if cached token is valid
     */
    protected function is_cached_token_valid($cached_token)
    {
        return isset($cached_token['access_token']) && 
               isset($cached_token['expires_at']) && 
               $cached_token['expires_at'] > time();
    }

    /**
     * Cache token in session
     * 
     * @param string $provider_key Provider-specific key for caching
     * @param string $access_token The access token to cache
     * @param int $expires_in Token lifetime in seconds
     */
    protected function cache_token($provider_key, $access_token, $expires_in)
    {
        $cache_key = $provider_key . '_token_' . $this->environment . '_' . md5(get_staff_user_id());
        $expires_at = time() + $expires_in - 60; // Expire 1 minute early for safety
        
        $token_data = [
            'access_token' => $access_token,
            'expires_at' => $expires_at,
            'created_at' => time()
        ];
        
        $this->CI->session->set_userdata($cache_key, $token_data);
        $this->token_expires_at = $expires_at;
    }

    /**
     * Clear cached token (useful for testing or when token is invalid)
     * 
     * @param string $provider_key Provider-specific key for caching
     */
    protected function clear_token_cache($provider_key)
    {
        $cache_key = $provider_key . '_token_' . $this->environment . '_' . md5(get_staff_user_id());
        $this->CI->session->unset_userdata($cache_key);
        $this->auth_token = null;
        $this->token_expires_at = null;
    }

    /**
     * Get token expiration info for debugging
     * 
     * @param string $provider_key Provider-specific key for caching
     */
    protected function get_token_info($provider_key)
    {
        $cached_token = $this->get_cached_token($provider_key);
        return [
            'has_memory_token' => !empty($this->auth_token),
            'memory_token_expires_at' => $this->token_expires_at,
            'has_cached_token' => !empty($cached_token),
            'cached_token_expires_at' => $cached_token['expires_at'] ?? null,
            'current_time' => time(),
            'is_valid' => $this->is_token_valid()
        ];
    }

    /**
     * Generic token management for OAuth2/API key authentication
     * 
     * @param string $provider_key Provider-specific key for caching
     * @param callable $refresh_callback Function that returns new token data ['access_token' => string, 'expires_in' => int]
     * @return string Valid access token
     */
    protected function get_or_refresh_token($provider_key, $refresh_callback)
    {
        // Check if we have a valid cached token
        if ($this->is_token_valid()) {
            return $this->auth_token;
        }

        // Try to load token from session cache
        $cached_token = $this->get_cached_token($provider_key);
        if ($cached_token && $this->is_cached_token_valid($cached_token)) {
            $this->auth_token = $cached_token['access_token'];
            $this->token_expires_at = $cached_token['expires_at'];
            return $this->auth_token;
        }

        // Generate new token using provider-specific callback
        $this->auth_token = null;
        $this->token_expires_at = null;
        
        $token_data = $refresh_callback();
        if (!isset($token_data['access_token'])) {
            throw new Exception('Token refresh callback must return array with access_token');
        }

        $this->auth_token = $token_data['access_token'];
        $expires_in = $token_data['expires_in'] ?? 3600; // Default to 1 hour
        
        // Cache the token
        $this->cache_token($provider_key, $this->auth_token, $expires_in);
        
        return $this->auth_token;
    }

    // ========================================
    // LEGAL ENTITY MANAGEMENT - DEFAULT IMPLEMENTATIONS
    // ========================================

    /**
     * Default implementation - throws not supported exception
     */
    public function get_legal_entities()
    {
        return [
            'success' => false,
            'message' => 'Legal entity management not supported by this provider',
            'entities' => []
        ];
    }

    /**
     * Default implementation - throws not supported exception
     */
    public function create_legal_entity($entity_data)
    {
        return [
            'success' => false,
            'message' => 'Legal entity management not supported by this provider'
        ];
    }

    /**
     * Default implementation - throws not supported exception
     */
    public function update_legal_entity($entity_id, $entity_data)
    {
        return [
            'success' => false,
            'message' => 'Legal entity management not supported by this provider'
        ];
    }

    /**
     * Default implementation - throws not supported exception
     */
    public function delete_legal_entity($entity_id)
    {
        return [
            'success' => false,
            'message' => 'Legal entity management not supported by this provider'
        ];
    }

    /**
     * Default implementation - throws not supported exception
     */
    public function get_legal_entity($entity_id)
    {
        return [
            'success' => false,
            'message' => 'Legal entity management not supported by this provider',
            'entity' => null
        ];
    }
}