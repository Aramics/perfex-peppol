<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Document Management Trait
 * 
 * Handles document management operations including:
 * - Document viewing and details
 * - Document status management
 * - Expense creation from documents
 * - Document listing and filtering
 */
trait Peppol_document_management_trait
{
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

        // Get expense statistics
        $data['expense_stats'] = $this->peppol_model->get_expense_statistics();
        $data['invoice_expense_eligible'] = $this->peppol_model->get_expense_eligible_statistics('invoice');
        $data['credit_note_expense_eligible'] = $this->peppol_model->get_expense_eligible_statistics('credit_note');

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
        $metadata = $document->metadata;

        // Get attachments from UBL document
        $attachments = [];
        if (isset($document->ubl_document['data']['attachments'])) {
            $attachments = $document->ubl_document['data']['attachments'];
        }

        // Get clarifications data for forms
        $clarifications = $this->peppol_service->get_available_clarifications();

        // Prepare simplified view data - pass document directly with minimal processing
        $view_data = [
            'document' => $document,
            'metadata' => $metadata,
            'attachments' => $attachments,
            'clarifications' => $clarifications
        ];

        // Render the view content
        $content = $this->load->view('peppol/templates/document_details_content', $view_data, true);

        echo json_encode([
            'success' => true,
            'content' => $content,
            'clarifications' => $clarifications
        ]);
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
        $effective_date = $this->input->post('effective_date', true);
        $clarifications = $this->input->post('clarifications');

        if (!$document_id || !$status) {
            echo json_encode(['success' => false, 'message' => _l('peppol_invalid_request_data')]);
            return;
        }

        // Process clarifications if provided
        $processed_clarifications = [];
        if (!empty($clarifications) && is_array($clarifications)) {
            foreach ($clarifications as $clarification) {
                if (
                    !empty($clarification['clarificationType']) &&
                    !empty($clarification['clarificationCode']) &&
                    !empty($clarification['clarification'])
                ) {
                    $processed_clarifications[] = [
                        'clarificationType' => $clarification['clarificationType'],
                        'clarificationCode' => $clarification['clarificationCode'],
                        'clarification' => $clarification['clarification']
                    ];
                }
            }
        }

        try {
            $result = $this->peppol_service->mark_document_status(
                $document_id,
                $status,
                $note,
                $processed_clarifications,
                $effective_date
            );
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get available clarifications (AJAX) - Fallback endpoint, rarely used due to caching
     */
    public function get_clarifications()
    {
        if (!staff_can('view', 'peppol')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        try {
            $clarifications = $this->peppol_service->get_available_clarifications();
            echo json_encode([
                'success' => true,
                'data' => $clarifications
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create expense from PEPPOL document (AJAX)
     */
    public function create_expense($document_id)
    {
        if (!staff_can('create', 'expenses')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        if (!$document_id) {
            echo json_encode(['success' => false, 'message' => _l('peppol_invalid_request_data')]);
            return;
        }

        // If it's a GET request, return the expense creation form
        if (!$this->input->post()) {
            try {
                $form_data = $this->peppol_service->prepare_expense_form_data($document_id);
                if (!$form_data['success']) {
                    echo json_encode($form_data);
                    return;
                }

                $view_data = [
                    'document' => $form_data['document'],
                    'expense_data' => $form_data['expense_data'],
                    'payment_modes' => $form_data['payment_modes'],
                    'expense_categories' => $form_data['expense_categories']
                ];

                $form_html = $this->load->view('peppol/templates/expense_creation_form', $view_data, true);

                echo json_encode([
                    'success' => true,
                    'show_form' => true,
                    'form_html' => $form_html
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            return;
        }

        // Handle POST request for creating the expense
        try {
            $override_data = [
                'category' => $this->input->post('category'),
                'paymentmode' => $this->input->post('paymentmode'),
                'tax_rate' => $this->input->post('tax_rate'),
                'tax2_rate' => $this->input->post('tax2_rate', true)
            ];

            $result = $this->peppol_service->create_expense_from_document($document_id, $override_data);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}