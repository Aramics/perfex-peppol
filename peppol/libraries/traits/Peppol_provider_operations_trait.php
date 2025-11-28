<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Operations Trait
 * 
 * Handles PEPPOL provider interactions including:
 * - Document sending through providers
 * - Send result processing and storage
 * - Webhook notification processing
 * - Provider communication management
 * 
 * @package PEPPOL
 * @subpackage Libraries\Traits
 */
trait Peppol_provider_operations_trait
{
    /**
     * Send document through PEPPOL provider
     * 
     * Orchestrates the complete document sending process including data preparation,
     * UBL generation, provider communication, and result handling.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id Local document ID
     * @return array Response with success flag and message
     */
    public function send_document($document_type, $document_id)
    {
        try {
            // Prepare and validate data
            $data = $this->prepare_document_data($document_type, $document_id);
            if (!$data['success']) {
                return $data;
            }

            // Generate UBL content with complete data (payments read from invoice object)
            $ubl_content = $this->generate_document_ubl($document_type, $document_id, $data);

            // Send via provider
            $result = $data['provider']->send('invoice', $ubl_content, $data['document_data'], $data['sender_info'], $data['receiver_info']);

            return $this->_handle_send_result($document_type, $document_id, $result, $data['provider']);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process PEPPOL webhook notifications
     * 
     * Triggers the active provider's webhook processing method to handle
     * incoming notifications about document status changes or new documents.
     * 
     * @return array Processing results from the provider
     */
    public function process_notifications()
    {
        // Get notification lookup time from settings
        $lookup_hours = (float)(get_option('peppol_notification_lookup_hours') ?: 72);
        $total_minutes = $lookup_hours * 60;
        
        // Prepare filter parameters
        $filter = [
            'startDateTime' => date('c', strtotime("-{$total_minutes} minutes")),
            'pageSize' => 100
        ];
        
        return peppol_get_active_provider()->webhook($filter);
    }

    /**
     * Retrieve UBL document content from provider
     * 
     * Fetches the original UBL XML content from the PEPPOL provider's storage.
     * This content may differ from locally generated UBL due to provider processing.
     * 
     * @param int $peppol_document_id PEPPOL document ID
     * @return array Response with UBL content and metadata
     */
    public function get_provider_ubl($peppol_document_id)
    {
        try {
            // Get the PEPPOL document with decoded metadata
            $peppol_document = $this->CI->peppol_model->get_peppol_document_by_id($peppol_document_id);

            if (!$peppol_document) {
                return [
                    'success' => false,
                    'message' => _l('peppol_document_not_found')
                ];
            }

            // Check if document has provider document ID
            if (empty($peppol_document->provider_document_id)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_provider_ubl_not_available')
                ];
            }

            // Get registered providers
            $providers = peppol_get_registered_providers();

            if (!isset($providers[$peppol_document->provider])) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_provider_not_found_error'), $peppol_document->provider)
                ];
            }

            $provider_instance = $providers[$peppol_document->provider];

            // Check if provider supports UBL retrieval
            if (!method_exists($provider_instance, 'get_document_ubl')) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_provider_no_ubl_support'), $peppol_document->provider)
                ];
            }

            // Retrieve UBL from provider - pass the document object directly
            $ubl_result = $provider_instance->get_document_ubl($peppol_document);

            if (!$ubl_result['success']) {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_ubl_retrieve_failed'), $ubl_result['message'])
                ];
            }

            // Get UBL content from response (different providers may use different field names)
            $ubl_content = $ubl_result['ubl_content'] ?? $ubl_result['ubl_xml'] ?? $ubl_result['data'] ?? '';

            if (empty($ubl_content)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_ubl_content_empty')
                ];
            }

            return [
                'success' => true,
                'ubl_content' => $ubl_content,
                'document' => $peppol_document,
                'filename' => $peppol_document->document_type . '_' . ($peppol_document->local_reference_id ?? 'unknown') . '_provider_ubl.xml'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(_l('peppol_ubl_retrieve_error'), $e->getMessage())
            ];
        }
    }

    /**
     * Handle send result and store PEPPOL metadata
     * 
     * Processes the response from provider sending operation and stores
     * the result metadata in the local PEPPOL documents table.
     * 
     * @param string $document_type Document type that was sent
     * @param int $document_id Local document ID
     * @param array $result Provider send result
     * @param object $provider Provider instance that handled the send
     * @return array Formatted response with success status
     * @private
     */
    private function _handle_send_result($document_type, $document_id, $result, $provider)
    {
        if ($result['success']) {
            // Store PEPPOL document metadata
            $peppol_data = [
                'document_type' => $document_type,
                'local_reference_id' => $document_id,
                'status' => 'pending', // Will be updated in notification
                'provider' => $provider->get_id(),
                'provider_document_id' => $result['document_id'] ?? null,
                'provider_metadata' => json_encode($result['metadata'] ?? []),
                'sent_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->CI->peppol_model->create_peppol_document($peppol_data);

            return [
                'success' => true,
                'message' => ucfirst($document_type) . ' sent successfully via PEPPOL'
            ];
        }

        return $result;
    }
}