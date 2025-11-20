<?php

use Einvoicing\Readers\UblReader;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL UBL Document Parser
 * 
 * Dedicated UBL XML parser that converts UBL documents into structured data.
 * This class follows the Single Responsibility Principle (SRP) by focusing
 * solely on parsing UBL XML without performing any database operations.
 * 
 * The parser uses the josemmo/einvoicing library for UBL processing and 
 * returns structured data that can be consumed by the service layer for
 * document creation in Perfex CRM.
 * 
 * Supported document types:
 * - UBL Invoice (converted to Perfex invoice)
 * - UBL CreditNote (converted to Perfex credit note)
 * 
 * @package PEPPOL
 * @since 1.0.0
 * @see Peppol_service For database operations and document creation
 * @see Einvoicing\Readers\UblReader For UBL XML processing
 */
class Peppol_ubl_document_parser
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Parse UBL XML document and extract structured data for document creation
     * 
     * This is the main entry point for UBL document parsing. It accepts UBL XML
     * content and returns a structured array containing all necessary information
     * for creating documents in Perfex CRM.
     * 
     * The method automatically detects document type (invoice vs credit note) and
     * extracts all relevant data including:
     * - Document metadata (dates, currency, totals)
     * - Party information (buyer/seller details)
     * - Line items with quantities and prices
     * - Payment terms and references
     * 
     * @param string $ubl_xml The complete UBL XML content to parse
     * @param string|null $external_document_id Optional external document ID for tracking
     * 
     * @return array {
     *     Parsing result with success status and data or error message
     * 
     *     @type bool   $success Whether parsing was successful
     *     @type array  $data    Structured document data (only if success=true) {
     *         @type string $external_id         External document identifier
     *         @type string $document_type       'invoice' or 'credit_note'
     *         @type string $document_number     Document number from UBL
     *         @type string $issue_date          Issue date in Y-m-d format
     *         @type string $due_date           Due date in Y-m-d format
     *         @type string $currency_code      ISO currency code
     *         @type string $notes              Combined notes from UBL
     *         @type string $payment_terms      Payment terms text
     *         @type string $billing_reference  Reference to related documents
     *         @type array  $buyer              Buyer party information
     *         @type array  $seller             Seller party information
     *         @type array  $items              Array of line items
     *         @type array  $totals             Document totals (subtotal, tax, total)
     *     }
     *     @type string $message Error message (only if success=false)
     * }
     * 
     * @throws Exception When UBL parsing fails due to invalid XML or library issues
     * 
     * @since 1.0.0
     * @example
     *   $result = $parser->parse($ubl_xml, 'EXT-001');
     *   if ($result['success']) {
     *       $document_data = $result['data'];
     *       // Process with service layer
     *   }
     */
    public function parse($ubl_xml, $external_document_id = null)
    {
        try {

            // Parse UBL XML
            $reader = new UblReader($ubl_xml);

            // Detect document type
            $is_credit_note = $this->_detect_credit_note($ubl_xml);
            $document_type = $is_credit_note ? 'credit_note' : 'invoice';

            // Parse document data from UBL
            $parsed_data = $this->_parse_ubl_data($reader, $document_type, $external_document_id);

            return [
                'success' => true,
                'data' => $parsed_data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error parsing UBL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Detect document type by examining UBL XML root element
     * 
     * Performs a simple string search to identify whether the UBL document
     * is a credit note or invoice based on the root XML element name.
     * 
     * @param string $ubl_xml The complete UBL XML content
     * 
     * @return bool True if document is a credit note, false for invoice
     * 
     * @since 1.0.0
     */
    private function _detect_credit_note($ubl_xml)
    {
        return stripos($ubl_xml, '<CreditNote') !== false;
    }

    /**
     * Extract and structure all data from UBL reader into standardized format
     * 
     * This method coordinates the extraction of all document components:
     * - Basic document information (dates, currency, references)
     * - Party information (buyer and seller)
     * - Line items with all details
     * - Document totals and tax information
     * 
     * @param UblReader $reader The initialized UBL reader instance
     * @param string $document_type Either 'invoice' or 'credit_note'
     * @param string|null $external_document_id Optional external reference ID
     * 
     * @return array {
     *     Complete structured document data ready for Perfex CRM processing
     * 
     *     @type string $external_id         External document identifier
     *     @type string $document_type       Document type classification
     *     @type string $document_number     UBL document ID/number
     *     @type string $issue_date          Document issue date (Y-m-d)
     *     @type string $due_date           Payment due date (Y-m-d)
     *     @type string $currency_code      ISO 4217 currency code
     *     @type string $notes              Concatenated document notes
     *     @type string $payment_terms      Payment terms description
     *     @type string $billing_reference  Reference to related billing documents
     *     @type array  $buyer              Complete buyer party information
     *     @type array  $seller             Complete seller party information
     *     @type array  $items              Array of parsed line items
     *     @type array  $totals             Financial totals breakdown
     * }
     * 
     * @since 1.0.0
     */
    private function _parse_ubl_data($reader, $document_type, $external_document_id)
    {
        $data = [
            'external_id' => $external_document_id,
            'document_type' => $document_type,
            'document_number' => $this->_safe_get_value($reader, 'id'),
            'issue_date' => $this->_safe_get_date($reader, 'issueDate'),
            'due_date' => $this->_safe_get_date($reader, 'dueDate'),
            'currency_code' => $this->_safe_get_value($reader, 'documentCurrencyCode'),
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
     * Extract party information (buyer or seller) from UBL reader
     * 
     * Extracts comprehensive party data including identification, contact
     * information, and address details. Uses the UBL reader's getValue method
     * with appropriate field prefixes to distinguish between buyer and seller.
     * 
     * @param UblReader $reader The UBL reader instance
     * @param string $party_type Either 'buyer' or 'seller' to determine field prefix
     * 
     * @return array {
     *     Complete party information structure
     * 
     *     @type string $name           Party/company name
     *     @type string $identifier     PEPPOL or other business identifier
     *     @type string $scheme         Identifier scheme code (e.g., '0208' for BE VAT)
     *     @type string $vat_number     VAT registration number
     *     @type string $email          Contact email address
     *     @type string $address        Street address
     *     @type string $city           City name
     *     @type string $postal_code    ZIP/postal code
     *     @type string $country_code   ISO 3166-1 alpha-2 country code
     *     @type string $telephone      Phone number
     *     @type string $website        Website URL
     * }
     * 
     * @since 1.0.0
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
     * Extract and process line items from UBL document
     * 
     * Processes invoice lines or credit note lines depending on document type.
     * Each line item is converted to Perfex CRM item format with proper
     * quantity handling for credit notes (credited quantities) vs invoices
     * (invoiced quantities).
     * 
     * Falls back to creating a single placeholder item if line parsing fails,
     * ensuring the document can still be processed.
     * 
     * @param UblReader $reader The UBL reader instance
     * @param string $document_type Either 'invoice' or 'credit_note'
     * 
     * @return array {
     *     Array of line items in Perfex CRM format
     * 
     *     Each item contains:
     *     @type string $description      Short item description
     *     @type string $long_description Detailed item description
     *     @type float  $qty             Item quantity (positive for both types)
     *     @type float  $rate            Unit price/rate
     *     @type int    $order           Line item order/sequence
     *     @type array  $taxname         Tax information (empty array for now)
     * }
     * 
     * @since 1.0.0
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
     * Extract financial totals from UBL document
     * 
     * Retrieves the key monetary amounts from the UBL document including
     * subtotal (before tax), tax amount, and final payable amount.
     * All amounts default to 0 if not found in the UBL.
     * 
     * @param UblReader $reader The UBL reader instance
     * 
     * @return array {
     *     Financial totals structure
     * 
     *     @type float $subtotal   Line extension amount (before tax)
     *     @type float $tax_amount Tax exclusive amount or tax total
     *     @type float $total      Final payable amount including all charges
     * }
     * 
     * @since 1.0.0
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
     * Extract and concatenate all document notes from UBL
     * 
     * Retrieves all note elements from the UBL document and combines them
     * into a single string with newline separators. Handles cases where
     * the getNotes method is not available or fails.
     * 
     * @param UblReader $reader The UBL reader instance
     * 
     * @return string Concatenated notes separated by newlines, empty string if no notes
     * 
     * @since 1.0.0
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
     * Safely extract values from UBL reader with error handling
     * 
     * Attempts to retrieve values from the UBL reader using either the
     * getValue method or direct method calls. Provides graceful error
     * handling for missing methods or extraction failures.
     * 
     * This method abstracts the complexity of the UBL reader API and
     * ensures consistent behavior across different UBL document structures.
     * 
     * @param UblReader $reader The UBL reader instance
     * @param string $method The field name or method name to retrieve
     * 
     * @return mixed|null The extracted value or null if not found/accessible
     * 
     * @since 1.0.0
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
     * Safely extract and normalize date values from UBL reader
     * 
     * Retrieves date values from UBL and converts them to standardized
     * Y-m-d format for Perfex CRM compatibility. Handles both DateTime
     * objects and string dates with proper error handling.
     * 
     * @param UblReader $reader The UBL reader instance
     * @param string $method The date field name to retrieve
     * 
     * @return string|null Date in Y-m-d format or null if parsing fails
     * 
     * @since 1.0.0
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
     * Safely extract values from UBL line item objects with error handling
     * 
     * Similar to _safe_get_value but specifically designed for UBL line item
     * objects which may have different method availability. Provides consistent
     * error handling for line item data extraction.
     * 
     * @param object $line The UBL line item object
     * @param string $method The method name to call on the line item
     * 
     * @return mixed|null The extracted value or null if method fails/unavailable
     * 
     * @since 1.0.0
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