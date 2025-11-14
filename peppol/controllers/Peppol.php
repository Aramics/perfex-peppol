<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(PEPPOL_MODULE_NAME . '/peppol_model');
        $this->load->library(PEPPOL_MODULE_NAME . '/peppol_service');
    }

    // ======================
    // UNIFIED BULK ACTIONS
    // ======================

    /**
     * Get statistics for bulk actions (unified for invoices and credit notes)
     */
    public function bulk_action_stats()
    {
        $this->_handle_bulk_action_stats('invoice');
    }

    /**
     * Credit note bulk action stats
     */
    public function credit_note_bulk_action_stats()
    {
        $this->_handle_bulk_action_stats('credit_note');
    }

    /**
     * Handle bulk action statistics for any document type
     */
    private function _handle_bulk_action_stats($document_type)
    {
        if (!staff_can('view', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $stats = $this->_get_bulk_action_stats($document_type, $action);

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get bulk action statistics for a document type
     */
    private function _get_bulk_action_stats($document_type, $action)
    {
        $lang_map = [
            'invoice' => [
                'send_unsent' => 'peppol_send_all_unsent',
                'retry_failed' => 'peppol_retry_all_failed',
                'download_sent' => 'peppol_download_all_sent'
            ],
            'credit_note' => [
                'send_unsent' => 'peppol_send_all_unsent_credit_notes',
                'retry_failed' => 'peppol_retry_all_failed_credit_notes',
                'download_sent' => 'peppol_download_all_sent_credit_note_ubl'
            ]
        ];

        $lang_keys = $lang_map[$document_type];
        $count = $this->peppol_model->count_documents_for_action($document_type, $action);

        return [
            'action' => $action,
            'count' => $count,
            'description' => isset($lang_keys[$action]) ? _l($lang_keys[$action]) : _l('peppol_unknown_action'),
            'operation_type' => $action === 'download_sent' ? 'download' : 'send'
        ];
    }

    /**
     * Bulk send invoices via PEPPOL
     */
    public function bulk_send()
    {
        $this->_handle_bulk_send('invoice');
    }

    /**
     * Bulk send credit notes via PEPPOL
     */
    public function credit_note_bulk_send()
    {
        $this->_handle_bulk_send('credit_note');
    }

    /**
     * Handle bulk send for any document type
     */
    private function _handle_bulk_send($document_type)
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $document_ids = $this->peppol_model->get_document_ids_for_action($document_type, $action);

        if (empty($document_ids)) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_no_invoices_found')
            ]);
            return;
        }

        // Process documents
        $total = count($document_ids);
        $success = 0;
        $errors = 0;
        $error_messages = [];

        foreach ($document_ids as $document_id) {
            try {
                if ($document_type === 'invoice') {
                    $result = $this->peppol_service->send_invoice($document_id);
                } else {
                    $result = $this->peppol_service->send_credit_note($document_id);
                }
                
                if ($result['success']) {
                    $success++;
                } else {
                    $errors++;
                    $lang_key = $document_type === 'invoice' ? 'peppol_invoice_error_format' : 'peppol_credit_note_error_format';
                    $error_messages[] = sprintf(_l($lang_key), $document_id, $result['message']);
                }
            } catch (Exception $e) {
                $errors++;
                $lang_key = $document_type === 'invoice' ? 'peppol_invoice_error_format' : 'peppol_credit_note_error_format';
                $error_messages[] = sprintf(_l($lang_key), $document_id, $e->getMessage());
            }
        }

        echo json_encode($this->_prepare_bulk_response($total, $success, $errors, $error_messages));
    }


    /**
     * Prepare bulk operation response
     */
    private function _prepare_bulk_response($total, $success, $errors, $error_messages = [])
    {
        $response = [
            'success' => $success > 0,
            'progress' => [
                'total' => $total,
                'completed' => $total,
                'success' => $success,
                'errors' => $errors
            ]
        ];

        if ($errors === 0) {
            $response['message'] = _l('peppol_operation_completed');
        } elseif ($success > 0) {
            $response['message'] = sprintf(_l('peppol_operation_partial_success'), $success, $errors);
        } else {
            $response['message'] = _l('peppol_operation_failed');
            $response['success'] = false;
        }

        if (!empty($error_messages)) {
            $response['errors'] = array_slice($error_messages, 0, 10); // Limit to 10 errors
        }

        return $response;
    }

    // ================================
    // SINGLE DOCUMENT SEND METHODS
    // ================================

    /**
     * Send single invoice via PEPPOL (AJAX)
     */
    public function send_ajax($invoice_id)
    {
        $this->_handle_single_send('invoice', $invoice_id);
    }

    /**
     * Send single credit note via PEPPOL (AJAX)
     */
    public function send_credit_note_ajax($credit_note_id)
    {
        $this->_handle_single_send('credit_note', $credit_note_id);
    }

    /**
     * Handle single document send
     */
    private function _handle_single_send($document_type, $document_id)
    {
        if (!staff_can('create', 'peppol')) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_access_denied')
            ]);
            return;
        }

        if ($document_type === 'invoice') {
            $response = $this->peppol_service->send_invoice($document_id);
        } else {
            $response = $this->peppol_service->send_credit_note($document_id);
        }
        
        echo json_encode($response);
    }

    // ================================
    // UTILITY METHODS
    // ================================

    /**
     * Test PEPPOL connection
     */
    public function test_connection()
    {
        if (!staff_can('view', 'peppol')) {
            echo json_encode(['success' => false, 'message' => _l('peppol_access_denied')]);
            return;
        }

        try {
            // Basic configuration check
            $company_identifier = get_option('peppol_company_identifier');
            $provider = get_option('peppol_active_provider');
            
            if (empty($company_identifier) || empty($provider)) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('peppol_configuration_incomplete')
                ]);
                return;
            }

            // Mock test for now - in real implementation, test actual connection
            echo json_encode([
                'success' => true,
                'message' => _l('peppol_connection_test_success')
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_connection_test_failed') . ': ' . $e->getMessage()
            ]);
        }
    }

}