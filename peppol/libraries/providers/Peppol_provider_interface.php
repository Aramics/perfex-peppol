<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Simplified PEPPOL Provider Interface
 * 
 * This interface defines the essential methods that all PEPPOL access point providers must implement.
 */
interface Peppol_provider_interface
{
    /**
     * Send a document via PEPPOL
     * 
     * @param string $document_type Document type ('invoice', 'credit_note', etc.)
     * @param string $ubl_content UBL XML content
     * @param array $document_data Document metadata
     * @param array $sender_info Sender information
     * @param array $receiver_info Receiver information
     * @return array Array with 'success', 'message', and optional 'document_id', 'tracking_info'
     */
    public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info);

    /**
     * Handle webhook callbacks from the provider
     * 
     * @param array $payload Webhook payload
     * @return array Array with 'success' and 'message'
     */
    public function webhook($payload);

    /**
     * Test connection to the provider's API/service
     * 
     * @return array Array with 'success' (bool) and 'message' (string) keys
     */
    public function test_connection();

    /**
     * Get setting input definitions
     * 
     * @return array Array of input definitions with properties (type, label, required, etc.)
     */
    public function get_setting_inputs();

    /**
     * Get current settings values
     * 
     * @return array Array of current setting values
     */
    public function get_settings();

    /**
     * Set settings values
     * 
     * @param array $settings Array of setting key-value pairs
     * @return bool True if settings were saved successfully
     */
    public function set_settings($settings);

    /**
     * Get list of supported document types
     * 
     * @return array Array of supported document types (e.g., ['invoice', 'credit_note'])
     */
    public function supported_documents();

    /**
     * Render setting inputs as HTML form fields
     * 
     * @param array $inputs Input definitions (optional, uses get_setting_inputs() if not provided)
     * @param array $current_values Current setting values (optional, uses get_settings() if not provided)
     * @return string HTML form fields
     */
    public function render_setting_inputs($inputs = [], $current_values = []);

    /**
     * Get the provider ID
     * 
     * @return string Provider unique identifier
     */
    public function get_id();
}