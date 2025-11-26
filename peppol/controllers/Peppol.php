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

        // Load client data
        $client = $this->peppol_service->get_client($document->clientid);

        // Prepare sender info using service
        $sender_info = $this->peppol_service->prepare_sender_info();

        // Prepare receiver info using service
        $receiver_info = $this->peppol_service->prepare_receiver_info($client);

        // Generate UBL content with complete data
        if ($document_type === 'invoice') {
            return $this->peppol_service->generate_invoice_ubl($document, $sender_info, $receiver_info);
        } else {
            return $this->peppol_service->generate_credit_note_ubl($document, $sender_info, $receiver_info);
        }
    }



    // ================================
    // DATABASE MANAGEMENT METHODS
    // ================================

    /**
     * Upgrade database schema
     */
    public function upgrade_database()
    {
        if (!is_admin()) {
            access_denied('admin');
        }

        try {
            // Run the installation hook which includes upgrade logic
            require_once(__DIR__ . '/../install.php');

            set_alert('success', 'Database schema updated successfully. New provider and provider_metadata columns have been added.');
        } catch (Exception $e) {
            set_alert('danger', 'Database upgrade failed: ' . $e->getMessage());
        }

        redirect(admin_url('settings?group=peppol'));
    }

    // ================================
    // PROVIDER MANAGEMENT METHODS
    // ================================

    /**
     * Test provider connection (AJAX)
     */
    public function test_provider_connection()
    {
        if (!staff_can('create', 'settings') || !$this->input->post()) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_access_denied')
            ]);
            return;
        }

        $provider_id = $this->input->post('provider');
        $form_settings = $this->input->post('settings');

        if (!$provider_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_invalid_provider')
            ]);
            return;
        }

        try {
            // Get registered providers
            $providers = peppol_get_registered_providers();

            if (!isset($providers[$provider_id])) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('peppol_provider_not_found')
                ]);
                return;
            }

            $provider_instance = $providers[$provider_id];

            // Filter and clean settings for this provider
            $provider_settings = [];
            $provider_prefix = "peppol_{$provider_id}_";

            if (is_array($form_settings)) {
                foreach ($form_settings as $key => $value) {
                    // Extract settings that belong to this provider and remove prefix
                    if (strpos($key, $provider_prefix) === 0) {
                        $clean_key = str_replace($provider_prefix, '', $key);
                        $provider_settings[$clean_key] = $value;
                    }
                }
            }

            // Test the connection with cleaned settings
            $result = $provider_instance->test_connection($provider_settings);

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ================================
    // DOCUMENTS MANAGEMENT METHODS
    // ================================

    /**
     * Test method to create sample PEPPOL document (for debugging)
     */
    public function create_test_document()
    {
        if (!is_admin()) {
            access_denied('admin');
        }

        $data = [
            'document_type' => 'invoice',
            'document_id' => 1, // Assuming invoice ID 1 exists
            'status' => 'sent',
            'provider' => 'ademico',
            'provider_document_id' => 'test-' . time(),
            'provider_metadata' => json_encode(['test' => true]),
            'sent_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert(db_prefix() . 'peppol_documents', $data);
        $id = $this->db->insert_id();

        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Test document created with ID: ' . $id]);
    }

    /**
     * Documents management page
     */
    public function documents($table = '')
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        // Return the table data for ajax request
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path(PEPPOL_MODULE_NAME, 'admin/tables/peppol_documents'));
        }

        $data['title'] = _l('peppol_documents');

        // Get statistics for different document types
        $data['invoice_stats'] = $this->peppol_model->get_document_statistics('invoice');
        $data['credit_note_stats'] = $this->peppol_model->get_document_statistics('credit_note');

        // Get provider information
        $data['providers'] = peppol_get_registered_providers();
        $data['active_provider'] = get_option('peppol_active_provider', '');

        $this->load->view('peppol/admin/documents/manage', $data);
    }

    /**
     * View PEPPOL document details (AJAX)
     */
    public function view_document($id)
    {
        if (!staff_can('view', 'peppol')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $document = $this->peppol_service->get_enriched_document($id);

        if (empty($document->id)) {
            echo json_encode(['success' => false, 'message' => _l('peppol_document_not_found')]);
            return;
        }

        // Parse metadata
        $metadata = json_decode($document->provider_metadata ?? '{}', true) ?: [];

        // Get attachments from UBL document
        $attachments = [];
        if (isset($document->ubl_document['data']['attachments'])) {
            $attachments = $document->ubl_document['data']['attachments'];
        }

        // Prepare simplified view data - pass document directly with minimal processing
        $view_data = [
            'document' => $document,
            'metadata' => $metadata,
            'attachments' => $attachments
        ];

        // Render the view content
        $content = $this->load->view('peppol/templates/document_details_content', $view_data, true);

        echo json_encode([
            'success' => true,
            'content' => $content
        ]);
    }

    /**
     * Download UBL from provider (original UBL file)
     */
    public function download_provider_ubl($document_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        // Use service layer to retrieve UBL
        $result = $this->peppol_service->get_provider_ubl($document_id);

        if (!$result['success']) {
            show_error($result['message']);
            return;
        }

        // Set headers and output UBL content
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['ubl_content']));

        echo $result['ubl_content'];
    }

    /**
     * Mark document response status (AJAX)
     */
    public function mark_document_status()
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $document_id = $this->input->post('document_id');
        $status = $this->input->post('status');
        $note = $this->input->post('note', true);

        if (!$document_id || !$status) {
            echo json_encode(['success' => false, 'message' => _l('peppol_invalid_request_data')]);
            return;
        }

        try {
            $result = $this->peppol_service->mark_document_status($document_id, $status, $note);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

}