<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Document Response Management Trait
 * 
 * Handles document response operations including:
 * - Document status updates and responses
 * - Clarification management
 * - Provider response communication
 * - Response data validation and storage
 * 
 * @package PEPPOL
 * @subpackage Libraries\Traits
 */
trait Peppol_document_response_trait
{
    /**
     * Mark document response status
     * 
     * Updates the status of a received PEPPOL document and sends the response
     * back through the PEPPOL network via the provider. Handles clarifications
     * and validation of response data.
     * 
     * @param int $document_id PEPPOL document ID
     * @param string $status Response status code (accept, reject, etc.)
     * @param string $note Optional response note
     * @param array $clarifications Optional clarifications array
     * @param string $effective_date Optional effective date for the response
     * @return array Response with success flag and message
     */
    public function mark_document_status($document_id, $status, $note = '', $clarifications = [], $effective_date = '')
    {
        $document = $this->CI->peppol_model->get_peppol_document_by_id($document_id);

        if (!$document) {
            return ['success' => false, 'message' => _l('peppol_document_not_found')];
        }

        // Only allow responses for received documents (those with received_at timestamp)
        if (empty($document->received_at)) {
            return ['success' => false, 'message' => _l('peppol_cannot_respond_to_document')];
        }

        // Get provider for response sending
        $providers = peppol_get_registered_providers();
        if (!isset($providers[$document->provider])) {
            return ['success' => false, 'message' => _l('peppol_provider_not_found')];
        }

        $provider = $providers[$document->provider];

        // Require provider to support invoice responses
        if (!method_exists($provider, 'send_document_response')) {
            return [
                'success' => false,
                'message' => sprintf(_l('peppol_provider_no_response_support'), $document->provider)
            ];
        }

        // Prepare response payload
        $response_data = [
            'invoiceTransmissionId' => $document->provider_document_id,
            'responseCode' => $status,
            'effectiveDate' => !empty($effective_date) ? $effective_date : date('c'),
            'note' => $note
        ];

        // Add clarifications if provided
        if (!empty($clarifications) && is_array($clarifications)) {
            $response_data['invoiceClarifications'] = $clarifications;
        }

        // Send response via provider
        try {
            $result = $provider->send_document_response($response_data, $document->document_type);

            if ($result['success']) {
                // Prepare data to store locally
                $update_data = [
                    'response_status' => $status,
                    'response_note' => $note,
                    'responded_at' => date('Y-m-d H:i:s'),
                    'responded_by' => get_staff_user_id()
                ];

                // Store clarifications if provided
                if (!empty($clarifications)) {
                    $update_data['response_clarifications'] = json_encode($clarifications);
                }

                // Update document status locally only after successful provider response
                $this->CI->db->where('id', $document_id);
                $this->CI->db->update(db_prefix() . 'peppol_documents', $update_data);

                return [
                    'success' => true,
                    'message' => _l('peppol_response_sent_successfully'),
                    'response_data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => _l('peppol_response_send_failed') . ': ' . ($result['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending response: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available clarification types and codes
     * 
     * Returns standardized clarification types, reason codes, and action codes
     * available for PEPPOL document responses. These codes are used to provide
     * structured feedback about document issues or status changes.
     * 
     * @return array Available clarifications structure with types, reason codes, and action codes
     */
    public function get_available_clarifications()
    {
        return [
            'types' => [
                'OPStatusReason' => _l('peppol_clarification_type_status_reason'),
                'OPStatusAction' => _l('peppol_clarification_type_status_action')
            ],
            'reason_codes' => [
                'NON' => _l('peppol_clarification_reason_non'),
                'REF' => _l('peppol_clarification_reason_ref'),
                'LEG' => _l('peppol_clarification_reason_leg'),
                'REC' => _l('peppol_clarification_reason_rec'),
                'QUA' => _l('peppol_clarification_reason_qua'),
                'DEL' => _l('peppol_clarification_reason_del'),
                'PRI' => _l('peppol_clarification_reason_pri'),
                'QTY' => _l('peppol_clarification_reason_qty'),
                'ITM' => _l('peppol_clarification_reason_itm'),
                'PAY' => _l('peppol_clarification_reason_pay'),
                'UNR' => _l('peppol_clarification_reason_unr'),
                'FIN' => _l('peppol_clarification_reason_fin'),
                'PPD' => _l('peppol_clarification_reason_ppd'),
                'OTH' => _l('peppol_clarification_reason_oth')
            ],
            'action_codes' => [
                'NOA' => _l('peppol_clarification_action_noa'),
                'PIN' => _l('peppol_clarification_action_pin'),
                'NIN' => _l('peppol_clarification_action_nin'),
                'CNF' => _l('peppol_clarification_action_cnf'),
                'CNP' => _l('peppol_clarification_action_cnp'),
                'CNA' => _l('peppol_clarification_action_cna'),
                'OTH' => _l('peppol_clarification_action_oth')
            ]
        ];
    }
}