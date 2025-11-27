<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load all trait classes
require_once __DIR__ . '/traits/Peppol_expense_trait.php';
require_once __DIR__ . '/traits/Peppol_provider_operations_trait.php';
require_once __DIR__ . '/traits/Peppol_document_response_trait.php';

class Peppol_service
{
    // Use traits for organized functionality
    use Peppol_expense_trait;
    use Peppol_provider_operations_trait;
    use Peppol_document_response_trait;

    const TYPE_INVOICE = 'invoice';
    const TYPE_CREDIT_NOTE = 'credit_note';

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
     * Generate UBL XML for local document
     * 
     * Creates UBL XML content for invoices or credit notes using the configured
     * UBL generator. Handles data preparation and validation.
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id Local document ID
     * @param array $data Optional pre-prepared document data
     * @return string|array UBL XML content or error array
     */
    public function generate_document_ubl($document_type, $document_id, $data = [])
    {
        // Prepare and validate data
        $data = isset($data['document']) && isset($data['sender_info']) && isset($data['sender_info']) ? $data : $this->prepare_document_data($document_type, $document_id);
        if (!$data['success']) {
            return $data;
        }

        // Generate UBL content with complete data (payments read from invoice object)
        $method = $document_type == self::TYPE_CREDIT_NOTE ? 'generate_credit_note_ubl' : 'generate_invoice_ubl';
        $ubl_content = $this->CI->peppol_ubl_generator->{$method}($data['document'], $data['sender_info'], $data['receiver_info']);
        return $ubl_content;
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

            $doc_data = $document->document_type === self::TYPE_CREDIT_NOTE ?
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
     * Get client data with contact information
     * 
     * Retrieves client data ensuring VAT information is preserved and
     * includes primary contact information for PEPPOL communications.
     * 
     * @param int $client_id Client ID
     * @return object|null Client object with primary contact or null if not found
     */
    public function get_client($client_id)
    {
        $this->CI->load->model('clients_model');
        // We do no want to use ->get($client_id) to ensure vat is not ever removed.
        $clients = $this->CI->clients_model->get('', [db_prefix() . 'clients.userid' => $client_id]);
        $client = isset($clients[0]) ? (object)$clients[0] : null;
        if (!$client) return;

        $primary_contacts = $this->CI->clients_model->get_contacts($client_id, ['active' => 1, 'is_primary' => 1]);
        $client->primary_contact = isset($primary_contacts[0]) ? (object)$primary_contacts[0] : null;
        return $client;
    }

    /**
     * Prepare document data for sending
     * 
     * Orchestrates the preparation of all data needed for PEPPOL document sending:
     * - Loads and validates the document (invoice/credit note)
     * - Retrieves client information
     * - Prepares sender and receiver information
     * - Validates active provider
     * - Processes document attachments
     * 
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @param int $document_id Local document ID
     * @return array Prepared data array or error response
     */
    public function prepare_document_data($document_type, $document_id)
    {
        // Load appropriate model
        if ($document_type === self::TYPE_INVOICE) {
            $this->CI->load->model('invoices_model');
            $document = $this->CI->invoices_model->get($document_id);
        } elseif ($document_type === self::TYPE_CREDIT_NOTE) {
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
        $active_provider = peppol_get_active_provider();
        if (!$active_provider) {
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
            'provider' => $active_provider,
            'document_data' => $document
        ];
    }

    /**
     * Prepare attachment for UBL document
     *
     * Processes document attachments and adds external links for customer-visible
     * attachments. Also adds public view link for invoices.
     *
     * @param object $document The document object with attachments
     * @param string $document_type Document type ('invoice' or 'credit_note')
     * @return array The processed list of attachments
     */
    public function prepare_attachments($document, $document_type)
    {
        if (!isset($document->attachments)) return $document;

        $attachments = [];
        foreach ($document->attachments as $key => $attachment) {

            if ($attachment['visible_to_customer'] == 1) {
                $link = base_url('download/file/sales_attachment/' . ($attachment['attachment_key'] ?: random_string()));
                $attachment['external_link'] = empty($attachment['external_link']) ? $link : $attachment['external_link'];
                $attachments[] = $attachment;
            }
        }

        // Add link to the where pdf can be downloaded i.e view as customer.
        if ($document_type == self::TYPE_INVOICE) { // public view of the invoice on the system
            $document_number =  format_invoice_number($document->id);
            $attachments[] = [
                'attachment_key' => $document->hash,
                'description' => $document_number,
                'file_name' => $document_number,
                'external_link' => base_url('invoice/' . $document->id . '/' . $document->hash),
            ];
        }

        return $attachments;
    }

    /**
     * Prepare sender information (company data)
     * 
     * Collects company information from system settings to use as sender
     * data in PEPPOL documents. Validates required PEPPOL identifiers.
     * 
     * @return array Formatted sender information
     * @throws Exception If PEPPOL company identifiers are not configured
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
     * Prepare receiver information (client data)
     * 
     * Formats client information for use as receiver data in PEPPOL documents.
     * Validates required PEPPOL identifiers from custom fields.
     * 
     * @param object $client Client object with contact information
     * @return array Formatted receiver information
     * @throws Exception If client PEPPOL identifiers are not configured
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
                'phone' => $client->phonenumber ?: ($client->primary_contact->phonenumber ?? ''),
                'email' => $client->primary_contact->email ?? ''
            ]
        ];
    }

    /**
     * Get country ISO2 code from country ID
     * 
     * Resolves country ID to ISO2 country code for PEPPOL addressing.
     * 
     * @param int $country_id Country ID from system countries table
     * @return string ISO2 country code or empty string if not found
     * @private
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
}