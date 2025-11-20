<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('peppol/peppol_model');

        // Load the advanced UBL generator if available
        $this->CI->load->library('peppol/peppol_ubl_generator');
    }

    /**
     * Send invoice via PEPPOL
     */
    public function send_invoice($invoice_id)
    {
        try {
            // Prepare and validate data
            $data = $this->_prepare_document_data('invoice', $invoice_id);
            if (!$data['success']) {
                return $data;
            }

            // Generate UBL content with complete data (payments read from invoice object)
            $ubl_content = $this->generate_invoice_ubl($data['document'], $data['sender_info'], $data['receiver_info']);

            // Send via provider
            $result = $data['provider']->send('invoice', $ubl_content, $data['document_data'], $data['sender_info'], $data['receiver_info']);

            return $this->_handle_send_result('invoice', $invoice_id, $result, $data['provider']);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send credit note via PEPPOL
     */
    public function send_credit_note($credit_note_id)
    {
        try {
            // Prepare and validate data
            $data = $this->_prepare_document_data('credit_note', $credit_note_id);
            if (!$data['success']) {
                return $data;
            }

            // Generate UBL content with complete data
            $ubl_content = $this->generate_credit_note_ubl($data['document'], $data['sender_info'], $data['receiver_info']);

            // Send via provider
            $result = $data['provider']->send('credit_note', $ubl_content, $data['document_data'], $data['sender_info'], $data['receiver_info']);

            return $this->_handle_send_result('credit_note', $credit_note_id, $result, $data['provider']);
        } catch (Exception $e) {
            // Update credit note status display to Failed on exception
            $this->update_credit_note_status_display($credit_note_id, 'Failed');

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate UBL for invoice
     */
    public function generate_invoice_ubl($invoice, $sender_info, $receiver_info)
    {
        try {
            // Get invoice items
            $invoice_items = get_items_by_type('invoice', $invoice->id);
            if (empty($invoice_items)) {
                throw new Exception('Invoice must have at least one item');
            }

            // Check if library is available
            if (!isset($this->CI->peppol_ubl_generator) || !$this->CI->peppol_ubl_generator->is_library_available()) {
                throw new Exception('UBL generation library is not available. Please ensure the Einvoicing library is properly installed.');
            }

            // Add bank details to invoice object for UBL generator
            $invoice->bank_details = $this->_get_bank_details();

            // Add payment terms templates for UBL generator
            $invoice->payment_terms_templates = $this->_get_payment_terms_templates();

            // Generate UBL using library with complete data (payments read from invoice object)
            return $this->CI->peppol_ubl_generator->generate_invoice_ubl($invoice, $invoice_items, $sender_info, $receiver_info);
        } catch (Exception $e) {
            throw new Exception('Error generating invoice UBL: ' . $e->getMessage());
        }
    }

    /**
     * Generate UBL for credit note
     */
    public function generate_credit_note_ubl($credit_note, $sender_info, $receiver_info)
    {
        try {
            // Get credit note items
            $credit_note_items = get_items_by_type('credit_note', $credit_note->id);
            if (empty($credit_note_items)) {
                throw new Exception('Credit note must have at least one item');
            }

            // Check if library is available
            if (!isset($this->CI->peppol_ubl_generator) || !$this->CI->peppol_ubl_generator->is_library_available()) {
                throw new Exception('UBL generation library is not available. Please ensure the Einvoicing library is properly installed.');
            }

            // Add bank details to credit note object for UBL generator
            // @todo Add client bank details via custom fields
            $credit_note->bank_details = [];

            // Add payment terms templates for UBL generator
            $credit_note->payment_terms_templates = $this->_get_payment_terms_templates();

            // Generate UBL using library with complete data
            return $this->CI->peppol_ubl_generator->generate_credit_note_ubl($credit_note, $credit_note_items, $sender_info, $receiver_info);
        } catch (Exception $e) {
            throw new Exception('Error generating credit note UBL: ' . $e->getMessage());
        }
    }

    /**
     * Get client custom field value
     */
    public function get_client_custom_field($client_id, $field_slug)
    {
        // Get custom field ID for the given field slug
        $this->CI->db->where('fieldto', 'customers');
        $this->CI->db->where('slug', $field_slug);
        $custom_field = $this->CI->db->get(db_prefix() . 'customfields')->row();

        if (!$custom_field) {
            return '';
        }

        // Get custom field value
        $this->CI->db->where('relid', $client_id);
        $this->CI->db->where('fieldid', $custom_field->id);
        $field_value = $this->CI->db->get(db_prefix() . 'customfieldsvalues')->row();

        return $field_value ? $field_value->value : '';
    }

    /**
     * Update credit note PEPPOL status custom field (for display only)
     */
    public function update_credit_note_status_display($credit_note_id, $status)
    {
        // Get the custom field ID
        $this->CI->db->where('fieldto', 'credit_notes');
        $this->CI->db->where('slug', 'credit_notes_peppol_status');
        $custom_field = $this->CI->db->get(db_prefix() . 'customfields')->row();

        if (!$custom_field) {
            return false;
        }

        // Check if value already exists
        $this->CI->db->where('relid', $credit_note_id);
        $this->CI->db->where('fieldid', $custom_field->id);
        $existing_value = $this->CI->db->get(db_prefix() . 'customfieldsvalues')->row();

        if ($existing_value) {
            // Update existing value
            $this->CI->db->where('id', $existing_value->id);
            $this->CI->db->update(db_prefix() . 'customfieldsvalues', ['value' => $status]);
        } else {
            // Insert new value
            $this->CI->db->insert(db_prefix() . 'customfieldsvalues', [
                'relid' => $credit_note_id,
                'fieldid' => $custom_field->id,
                'value' => $status
            ]);
        }

        return true;
    }

    /**
     * Prepare common document data for sending
     * 
     * @param string $document_type 'invoice' or 'credit_note'
     * @param int $document_id Document ID
     * @return array Array with success status and prepared data
     */
    private function _prepare_document_data($document_type, $document_id)
    {
        // Get active provider
        $provider = peppol_get_active_provider();
        if (!$provider) {
            return [
                'success' => false,
                'message' => _l('peppol_no_active_provider')
            ];
        }

        // Load document
        $document = $this->_load_document($document_type, $document_id);
        if (!$document['success']) {
            return $document;
        }

        // Get and validate client data
        try {
            $client = $this->get_client($document['document']->clientid);
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage()
            ];
        }

        // Check if already sent
        $existing = $this->CI->peppol_model->get_peppol_document($document_type, $document_id);
        if ($existing) {
            $lang_key = $document_type === 'invoice' ? 'peppol_invoice_already_processed' : 'peppol_credit_note_already_processed';
            return [
                'success' => false,
                'message' => _l($lang_key)
            ];
        }

        // Prepare sender and receiver info
        $sender_info = $this->prepare_sender_info();
        $receiver_info = $this->prepare_receiver_info($client);

        // Validate PEPPOL identifiers
        $errors = [];

        // Validate sender PEPPOL identifier
        $sender_validation = $this->_validate_entity_peppol_identifier($sender_info, 'sender');
        if (!$sender_validation['valid']) {
            $errors = array_merge($errors, $sender_validation['errors']);
        }

        // Validate receiver PEPPOL identifier
        $receiver_validation = $this->_validate_entity_peppol_identifier($receiver_info, 'receiver');
        if (!$receiver_validation['valid']) {
            $errors = array_merge($errors, $receiver_validation['errors']);
        }

        // Return validation result if there are errors
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => _l('peppol_validation_failed') . ': ' . implode(' ', $errors),
                'validation_errors' => $errors
            ];
        }

        return [
            'success' => true,
            'provider' => $provider,
            'document' => $document['document'],
            'client' => $client,
            'document_data' => $this->_prepare_document_metadata($document['document']),
            'sender_info' => $sender_info,
            'receiver_info' => $receiver_info
        ];
    }

    /**
     * Load document by type and ID
     */
    private function _load_document($document_type, $document_id)
    {
        if ($document_type === 'invoice') {
            $this->CI->load->model('invoices_model');
            $document = $this->CI->invoices_model->get($document_id);
            $not_found_message = _l('peppol_invoice_not_found');
        } else {
            $this->CI->load->model('credit_notes_model');
            $document = $this->CI->credit_notes_model->get($document_id);
            $not_found_message = _l('peppol_credit_note_not_found');
        }

        if (!$document) {
            return [
                'success' => false,
                'message' => $not_found_message
            ];
        }

        return [
            'success' => true,
            'document' => $document
        ];
    }

    /**
     * Prepare document metadata
     */
    private function _prepare_document_metadata($document)
    {
        return [
            'id' => $document->id,
            'number' => $document->number,
            'date' => $document->date,
            'total' => $document->total
        ];
    }

    /**
     * Prepare sender info (company) with complete UBL data
     */
    public function prepare_sender_info()
    {
        return [
            'identifier' => get_option('peppol_company_identifier'),
            'scheme' => get_option('peppol_company_scheme') ?: '0208',
            'name' => get_option('companyname'),
            'contact_name' => get_option('companyname'),
            'address' => get_option('company_address'),
            'city' => get_option('company_city'),
            'postal_code' => get_option('company_zip'),
            'country_code' => get_option('peppol_company_country_code') ?: get_option('invoice_company_country_code') ?: 'BE',
            'vat_number' => get_option('company_vat'),
            'phone' => get_option('company_phonenumber'),
            'email' => get_option('company_email'),
            'website' => base_url(),
            'contact_type' => 'public'
        ];
    }

    /**
     * Prepare receiver info (client) with complete UBL data
     */
    public function prepare_receiver_info($client)
    {
        // Get PEPPOL identifier and scheme from client custom fields
        $identifier = $this->get_client_custom_field($client->userid, 'customers_peppol_identifier');
        $scheme = $this->get_client_custom_field($client->userid, 'customers_peppol_scheme');

        // Client name (company or individual)
        $client_name = $client->company ?: ($client->firstname . ' ' . $client->lastname);

        return [
            'identifier' => $identifier,
            'scheme' => $scheme,
            'name' => $client_name,
            'address' => $client->address,
            'city' => $client->city,
            'postal_code' => $client->zip,
            'country_code' => get_country($client->country)->iso2 ?? 'BE',
            'vat_number' => $client->vat,
            'phone' => $client->phonenumber ?: ($client->contacts[0]['phonenumber'] ?? ''),
            'email' => $client->contacts[0]['email'] ?? '',
            'contact_name' => trim(($client->contacts[0]['firstname'] ??  '') . ' ' . ($client->contacts[0]['lastname'] ??  '')),
            'website' => $client->website ?? '',
            'contact_type' => 'primary',
        ];
    }

    public function get_client($client_id)
    {

        $this->CI->load->model('clients_model');
        $client = $this->CI->clients_model->get($client_id);
        $client->contacts = $this->CI->clients_model->get_contacts($client_id, ['active' => 1, 'is_primary' => 1]);

        if (!$client) {
            throw new \Exception(sprintf(_l('peppol_client_not_found'), $client_id), 1);
        }

        return $client;
    }


    /**
     * Validate PEPPOL identifier and scheme for a single entity
     * 
     * @param array $entity_info Entity information
     * @param string $entity_type 'sender' or 'receiver'
     * @return array Validation result with errors
     */
    private function _validate_entity_peppol_identifier($entity_info, $entity_type)
    {
        $errors = [];

        // Validate identifier
        if (empty($entity_info['identifier'])) {
            $errors[] = _l('peppol_' . $entity_type . '_identifier_required');
        } else {
            // Basic format validation
            if (strlen(trim($entity_info['identifier'])) < 3) {
                $errors[] = _l('peppol_identifier_too_short');
            }
        }

        // Validate scheme
        if (empty($entity_info['scheme'])) {
            $errors[] = _l('peppol_' . $entity_type . '_scheme_required');
        } else {
            // Validate scheme format (typically 4 digits)
            if (!preg_match('/^[0-9]{4}$/', $entity_info['scheme'])) {
                $errors[] = _l('peppol_scheme_invalid_format');
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Handle send result and create records
     */
    private function _handle_send_result($document_type, $document_id, $result, $provider)
    {
        if ($result['success']) {

            $peppol_data = [
                'document_type' => $document_type,
                'document_id' => $document_id,
                'status' => 'sent',
                'provider' => $provider->get_id(),
                'provider_document_id' => $result['document_id'] ?? null,
                'provider_metadata' => json_encode($result['metadata'] ?? []),
                'sent_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $peppol_id = $this->CI->peppol_model->create_peppol_document($peppol_data);

            // Update credit note status display if needed
            if ($document_type === 'credit_note') {
                $this->update_credit_note_status_display($document_id, 'Sent');
            }

            // Log activity
            $this->CI->peppol_model->log_activity([
                'type' => $document_type . '_sent',
                'document_type' => $document_type,
                'document_id' => $document_id,
                'message' => $result['message'],
                'staff_id' => get_staff_user_id()
            ]);

            return [
                'success' => true,
                'message' => $result['message'],
                'peppol_id' => $peppol_id
            ];
        } else {
            // Update credit note status display to Failed if needed
            if ($document_type === 'credit_note') {
                $this->update_credit_note_status_display($document_id, 'Failed');
            }

            return [
                'success' => false,
                'message' => $result['message']
            ];
        }
    }

    /**
     * Get bank details for UBL generation
     */
    private function _get_bank_details()
    {
        return [
            'account_number' => get_option('peppol_bank_account', ''),
            'bank_bic' => get_option('peppol_bank_bic', ''),
            'account_name' => get_option('peppol_bank_name', '') ?: get_option('companyname', '')
        ];
    }

    /**
     * Get payment terms templates for UBL generation
     */
    private function _get_payment_terms_templates()
    {
        return [
            'partial' => _l('peppol_payment_terms_partial'),
            'paid' => _l('peppol_payment_terms_paid'),
            'refund' => _l('peppol_payment_terms_refund')
        ];
    }

    /**
     * Create Perfex document from UBL XML using dedicated parser
     * 
     * @param string $ubl_xml The UBL XML content
     * @param string $document_id External document ID
     * @param array $metadata Additional metadata
     * @return array Result with success status and created document info
     */
    public function create_document_from_ubl($ubl_xml, $document_id, $metadata = [])
    {
        // Load the dedicated UBL document parser
        $this->CI->load->library('peppol/peppol_ubl_document_parser');
        
        // Use the parser to create the document
        return $this->CI->peppol_ubl_document_parser->parse_and_create($ubl_xml, $document_id, $metadata);
    }
}