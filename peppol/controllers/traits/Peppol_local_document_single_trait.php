<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Local Document Single Operations Trait
 * 
 * Handles single local document operations including:
 * - Single document sending via PEPPOL
 * - UBL generation and downloads
 * - Provider UBL downloads
 */
trait Peppol_local_document_single_trait
{
    // ================================
    // SINGLE DOCUMENT SEND METHODS
    // ================================

    /**
     * Send single local document via PEPPOL (AJAX)
     * 
     * Sends an individual invoice or credit note through the PEPPOL network.
     * Validates staff permissions and delegates to the service layer.
     * 
     * @param int $document_id ID of the local document to send
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @return void Outputs JSON response with send result
     */
    public function send_ajax($document_id, $document_type = 'invoice')
    {
        $this->_handle_single_send($document_type, $document_id);
    }

    /**
     * Handle single document send operation
     * 
     * Internal method that processes the actual sending of a single document.
     * Validates permissions and calls the appropriate service method.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id ID of the document to send
     * @return void Outputs JSON response with operation result
     */
    private function _handle_single_send($document_type, $document_id)
    {
        if (!staff_can('create', 'peppol')) {
            return $this->json_output([
                'success' => false,
                'message' => _l('peppol_access_denied')
            ]);
        }

        $response = $this->peppol_service->send_document($document_type, $document_id);

        return $this->json_output($response);
    }


    /**
     * Generate and view UBL XML for a local document
     * 
     * Generates UBL XML content for any local document and displays it inline
     * in the browser. Works with both sent and unsent documents.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id ID of the local document
     * @return void Outputs XML content with appropriate headers
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
     * Generate and download UBL XML file for a local document
     * 
     * Generates UBL XML content and initiates download with proper filename.
     * Uses formatted document numbers for user-friendly filenames.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id ID of the local document
     * @return void Initiates file download
     */
    public function generate_download_ubl($document_type, $document_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        try {
            $ubl_content = $this->_generate_ubl_content($document_type, $document_id);
            $number = $document_type == 'invoice' ? format_invoice_number($document_id) : format_credit_note_number($document_id);

            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $number . '_ubl.xml"');
            header('Content-Length: ' . strlen($ubl_content));
            echo $ubl_content;
        } catch (Exception $e) {
            show_error('Error generating UBL: ' . $e->getMessage());
        }
    }

    /**
     * Download original UBL file from PEPPOL provider
     * 
     * Downloads the original UBL XML file as stored by the PEPPOL provider,
     * which may differ from our locally generated UBL due to provider processing.
     * 
     * @param int $document_id ID of the PEPPOL document record
     * @return void Initiates file download or shows error
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
     * Generate UBL XML content for a local document
     * 
     * Internal helper method that creates UBL XML content using the service layer.
     * Handles error checking and exception throwing for invalid content.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id ID of the local document
     * @return string Generated UBL XML content
     * @throws Exception If UBL generation fails
     */
    private function _generate_ubl_content($document_type, $document_id)
    {
        $ubl_content = $this->peppol_service->generate_document_ubl($document_type, $document_id);
        if (isset($ubl_content['message'])) {
            throw new \Exception($ubl_content['message'], 1);
        }
        return $ubl_content;
    }
}