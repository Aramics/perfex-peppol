<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Document Management Trait
 * 
 * Handles management of PEPPOL documents (both sent and received) including:
 * - Document viewing and details display
 * - Document status management and responses
 * - Expense creation from received documents
 * - Document listing, filtering and statistics
 * 
 * @package PEPPOL
 * @subpackage Controllers\Traits
 */
trait Peppol_document_management_trait
{
    /**
     * PEPPOL documents management page
     * 
     * Main dashboard for viewing and managing all PEPPOL documents.
     * Displays statistics, filtering options, and document listings.
     * Handles both AJAX requests for table data and regular page loads.
     * 
     * @param string $table Legacy parameter (unused)
     * @return void Loads view or returns AJAX table data
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
     * View PEPPOL document details (Sidewise view)
     * 
     * Displays comprehensive document information in a dedicated page view,
     * similar to sales document views. Loads document details, attachments,
     * and provides action buttons for status updates and expense creation.
     * 
     * @param int $id PEPPOL document ID
     * @return void Loads document view page
     */
    public function view_document($id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $document = $this->peppol_service->get_enriched_document($id);
        if (empty($document->id)) {
            show_error($document['message'] ?? _l('peppol_document_not_found'));
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

        // Prepare view data
        $data = [
            'title' => sprintf(
                '%s - %s',
                ucfirst(str_replace('_', ' ', $document->document_type)),
                !empty($document->local_reference_id) ? '#' . $document->local_reference_id : $document->provider_document_id
            ),
            'document' => $document,
            'metadata' => $metadata,
            'attachments' => $attachments,
            'clarifications' => $clarifications
        ];

        $this->load->view('peppol/admin/documents/view', $data);
    }

    /**
     * View PEPPOL document details (AJAX - Legacy for modal)
     * 
     * Loads comprehensive document information including metadata, 
     * attachments, and available response options. Returns formatted
     * HTML content for modal display. Kept for backward compatibility.
     * 
     * @param int $id PEPPOL document ID
     * @return void Outputs JSON response with document details
     */
    public function view_document_modal($id)
    {
        if (!staff_can('view', 'peppol')) {
            return $this->json_output(['success' => false, 'message' => _l('access_denied')]);
        }

        $document = $this->peppol_service->get_enriched_document($id);

        if (empty($document->id)) {
            return $this->json_output(['success' => false, 'message' => _l('peppol_document_not_found')]);
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

        return $this->json_output([
            'success' => true,
            'content' => $content,
            'clarifications' => $clarifications
        ]);
    }

    /**
     * Mark document response status (AJAX)
     * 
     * Updates the status of a PEPPOL document (e.g., accept, reject, paid).
     * Processes clarifications and sends response back to the sender
     * through the PEPPOL network.
     * 
     * @return void Outputs JSON response with operation result
     */
    public function mark_document_status()
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            return $this->json_output(['success' => false, 'message' => _l('access_denied')]);
        }

        $document_id = $this->input->post('document_id');
        $status = $this->input->post('status');
        $note = $this->input->post('note', true);
        $effective_date = $this->input->post('effective_date', true);
        $clarifications = $this->input->post('clarifications');

        if (!$document_id || !$status) {
            return $this->json_output(['success' => false, 'message' => _l('peppol_invalid_request_data')]);
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
            return $this->json_output($result);
        } catch (Exception $e) {
            return $this->json_output([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get status update form for document (AJAX)
     * 
     * Returns the status update form HTML for modal display.
     * Includes clarifications and response options for the document.
     * 
     * @param int $id PEPPOL document ID
     * @return void Outputs JSON response with form HTML
     */
    public function get_status_update_form($id)
    {
        if (!staff_can('create', 'peppol')) {
            return $this->json_output(['success' => false, 'message' => _l('access_denied')]);
        }

        $document = $this->peppol_service->get_enriched_document($id);

        if (empty($document->id)) {
            return $this->json_output(['success' => false, 'message' => _l('peppol_document_not_found')]);
        }

        // Only allow status updates for received documents
        if (empty($document->received_at)) {
            return $this->json_output(['success' => false, 'message' => _l('peppol_cannot_update_status_outbound')]);
        }

        try {
            // Get clarifications data for the form
            $clarifications = $this->peppol_service->get_available_clarifications();

            // Prepare view data
            $view_data = [
                'document' => $document,
                'clarifications' => $clarifications
            ];

            // Render the status update form
            $content = $this->load->view('peppol/templates/status_update_form', $view_data, true);

            return $this->json_output([
                'success' => true,
                'content' => $content
            ]);
        } catch (Exception $e) {
            return $this->json_output([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get available clarifications for document responses (AJAX)
     * 
     * Returns standardized clarification codes and types available
     * for document responses. Primarily used as fallback since
     * clarifications are typically cached on the frontend.
     * 
     * @return void Outputs JSON response with clarification data
     */
    public function get_clarifications()
    {
        if (!staff_can('view', 'peppol')) {
            return $this->json_output(['success' => false, 'message' => _l('access_denied')]);
        }

        try {
            $clarifications = $this->peppol_service->get_available_clarifications();
            return $this->json_output([
                'success' => true,
                'data' => $clarifications
            ]);
        } catch (Exception $e) {
            return $this->json_output([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create expense from received PEPPOL document (AJAX)
     * 
     * Converts a received PEPPOL invoice or credit note into a local expense.
     * Handles both form display (GET) and expense creation (POST).
     * Automatically extracts tax rates, payment modes, and vendor information.
     * 
     * @param int $document_id PEPPOL document ID
     * @return void Outputs JSON response with form HTML or creation result
     */
    public function create_expense($document_id)
    {
        if (!staff_can('create', 'expenses')) {
            return $this->json_output(['success' => false, 'message' => _l('access_denied')]);
        }

        if (!$document_id) {
            return $this->json_output(['success' => false, 'message' => _l('peppol_invalid_request_data')]);
        }

        // If it's a GET request, return the expense creation form
        if (!$this->input->post()) {
            try {
                $form_data = $this->peppol_service->prepare_expense_form_data($document_id);
                if (!$form_data['success']) {
                    return $this->json_output($form_data);
                }

                $view_data = [
                    'document' => $form_data['document'],
                    'expense_data' => $form_data['expense_data'],
                    'payment_modes' => $form_data['payment_modes'],
                    'expense_categories' => $form_data['expense_categories']
                ];

                $form_html = $this->load->view('peppol/templates/expense_creation_form', $view_data, true);

                return $this->json_output([
                    'success' => true,
                    'show_form' => true,
                    'form_html' => $form_html
                ]);
            } catch (Exception $e) {
                return $this->json_output([
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
            return $this->json_output($result);
        } catch (Exception $e) {
            return $this->json_output([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}