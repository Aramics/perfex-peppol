<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/traits/Peppol_expense_trait.php';

class Peppol_service
{
    use Peppol_expense_trait;
    
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('peppol/peppol_model');

        // Load the advanced UBL generator if available
        $this->CI->load->library('peppol/peppol_ubl_generator');
        $this->CI->load->library('peppol/peppol_ubl_document_parser');
    }

    /**
     * Send invoice via PEPPOL
     */
    public function send_invoice($invoice_id)
    {
        try {
            // Prepare and validate data
            $data = $this->_prepare_document_data('invoice', $invoice_id);
            if (!$data['success']) {
                return $data;
            }

            // Generate UBL content with complete data (payments read from invoice object)
            $ubl_content = $this->generate_invoice_ubl($data['document'], $data['sender_info'], $data['receiver_info']);

            // Send via provider
            $result = $data['provider']->send('invoice', $ubl_content, $data['document_data'], $data['sender_info'], $data['receiver_info']);

            return $this->_handle_send_result('invoice', $invoice_id, $result, $data['provider']);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send credit note via PEPPOL
     */
    public function send_credit_note($credit_note_id)
    {
        try {
            // Prepare and validate data
            $data = $this->_prepare_document_data('credit_note', $credit_note_id);
            if (!$data['success']) {
                return $data;
            }

            // Generate UBL content
            $ubl_content = $this->generate_credit_note_ubl($data['document'], $data['sender_info'], $data['receiver_info']);

            // Send via provider
            $result = $data['provider']->send('credit_note', $ubl_content, $data['document_data'], $data['sender_info'], $data['receiver_info']);

            return $this->_handle_send_result('credit_note', $credit_note_id, $result, $data['provider']);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare document data for sending
     */
    private function _prepare_document_data($document_type, $document_id)
    {
        // Load appropriate model
        if ($document_type === 'invoice') {
            $this->CI->load->model('invoices_model');
            $document = $this->CI->invoices_model->get($document_id);
        } elseif ($document_type === 'credit_note') {
            $this->CI->load->model('credit_notes_model');
            $document = $this->CI->credit_notes_model->get($document_id);
        } else {
            return [
                'success' => false,
                'message' => 'Unsupported document type: ' . $document_type
            ];
        }

        if (!$document) {
            return [
                'success' => false,
                'message' => ucfirst($document_type) . ' not found'
            ];
        }

        $document->attachments = $this->prepare_attachments($document, $document_type);

        // Get client data
        $client = $this->get_client($document->clientid);
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Client not found'
            ];
        }

        // Prepare sender info (company/seller info)
        $sender_info = $this->prepare_sender_info();

        // Prepare receiver info (client/buyer info)
        $receiver_info = $this->prepare_receiver_info($client);

        // Get active provider
        $active_provider = get_option('peppol_active_provider');
        if (!$active_provider) {
            return [
                'success' => false,
                'message' => 'No PEPPOL provider configured'
            ];
        }

        // Get provider instance
        $providers = peppol_get_registered_providers();
        if (!isset($providers[$active_provider])) {
            return [
                'success' => false,
                'message' => 'Active provider not found: ' . $active_provider
            ];
        }

        return [
            'success' => true,
            'document' => $document,
            'client' => $client,
            'sender_info' => $sender_info,
            'receiver_info' => $receiver_info,
            'provider' => $providers[$active_provider],
            'document_data' => $document
        ];
    }

    /**
     * Handle send result and store PEPPOL metadata
     */
    private function _handle_send_result($document_type, $document_id, $result, $provider)
    {
        if ($result['success']) {
            // Store PEPPOL document metadata
            $peppol_data = [
                'document_type' => $document_type,
                'local_reference_id' => $document_id,
                'status' => 'sent',
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

    /**
     * Get client data
     */
    public function get_client($client_id)
    {
        $this->CI->load->model('clients_model');
        return $this->CI->clients_model->get($client_id);
    }

    /**
     * Prepare sender information
     */
    public function prepare_sender_info()
    {
        $company_identifier = get_option('peppol_company_identifier');
        $company_scheme = get_option('peppol_company_scheme');

        if (empty($company_identifier) || empty($company_scheme)) {
            throw new Exception('Company PEPPOL identifier not configured');
        }

        return [
            'name' => get_option('invoice_company_name') ?: get_option('companyname'),
            'identifier' => $company_identifier,
            'scheme' => $company_scheme,
            'vat' => get_option('company_vat'),
            'address' => [
                'street' => get_option('invoice_company_address') ?: get_option('company_address'),
                'city' => get_option('invoice_company_city') ?: get_option('company_city'),
                'state' => get_option('invoice_company_state') ?: get_option('company_state'),
                'postal_code' => get_option('invoice_company_postal_code') ?: get_option('company_postal_code'),
                'country_code' => get_option('invoice_company_country_code') ?: get_option('company_country_code')
            ],
            'contact' => [
                'phone' => get_option('invoice_company_phonenumber') ?: get_option('company_phonenumber'),
                'email' =>  get_option('company_email') ?: get_option('smtp_email')
            ]
        ];
    }

    /**
     * Prepare receiver information
     */
    public function prepare_receiver_info($client)
    {
        // Get PEPPOL identifier from custom fields
        $identifier = get_custom_field_value($client->userid, 'customers_peppol_identifier', 'customers');
        $scheme = get_custom_field_value($client->userid, 'customers_peppol_scheme', 'customers');

        if (empty($identifier) || empty($scheme)) {
            throw new Exception('Client PEPPOL identifier not configured for client: ' . $client->company);
        }

        return [
            'name' => $client->company,
            'identifier' => $identifier,
            'scheme' => $scheme,
            'vat' => $client->vat,
            'address' => [
                'street' => $client->billing_street ?: $client->address,
                'city' => $client->billing_city ?: $client->city,
                'state' => $client->billing_state ?: $client->state,
                'postal_code' => $client->billing_zip ?: $client->zip,
                'country_code' => $this->_get_country_code_from_id($client->billing_country ?: $client->country)
            ],
            'contact' => [
                'phone' => $client->phonenumber,
                'email' => $client->email
            ]
        ];
    }

    /**
     * Generate invoice UBL
     */
    public function generate_invoice_ubl($invoice, $sender_info, $receiver_info)
    {
        $invoice->attachements = $this->prepare_attachments($invoice, 'invoice');
        return $this->CI->peppol_ubl_generator->generate_invoice_ubl($invoice, $sender_info, $receiver_info);
    }

    /**
     * Generate credit note UBL
     */
    public function generate_credit_note_ubl($credit_note, $sender_info, $receiver_info)
    {
        $credit_note->attachements = $this->prepare_attachments($credit_note, 'credit_note');
        return $this->CI->peppol_ubl_generator->generate_credit_note_ubl($credit_note, $sender_info, $receiver_info);
    }

    /**
     * Get country code from country ID
     */
    private function _get_country_code_from_id($country_id)
    {
        if (!$country_id) {
            return '';
        }

        $this->CI->db->where('country_id', $country_id);
        $country = $this->CI->db->get(db_prefix() . 'countries')->row();

        return $country ? $country->iso2 : '';
    }

    /**
     * Get payment terms for different scenarios
     */
    public function get_payment_terms_options()
    {
        return [
            'partial' => _l('peppol_payment_terms_partial'),
            'paid' => _l('peppol_payment_terms_paid'),
            'refund' => _l('peppol_payment_terms_refund')
        ];
    }

    /**
     * Prepare attachment for ubl
     *
     * @param object $document
     * @param string $document_type
     * @return array The list of attachments.
     */
    public function prepare_attachments($document, $document_type)
    {
        if (!isset($document->attachments)) return $document;

        $attachments = [];
        foreach ($document->attachments as $key => $attachment) {
            if ($attachment['visible_to_customer'] == 1) {
                $link = base_url('download/file/sales_attachment/' . $attachment['attachment_key']);
                $attachment['external_link'] = empty($attachment['external_link']) ? $link : $attachment['external_link'];
                $attachments[] = $attachment;
            }
        }

        // Add link to the where pdf can be downloaded i.e view as customer.
        $document_number = $document_type == 'invoice' ? format_invoice_number($document->id) : format_credit_note_number($document->id);
        $attachments[] = [
            'description' => $document_number . ' PDF',
            'file_name' => $document_number . ' PDF',
            'external_link' => base_url('invoice/' . $document->id . '/' . $document->hash),
        ];

        return $attachments;
    }

    /**
     * Retrieve UBL document content from provider
     * 
     * @param int $peppol_document_id PEPPOL document ID
     * @return array Response with UBL content
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
     * Retrieve enriched PEPPOL document with optional UBL parsing and client data
     * 
     * This method fetches a PEPPOL document and optionally enriches it with:
     * - Provider UBL content and parsed data (attachments, structured content)
     * - Related local document data (invoice/credit note information)
     * - Client information (company details)
     * 
     * The method provides two modes of operation:
     * 1. Full enrichment (default): Includes UBL parsing and all related data
     * 2. Basic mode: Returns only the PEPPOL document record without UBL processing
     * 
     * When UBL parsing is enabled, the method will:
     * - Retrieve the original UBL XML from the provider
     * - Parse the UBL document to extract structured data including attachments
     * - Add the parsed data to the document object for easy access
     * 
     * @param int $document_id The PEPPOL document ID to retrieve
     * @param bool $include_ubl_data Whether to fetch and parse UBL data from provider
     *                               - true: Full document with UBL parsing and attachments
     *                               - false: Basic document record only
     * 
     * @return object|array Returns enriched document object on success, or error array on failure
     * 
     * Document object properties when successful:
     * - Basic PEPPOL document fields (id, status, provider_document_id, etc.)
     * - ubl_content: Raw UBL XML content (if $include_ubl_data = true)
     * - ubl_document: Parsed UBL data array including attachments (if $include_ubl_data = true)
     * - ubl_file_name: Generated filename for UBL download (if $include_ubl_data = true)
     * - client: Full client object with company details (if local_reference_id exists)
     * 
     * Error array format:
     * - success: false
     * - message: Error description
     * 
     * @throws Exception When UBL parsing fails or provider communication errors occur
     * 
     * @since 1.0.0
     * @see get_provider_ubl() For UBL retrieval from providers
     * @see Peppol_ubl_document_parser For UBL parsing functionality
     * 
     * @example
     * // Get full document with UBL data and attachments
     * $document = $this->peppol_service->get_enriched_document(123);
     * $attachments = $document->ubl_document['attachments'] ?? [];
     * 
     * // Get basic document only
     * $document = $this->peppol_service->get_enriched_document(123, false);
     */
    public function get_enriched_document($document_id, $include_ubl_data = true)
    {
        $document = null;
        if ($include_ubl_data) {
            $response = $this->get_provider_ubl($document_id);
            $document = $response['document'] ?? null;
            if (empty($document)) {
                return $response;
            }

            $document->ubl_content = $response['ubl_content'];
            $document->ubl_document = $this->CI->peppol_ubl_document_parser->parse($response['ubl_content']);
            $document->ubl_file_name = $response['filename'];
        } else {
            $document = $this->CI->peppol_model->get_peppol_document_by_id($document_id);
        }

        if (empty($document)) {
            return $document;
        }

        if (!empty($document->local_reference_id)) {
            // Fetch related data only if needed and document has local reference
            $this->CI->load->model(['invoices_model', 'credit_notes_model', 'clients_model']);

            $doc_data = $document->document_type === 'credit_note' ?
                $this->CI->credit_notes_model->get($document->local_reference_id)  :
                $this->CI->invoices_model->get($document->local_reference_id);

            if ($doc_data) {
                $client_id = $doc_data->clientid;
            }

            // Get client name if we have client_id
            if (isset($client_id) && $client_id) {
                $client = $this->CI->clients_model->get($client_id);
                if ($client) {
                    $document->client = $client;
                }
            }
        }

        return $document;
    }

    /**
     * Mark document response status
     * 
     * @param int $document_id PEPPOL document ID
     * @param string $status Response status
     * @param string $note Optional note
     * @param array $clarifications Optional clarifications array
     * @param string $effective_date Optional effective date
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
     * @return array Available clarifications structure
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