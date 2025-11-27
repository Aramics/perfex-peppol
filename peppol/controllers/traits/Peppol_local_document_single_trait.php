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
     * Send single document via PEPPOL (AJAX)
     */
    public function send_ajax($document_id, $document_type = 'invoice')
    {
        $this->_handle_single_send($document_type, $document_id);
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

        $response = $this->peppol_service->send_document($document_type, $document_id);

        echo json_encode($response);
    }

    // ================================
    // UBL GENERATION METHODS
    // ================================

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
     * Helper method to generate UBL content for any document type
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