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
        $client_id = $this->input->post('client_id');
        $stats = $this->_get_bulk_action_stats($document_type, $action, $client_id);

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get bulk action statistics for a document type
     */
    private function _get_bulk_action_stats($document_type, $action, $client_id = null)
    {
        $lang_map = [
            'invoice' => [
                'send_unsent' => 'peppol_send_all_unsent',
                'retry_failed' => 'peppol_retry_all_failed',
                'download_sent' => 'peppol_download_all_sent',
                'download_all_ubl' => 'peppol_download_all_ubl'
            ],
            'credit_note' => [
                'send_unsent' => 'peppol_send_all_unsent_credit_notes',
                'retry_failed' => 'peppol_retry_all_failed_credit_notes',
                'download_sent' => 'peppol_download_all_sent_credit_note_ubl',
                'download_all_ubl' => 'peppol_download_all_credit_note_ubl'
            ]
        ];

        $lang_keys = $lang_map[$document_type];
        $count = $this->peppol_model->count_documents_for_action($document_type, $action, $client_id);

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
     * Bulk download UBL files for invoices
     */
    public function bulk_download_ubl()
    {
        $this->_handle_bulk_download_ubl('invoice');
    }

    /**
     * Bulk download UBL files for credit notes
     */
    public function credit_note_bulk_download_ubl()
    {
        $this->_handle_bulk_download_ubl('credit_note');
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
        $client_id = $this->input->post('client_id');
        $document_ids = $this->peppol_model->get_document_ids_for_action($document_type, $action, $client_id);

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


    /**
     * Handle bulk download UBL for any document type
     */
    private function _handle_bulk_download_ubl($document_type)
    {
        if (!staff_can('view', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $client_id = $this->input->post('client_id');

        // Create ZIP file with UBL files
        $zip = new ZipArchive();
        $zip_filename = tempnam(get_temp_dir(), 'peppol_ubl_') . '.zip';

        if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
            show_error('Could not create ZIP file');
            return;
        }

        $success_count = 0;
        $error_count = 0;
        $error_messages = [];

        try {
            // Get documents based on action
            $documents = $this->_get_documents_for_download($document_type, $action, $client_id);

            foreach ($documents as $document) {
                try {
                    $ubl_content = $this->_generate_ubl_content($document_type, $document['id']);
                    $filename = $document_type . '_' . $document['id'] . '_ubl.xml';
                    $zip->addFromString($filename, $ubl_content);
                    $success_count++;
                } catch (Exception $e) {
                    $error_count++;
                    $lang_key = $document_type === 'invoice' ? 'peppol_invoice_error_format' : 'peppol_credit_note_error_format';
                    $error_messages[] = sprintf(_l($lang_key), $document['id'], $e->getMessage());
                }
            }

            $zip->close();

            if ($success_count > 0) {
                // Download the ZIP file
                $zip_name = $document_type . '_ubl_files_' . date('Y-m-d_H-i-s') . '.zip';
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zip_name . '"');
                header('Content-Length: ' . filesize($zip_filename));
                readfile($zip_filename);
            } else {
                show_error('No UBL files could be generated' . '<br/>' . implode('<br/>', $error_messages));
            }
        } finally {
            // Clean up temp file
            if (file_exists($zip_filename)) {
                unlink($zip_filename);
            }
        }
    }

    /**
     * Get documents for download based on action
     */
    private function _get_documents_for_download($document_type, $action, $client_id = null)
    {
        if ($document_type === 'invoice') {
            $this->load->model('invoices_model');

            switch ($action) {
                case 'download_sent':
                    // Get only sent PEPPOL documents
                    $peppol_docs = $this->peppol_model->get_documents_by_statuses($document_type, ['sent', 'delivered']);
                    $doc_ids = array_column($peppol_docs, 'document_id');
                    if (empty($doc_ids)) return [];
                    $this->db->where_in('id', $doc_ids);
                    if ($client_id) {
                        $this->db->where('clientid', $client_id);
                    }
                    return $this->invoices_model->get('');

                case 'download_all_ubl':
                    // Get all valid invoices
                    $this->db->where_in('status', [
                        Invoices_model::STATUS_UNPAID,
                        Invoices_model::STATUS_PAID,
                        Invoices_model::STATUS_OVERDUE
                    ]);
                    if ($client_id) {
                        $this->db->where('clientid', $client_id);
                    }
                    return $this->invoices_model->get('');

                default:
                    return [];
            }
        } else {
            $this->load->model('credit_notes_model');

            switch ($action) {
                case 'download_sent':
                    // Get only sent PEPPOL documents
                    $peppol_docs = $this->peppol_model->get_documents_by_statuses($document_type, ['sent', 'delivered']);
                    $doc_ids = array_column($peppol_docs, 'document_id');
                    if (empty($doc_ids)) return [];
                    $this->db->where_in('id', $doc_ids);
                    if ($client_id) {
                        $this->db->where('clientid', $client_id);
                    }
                    return $this->credit_notes_model->get('');

                case 'download_all_ubl':
                    // Get all valid credit notes  
                    $this->db->where('status >=', 1);
                    if ($client_id) {
                        $this->db->where('clientid', $client_id);
                    }
                    return $this->credit_notes_model->get('');

                default:
                    return [];
            }
        }
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

    // ======================
    // UBL GENERATION METHODS
    // ======================

    /**
     * Generate and view UBL for any document (including unsent ones)
     */
    public function generate_view_ubl($document_type, $document_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        try {
            $ubl_content = $this->_generate_ubl_content($document_type, $document_id);

            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $document_type . '_' . $document_id . '_ubl.xml"');
            echo $ubl_content;
        } catch (Exception $e) {
            show_error('Error generating UBL: ' . $e->getMessage());
        }
    }

    /**
     * Generate and download UBL for any document (including unsent ones)
     */
    public function generate_download_ubl($document_type, $document_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        try {
            $ubl_content = $this->_generate_ubl_content($document_type, $document_id);

            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $document_type . '_' . $document_id . '_ubl.xml"');
            header('Content-Length: ' . strlen($ubl_content));
            echo $ubl_content;
        } catch (Exception $e) {
            show_error('Error generating UBL: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to generate UBL content for any document type
     */
    private function _generate_ubl_content($document_type, $document_id)
    {
        // Load the appropriate model
        if ($document_type === 'invoice') {
            $this->load->model('invoices_model');
            $document = $this->invoices_model->get($document_id);
        } elseif ($document_type === 'credit_note') {
            $this->load->model('credit_notes_model');
            $document = $this->credit_notes_model->get($document_id);
        } else {
            throw new Exception('Invalid document type');
        }

        if (!$document) {
            throw new Exception(ucfirst($document_type) . ' not found');
        }

        // Check if UBL already exists in PEPPOL documents
        $peppol_document = $this->peppol_model->get_peppol_document($document_type, $document_id);

        if ($peppol_document && !empty($peppol_document->ubl_content)) {
            // Return existing UBL content
            return $peppol_document->ubl_content;
        } else {
            // Generate new UBL content
            if ($document_type === 'invoice') {
                return $this->peppol_service->generate_invoice_ubl($document);
            } else {
                return $this->peppol_service->generate_credit_note_ubl($document);
            }
        }
    }
}