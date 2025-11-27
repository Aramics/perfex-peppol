<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Local Document Bulk Operations Trait
 * 
 * Handles bulk operations for local documents (invoices/credit notes) including:
 * - Bulk action statistics
 * - Bulk sending via PEPPOL
 * - Bulk UBL downloads
 * - Response preparation utilities
 */
trait Peppol_local_document_bulk_trait
{
    /**
     * Get statistics for bulk actions on local documents
     * 
     * Returns count and description for bulk operations like sending unsent documents,
     * retrying failed sends, or downloading UBL files.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @return void Outputs JSON response with statistics
     */
    public function bulk_action_stats($document_type = 'invoice')
    {
        if (!staff_can('view', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $client_id = $this->input->post('client_id');

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

        $stats = [
            'action' => $action,
            'count' => $count,
            'description' => isset($lang_keys[$action]) ? _l($lang_keys[$action]) : _l('peppol_unknown_action'),
            'operation_type' => $action === 'download_sent' ? 'download' : 'send'
        ];

        return $this->json_output([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Bulk send local documents via PEPPOL
     * 
     * Processes multiple local invoices or credit notes for sending through PEPPOL network.
     * Handles both unsent documents and retry operations for failed sends.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @return void Outputs JSON response with operation results
     */
    public function bulk_send($document_type = 'invoice')
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $client_id = $this->input->post('client_id');
        $document_ids = $this->peppol_model->get_document_ids_for_action($document_type, $action, $client_id);

        if (empty($document_ids)) {
            return $this->json_output([
                'success' => false,
                'message' => _l('peppol_no_invoices_found')
            ]);
        }

        // Process documents
        $total = count($document_ids);
        $success = 0;
        $errors = 0;
        $error_messages = [];

        foreach ($document_ids as $document_id) {
            try {
                $result = $this->peppol_service->send_document($document_type, $document_id);

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

        return $this->json_output($this->_prepare_bulk_response($total, $success, $errors, $error_messages));
    }

    /**
     * Bulk download UBL files for local documents
     * 
     * Generates and packages multiple UBL XML files into a ZIP archive for download.
     * Can download UBL files for sent documents or generate UBL for any valid documents.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @return void Initiates file download or shows error
     */
    public function bulk_download_ubl($document_type = 'invoice')
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
                    $number = $document_type == 'invoice'
                        ? format_invoice_number($document['id'])
                        : format_credit_note_number($document['id']);
                    $filename = $number . '_ubl.xml';
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
     * Prepare standardized response for bulk operations
     * 
     * Creates a consistent JSON response structure for all bulk operations,
     * including progress tracking and error handling.
     * 
     * @param int $total Total number of documents processed
     * @param int $success Number of successful operations
     * @param int $errors Number of failed operations
     * @param array $error_messages Array of error messages (limited to 10)
     * @return array Structured response array for JSON output
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
     * Get local documents for bulk download operations
     * 
     * Retrieves document lists based on the requested action type and filters.
     * Handles both sent PEPPOL documents and all valid local documents.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param string $action Action type ('download_sent' or 'download_all_ubl')
     * @param int|null $client_id Optional client filter
     * @return array Array of document objects
     */
    private function _get_documents_for_download($document_type, $action, $client_id = null)
    {
        if ($document_type === 'invoice') {
            $this->load->model('invoices_model');

            switch ($action) {
                case 'download_sent':
                    // Get only sent PEPPOL documents
                    $peppol_docs = $this->peppol_model->get_documents_by_statuses($document_type, ['SENT', 'TECHNICAL_ACCEPTANCE']);
                    $doc_ids = array_column($peppol_docs, 'local_reference_id');
                    if (empty($doc_ids)) return [];
                    $this->db->where_in(db_prefix() . 'invoices' . '.id', $doc_ids);
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
                    $peppol_docs = $this->peppol_model->get_documents_by_statuses($document_type, ['SENT', 'TECHNICAL_ACCEPTANCE']);
                    $doc_ids = array_column($peppol_docs, 'local_reference_id');
                    if (empty($doc_ids)) return [];
                    $this->db->where_in(db_prefix() . 'creditnotes.id', $doc_ids);
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
}