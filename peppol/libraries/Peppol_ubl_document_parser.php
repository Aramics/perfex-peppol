<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL UBL Document Parser
 * 
 * Handles parsing UBL XML documents and converting them to Perfex CRM documents
 * Supports both invoices and credit notes with proper client and item management
 */
class Peppol_ubl_document_parser
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('clients_model');
        $this->CI->load->model('invoices_model');
        $this->CI->load->model('credit_notes_model');
    }

    /**
     * Parse UBL XML and create Perfex document
     * 
     * @param string $ubl_xml The UBL XML content
     * @param string $external_document_id External document ID for reference
     * @param array $metadata Additional metadata
     * @return array Result with success status and created document info
     */
    public function parse_and_create($ubl_xml, $external_document_id, $metadata = [])
    {
        try {
            // Validate UBL reader availability
            if (!class_exists('Einvoicing\UblReader')) {
                throw new Exception('UBL reader library not available');
            }

            // Parse UBL XML
            $reader = new \Einvoicing\UblReader($ubl_xml);

            // Detect document type
            $is_credit_note = $this->_detect_credit_note($ubl_xml);
            $document_type = $is_credit_note ? 'credit_note' : 'invoice';

            // Parse document data from UBL
            $parsed_data = $this->_parse_ubl_data($reader, $document_type, $external_document_id);
            
            // Get or create client
            $client_result = $this->_get_or_create_client($parsed_data);
            if (!$client_result['success']) {
                return $client_result;
            }

            // Create the document
            $document_result = $this->_create_document($parsed_data, $client_result['client_id'], $document_type);
            if (!$document_result['success']) {
                return $document_result;
            }

            // Store metadata if provided
            if (!empty($metadata)) {
                $this->_store_document_metadata($document_result['document_id'], $document_type, $external_document_id, $metadata);
            }

            return [
                'success' => true,
                'document_type' => $document_type,
                'document_id' => $document_result['document_id'],
                'client_id' => $client_result['client_id'],
                'message' => ucfirst($document_type) . ' created successfully from UBL'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error parsing UBL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Detect if UBL is a credit note
     */
    private function _detect_credit_note($ubl_xml)
    {
        return stripos($ubl_xml, '<CreditNote') !== false;
    }

    /**
     * Parse UBL data into structured format
     */
    private function _parse_ubl_data($reader, $document_type, $external_document_id)
    {
        $data = [
            'external_id' => $external_document_id,
            'document_type' => $document_type,
            'document_number' => $this->_safe_get_value($reader, 'id'),
            'issue_date' => $this->_safe_get_date($reader, 'issueDate'),
            'due_date' => $this->_safe_get_date($reader, 'dueDate'),
            'currency' => $this->_get_currency_id($this->_safe_get_value($reader, 'documentCurrencyCode')),
            'notes' => $this->_extract_notes($reader),
            'payment_terms' => $this->_safe_get_value($reader, 'paymentTerms'),
            'billing_reference' => $this->_safe_get_value($reader, 'billingReference')
        ];

        // Parse buyer/seller information
        $data['buyer'] = $this->_parse_party_info($reader, 'buyer');
        $data['seller'] = $this->_parse_party_info($reader, 'seller');

        // Parse line items
        $data['items'] = $this->_parse_line_items($reader, $document_type);

        // Calculate totals
        $data['totals'] = $this->_parse_totals($reader);

        return $data;
    }

    /**
     * Parse party (buyer/seller) information from UBL
     */
    private function _parse_party_info($reader, $party_type)
    {
        $prefix = $party_type; // 'buyer' or 'seller'

        return [
            'name' => $this->_safe_get_value($reader, $prefix . 'Name'),
            'identifier' => $this->_safe_get_value($reader, $prefix . 'Id'),
            'scheme' => $this->_safe_get_value($reader, $prefix . 'IdScheme'),
            'vat_number' => $this->_safe_get_value($reader, $prefix . 'CompanyId'),
            'email' => $this->_safe_get_value($reader, $prefix . 'ElectronicMail'),
            'address' => $this->_safe_get_value($reader, $prefix . 'Address'),
            'city' => $this->_safe_get_value($reader, $prefix . 'City'),
            'postal_code' => $this->_safe_get_value($reader, $prefix . 'PostalCode'),
            'country_code' => $this->_safe_get_value($reader, $prefix . 'CountryCode'),
            'telephone' => $this->_safe_get_value($reader, $prefix . 'Telephone'),
            'website' => $this->_safe_get_value($reader, $prefix . 'Website')
        ];
    }

    /**
     * Parse line items from UBL
     */
    private function _parse_line_items($reader, $document_type)
    {
        $items = [];

        try {
            // Get line items based on document type
            $lines = $document_type === 'credit_note'
                ? $reader->getCreditNoteLines()
                : $reader->getInvoiceLines();

            $order = 1;
            foreach ($lines as $line) {
                $items[] = [
                    'description' => $this->_safe_get_line_value($line, 'getNote') ?: $this->_safe_get_line_value($line, 'getDescription') ?: 'Item',
                    'long_description' => $this->_safe_get_line_value($line, 'getDescription') ?: '',
                    'qty' => $document_type === 'credit_note'
                        ? ($this->_safe_get_line_value($line, 'getCreditedQuantity') ?: 1)
                        : ($this->_safe_get_line_value($line, 'getInvoicedQuantity') ?: 1),
                    'rate' => $this->_safe_get_line_value($line, 'getPrice') ?: 0,
                    'order' => $order++,
                    'taxname' => [] // Tax handling can be enhanced later
                ];
            }
        } catch (Exception $e) {
            // If line parsing fails, create a single item with totals
            $items = [[
                'description' => 'UBL Document Item',
                'long_description' => 'Item imported from UBL document',
                'qty' => 1,
                'rate' => 0,
                'order' => 1,
                'taxname' => []
            ]];
        }

        return $items;
    }

    /**
     * Parse monetary totals from UBL
     */
    private function _parse_totals($reader)
    {
        return [
            'subtotal' => $this->_safe_get_value($reader, 'lineExtensionAmount') ?: 0,
            'tax_amount' => $this->_safe_get_value($reader, 'taxExclusiveAmount') ?: 0,
            'total' => $this->_safe_get_value($reader, 'payableAmount') ?: 0
        ];
    }

    /**
     * Get or create client from parsed buyer information
     */
    private function _get_or_create_client($parsed_data)
    {
        $buyer = $parsed_data['buyer'];

        // Try to find existing client by VAT number
        if (!empty($buyer['vat_number'])) {
            $existing_client = $this->CI->clients_model->get('', ['vat' => $buyer['vat_number']]);
            if ($existing_client && count($existing_client) > 0) {
                return [
                    'success' => true,
                    'client_id' => $existing_client[0]['userid'],
                    'was_existing' => true
                ];
            }
        }

        // Try to find by PEPPOL identifier if available
        if (!empty($buyer['identifier'])) {
            $client_id = $this->_find_client_by_peppol_identifier($buyer['identifier']);
            if ($client_id) {
                return [
                    'success' => true,
                    'client_id' => $client_id,
                    'was_existing' => true
                ];
            }
        }

        // Create new client
        return $this->_create_new_client($buyer);
    }

    /**
     * Find client by PEPPOL identifier custom field
     */
    private function _find_client_by_peppol_identifier($identifier)
    {
        $this->CI->db->select('c.userid')
            ->from(db_prefix() . 'clients c')
            ->join(db_prefix() . 'customfieldsvalues cfv', 'c.userid = cfv.relid')
            ->join(db_prefix() . 'customfields cf', 'cfv.fieldid = cf.id')
            ->where('cf.slug', 'customers_peppol_identifier')
            ->where('cfv.value', $identifier);

        $result = $this->CI->db->get()->row();
        return $result ? $result->userid : null;
    }

    /**
     * Create new client from buyer information
     */
    private function _create_new_client($buyer)
    {
        $client_data = [
            'company' => $buyer['name'] ?: 'Unknown Client',
            'vat' => $buyer['vat_number'] ?: '',
            'address' => $buyer['address'] ?: '',
            'city' => $buyer['city'] ?: '',
            'zip' => $buyer['postal_code'] ?: '',
            'country' => $this->_get_country_id_from_code($buyer['country_code']),
            'phonenumber' => $buyer['telephone'] ?: '',
            'website' => $buyer['website'] ?: '',
            'default_currency' => get_base_currency()->id,
            'show_primary_contact' => 1
        ];

        // Determine if we should create a contact (only if email is provided)
        $with_contact = !empty($buyer['email']);

        if ($with_contact) {
            // Add contact data to client data following Perfex pattern
            $client_data['firstname'] = $buyer['name'] ?: 'Contact';
            $client_data['lastname'] = '';
            $client_data['email'] = $buyer['email'];
            $client_data['password'] = random_string();
            $client_data['is_primary'] = 1;
        }

        $client_id = $this->CI->clients_model->add($client_data, $with_contact);

        if ($client_id) {
            // Store PEPPOL identifier as custom field if available
            if (!empty($buyer['identifier'])) {
                $this->_store_client_peppol_identifier($client_id, $buyer['identifier'], $buyer['scheme']);
            }

            return [
                'success' => true,
                'client_id' => $client_id,
                'was_existing' => false
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to create client'
        ];
    }

    /**
     * Create document (invoice or credit note) in Perfex
     */
    private function _create_document($parsed_data, $client_id, $document_type)
    {
        $base_data = [
            'clientid' => $client_id,
            'date' => $parsed_data['issue_date'] ?: date('Y-m-d'),
            'currency' => $parsed_data['currency'],
            'newitems' => $parsed_data['items'],
            'subtotal' => $parsed_data['totals']['subtotal'],
            'total' => $parsed_data['totals']['total'],
            'adminnote' => 'Created from PEPPOL UBL document: ' . $parsed_data['external_id']
        ];

        // Set external reference if available
        if (!empty($parsed_data['document_number'])) {
            $base_data['reference_no'] = $parsed_data['document_number'];
        }

        if ($document_type === 'credit_note') {
            $document_data = array_merge($base_data, [
                'status' => 1, // Open status
            ]);

            // Add billing reference and notes
            if (!empty($parsed_data['billing_reference'])) {
                $document_data['clientnote'] = 'Reference to invoice: ' . $parsed_data['billing_reference'];
            } elseif (!empty($parsed_data['notes'])) {
                $document_data['clientnote'] = $parsed_data['notes'];
            }

            $document_id = $this->CI->credit_notes_model->add($document_data);
        } else {
            $document_data = array_merge($base_data, [
                'duedate' => $parsed_data['due_date'] ?: date('Y-m-d', strtotime('+30 days')),
                'status' => Invoices_model::STATUS_UNPAID,
                'clientnote' => $parsed_data['notes'] ?: '',
                'terms' => $parsed_data['payment_terms'] ?: ''
            ]);

            $document_id = $this->CI->invoices_model->add($document_data);
        }

        if ($document_id) {
            return [
                'success' => true,
                'document_id' => $document_id
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to create ' . $document_type
        ];
    }

    /**
     * Store document metadata for tracking
     */
    private function _store_document_metadata($document_id, $document_type, $external_id, $metadata)
    {
        $this->CI->load->model('peppol/peppol_model');

        $peppol_data = [
            'document_type' => $document_type,
            'document_id' => $document_id,
            'status' => 'received',
            'provider' => $metadata['provider'] ?? 'unknown',
            'provider_document_id' => $external_id,
            'provider_metadata' => json_encode($metadata),
            'received_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->CI->peppol_model->create_peppol_document($peppol_data);
    }

    /**
     * Store client PEPPOL identifier as custom field
     */
    private function _store_client_peppol_identifier($client_id, $identifier, $scheme = null)
    {
        // Store PEPPOL identifier
        $this->_store_custom_field_value($client_id, 'customers_peppol_identifier', $identifier);

        // Store PEPPOL scheme if provided
        if ($scheme) {
            $this->_store_custom_field_value($client_id, 'customers_peppol_scheme', $scheme);
        }
    }

    /**
     * Store custom field value for client
     */
    private function _store_custom_field_value($client_id, $field_slug, $value)
    {
        $this->CI->db->where('fieldto', 'customers');
        $this->CI->db->where('slug', $field_slug);
        $custom_field = $this->CI->db->get(db_prefix() . 'customfields')->row();

        if ($custom_field) {
            $this->CI->db->insert(db_prefix() . 'customfieldsvalues', [
                'relid' => $client_id,
                'fieldid' => $custom_field->id,
                'value' => $value
            ]);
        }
    }

    /**
     * Get currency ID from currency code
     */
    private function _get_currency_id($currency_code)
    {
        if (!$currency_code) {
            return get_base_currency()->id;
        }

        $this->CI->db->where('name', $currency_code);
        $currency = $this->CI->db->get(db_prefix() . 'currencies')->row();

        return $currency ? $currency->id : get_base_currency()->id;
    }

    /**
     * Get country ID from ISO country code
     */
    private function _get_country_id_from_code($country_code)
    {
        if (!$country_code) {
            return 0;
        }

        $this->CI->db->where('iso2', strtoupper($country_code));
        $country = $this->CI->db->get(db_prefix() . 'countries')->row();

        return $country ? $country->country_id : 0;
    }

    /**
     * Extract notes from UBL reader
     */
    private function _extract_notes($reader)
    {
        try {
            $notes = [];
            if (method_exists($reader, 'getNotes')) {
                foreach ($reader->getNotes() as $note) {
                    $notes[] = $note;
                }
            }
            return implode("\n", $notes);
        } catch (Exception $e) {
            return '';
        }
    }


    /**
     * Safely get value from UBL reader
     */
    private function _safe_get_value($reader, $method)
    {
        try {
            if (method_exists($reader, 'getValue')) {
                return $reader->getValue($method);
            } elseif (method_exists($reader, $method)) {
                return $reader->$method();
            }
        } catch (Exception $e) {
            // Silently handle missing values
        }
        return null;
    }

    /**
     * Safely get date value from UBL reader
     */
    private function _safe_get_date($reader, $method)
    {
        try {
            $date = $this->_safe_get_value($reader, $method);
            if ($date && $date instanceof DateTime) {
                return $date->format('Y-m-d');
            } elseif ($date && is_string($date)) {
                return date('Y-m-d', strtotime($date));
            }
        } catch (Exception $e) {
            // Silently handle date parsing errors
        }
        return null;
    }

    /**
     * Safely get value from UBL line item
     */
    private function _safe_get_line_value($line, $method)
    {
        try {
            if (method_exists($line, $method)) {
                return $line->$method();
            }
        } catch (Exception $e) {
            // Silently handle missing values
        }
        return null;
    }
}