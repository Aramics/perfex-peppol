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
            // Load invoice
            $this->CI->load->model('invoices_model');
            $invoice = $this->CI->invoices_model->get($invoice_id);

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => _l('peppol_invoice_not_found')
                ];
            }

            // Check if client has PEPPOL identifier
            $this->CI->load->model('clients_model');
            $client = $this->CI->clients_model->get($invoice->clientid);
            
            if (!$client) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_client_no_identifier'), $invoice->clientid)
                ];
            }

            // Check PEPPOL identifier from custom field
            $customer_identifier = $this->_get_client_custom_field($client->userid, 'customers_peppol_identifier');
            if (empty($customer_identifier)) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_client_no_identifier'), $invoice->clientid)
                ];
            }

            // Check if already sent
            $existing = $this->CI->peppol_model->get_peppol_invoice_by_invoice($invoice_id);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => _l('peppol_invoice_already_processed')
                ];
            }

            // Create PEPPOL invoice record
            $peppol_data = [
                'invoice_id' => $invoice_id,
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $peppol_id = $this->CI->peppol_model->create_peppol_invoice($peppol_data);

            // Log activity
            $this->CI->peppol_model->log_activity([
                'type' => 'invoice_sent',
                'document_type' => 'invoice',
                'document_id' => $invoice_id,
                'message' => _l('peppol_invoice_sent_activity'),
                'staff_id' => get_staff_user_id()
            ]);

            return [
                'success' => true,
                'message' => _l('peppol_invoice_sent_successfully'),
                'peppol_id' => $peppol_id
            ];

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
            // Load credit note
            $this->CI->load->model('credit_notes_model');
            $credit_note = $this->CI->credit_notes_model->get($credit_note_id);

            if (!$credit_note) {
                return [
                    'success' => false,
                    'message' => _l('peppol_credit_note_not_found')
                ];
            }

            // Check if client has PEPPOL identifier
            $this->CI->load->model('clients_model');
            $client = $this->CI->clients_model->get($credit_note->clientid);
            
            if (!$client) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_client_no_identifier'), $credit_note->clientid)
                ];
            }

            // Check PEPPOL identifier from custom field
            $customer_identifier = $this->_get_client_custom_field($client->userid, 'customers_peppol_identifier');
            if (empty($customer_identifier)) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_client_no_identifier'), $credit_note->clientid)
                ];
            }

            // Check if already sent
            $existing = $this->CI->peppol_model->get_peppol_credit_note_by_credit_note($credit_note_id);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => _l('peppol_credit_note_already_processed')
                ];
            }

            // Create PEPPOL credit note record
            $peppol_data = [
                'credit_note_id' => $credit_note_id,
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $peppol_id = $this->CI->peppol_model->create_peppol_credit_note($peppol_data);

            // Update status display field
            $this->update_credit_note_status_display($credit_note_id, 'Sent');

            // Log activity
            $this->CI->peppol_model->log_activity([
                'type' => 'credit_note_sent',
                'document_type' => 'credit_note',
                'document_id' => $credit_note_id,
                'message' => _l('peppol_credit_note_sent_activity'),
                'staff_id' => get_staff_user_id()
            ]);

            return [
                'success' => true,
                'message' => _l('peppol_credit_note_sent_successfully'),
                'peppol_id' => $peppol_id
            ];

        } catch (Exception $e) {
            // Update status display field to Failed
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
    public function generate_invoice_ubl($invoice)
    {
        try {
            // Load required models
            $this->CI->load->model('invoices_model');
            $this->CI->load->model('clients_model');

            // Get client data
            $client = $this->CI->clients_model->get($invoice->clientid);
            if (!$client) {
                throw new Exception('Client not found for invoice');
            }

            // Get invoice items
            $invoice_items = get_items_by_type('invoice', $invoice->id);
            if (empty($invoice_items)) {
                throw new Exception('Invoice must have at least one item');
            }

            // Check if library is available
            if (!isset($this->CI->peppol_ubl_generator) || !$this->CI->peppol_ubl_generator->is_library_available()) {
                throw new Exception('UBL generation library is not available. Please ensure the Einvoicing library is properly installed.');
            }

            // Generate UBL using library
            return $this->CI->peppol_ubl_generator->generate_invoice_ubl($invoice, $client, $invoice_items);

        } catch (Exception $e) {
            throw new Exception('Error generating invoice UBL: ' . $e->getMessage());
        }
    }

    /**
     * Generate UBL for credit note
     */
    public function generate_credit_note_ubl($credit_note)
    {
        try {
            // Load required models
            $this->CI->load->model('credit_notes_model');
            $this->CI->load->model('clients_model');

            // Get client data
            $client = $this->CI->clients_model->get($credit_note->clientid);
            if (!$client) {
                throw new Exception('Client not found for credit note');
            }

            // Get credit note items
            $credit_note_items = get_items_by_type('credit_note', $credit_note->id);
            if (empty($credit_note_items)) {
                throw new Exception('Credit note must have at least one item');
            }

            // Check if library is available
            if (!isset($this->CI->peppol_ubl_generator) || !$this->CI->peppol_ubl_generator->is_library_available()) {
                throw new Exception('UBL generation library is not available. Please ensure the Einvoicing library is properly installed.');
            }

            // Generate UBL using library
            return $this->CI->peppol_ubl_generator->generate_credit_note_ubl($credit_note, $client, $credit_note_items);

        } catch (Exception $e) {
            throw new Exception('Error generating credit note UBL: ' . $e->getMessage());
        }
    }

    /**
     * Get client custom field value
     */
    private function _get_client_custom_field($client_id, $field_slug)
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

}