<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('peppol/peppol_model');
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

            if (!$client || empty($client->peppol_identifier)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_client_no_identifier')
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
                'invoice_id' => $invoice_id,
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

            if (!$client || empty($client->peppol_identifier)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_client_no_identifier')
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

            // Log activity
            $this->CI->peppol_model->log_activity([
                'type' => 'credit_note_sent',
                'credit_note_id' => $credit_note_id,
                'message' => _l('peppol_credit_note_sent_activity'),
                'staff_id' => get_staff_user_id()
            ]);

            return [
                'success' => true,
                'message' => _l('peppol_credit_note_sent_successfully'),
                'peppol_id' => $peppol_id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}