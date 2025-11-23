<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('peppol/peppol_model');

        // Load the advanced UBL generator if available
        $this->CI->load->library('peppol/peppol_ubl_generator');
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
        $invoice = $this->prepare_attachments($invoice, 'invoice');
        return $this->CI->peppol_ubl_generator->generate_invoice_ubl($invoice, $sender_info, $receiver_info);
    }

    /**
     * Generate credit note UBL
     */
    public function generate_credit_note_ubl($credit_note, $sender_info, $receiver_info)
    {
        $credit_note = $this->prepare_attachments($credit_note, 'credit_note');
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
}