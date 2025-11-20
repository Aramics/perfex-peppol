<?php

use Einvoicing\Invoice;
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
            $reader = new UblReader();
            $invoice = $reader->import($ubl_xml);

            // Detect document type using the Invoice object's type
            $document_type = $this->_detect_document_type($invoice);

            // Parse document data from UBL
            $parsed_data = $this->_parse_ubl_data($invoice, $document_type, $external_document_id);

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
     * Detect document type using the Invoice object's type property
     * 
     * Uses the josemmo/einvoicing library's built-in document type detection
     * which is more reliable than XML string matching. The library properly
     * parses the UBL structure and determines the correct document type.
     * 
     * @param Invoice $invoice The imported Invoice object from UblReader
     * 
     * @return string Either 'credit_note' or 'invoice'
     * 
     * @since 1.0.0
     */
    private function _detect_document_type($invoice)
    {

        $type = $invoice->getType();

        // Check for credit note types
        if (
            $type === Invoice::TYPE_CREDIT_NOTE_RELATED_TO_GOODS_OR_SERVICES ||
            $type === Invoice::TYPE_CREDIT_NOTE_RELATED_TO_FINANCIAL_ADJUSTMENTS ||
            $type === Invoice::TYPE_CREDIT_NOTE ||
            stripos($type, '<CreditNote') !== false
        ) {
            return 'credit_note';
        }

        // Default to invoice for all other types
        return 'invoice';
    }

    /**
     * Extract and structure all data from UBL Invoice object into standardized format
     * 
     * This method coordinates the extraction of all document components:
     * - Basic document information (dates, currency, references)
     * - Party information (buyer and seller)
     * - Line items with all details
     * - Document totals and tax information
     * 
     * @param Invoice $invoice The imported Invoice object from UblReader
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
    private function _parse_ubl_data($invoice, $document_type, $external_document_id)
    {
        // Extract dates with proper formatting
        $issue_date = $invoice->getIssueDate();
        $due_date = $invoice->getDueDate();

        $data = [
            'external_id' => $external_document_id,
            'document_type' => $document_type,
            'document_number' => $invoice->getNumber(),
            'issue_date' => $issue_date ? $issue_date->format('Y-m-d') : null,
            'due_date' => $due_date ? $due_date->format('Y-m-d') : null,
            'currency_code' => $invoice->getCurrency(),
            'notes' => implode("\n", $invoice->getNotes()),
            'payment_terms' => $invoice->getPaymentTerms() ?: '',
            'billing_reference' => $this->_get_billing_reference($invoice)
        ];

        // Parse buyer/seller information
        $data['buyer'] = $this->_parse_invoice_party_info($invoice, 'buyer');
        $data['seller'] = $this->_parse_invoice_party_info($invoice, 'seller');

        // Parse line items
        $data['items'] = $this->_parse_invoice_line_items($invoice, $document_type);

        // Calculate totals
        $data['totals'] = $this->_parse_invoice_totals($invoice);

        return $data;
    }


    /**
     * Extract and process line items from UBL document
     * 
     * Processes line items from UBL documents using the Invoice object's getLines() method.
     * Each line item is converted to Perfex CRM item format with proper
     * quantity handling - credit notes try getCreditedQuantity first, falling back
     * to getInvoicedQuantity if not available.
     * 
     * Exceptions are allowed to bubble up if line parsing fails completely,
     * as this indicates a fundamental issue with the UBL document.
     * 
     * @param Invoice $invoice The imported Invoice object
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
     *     @type string $unit            Unit of measure code (e.g., 'PCE', 'KGM')
     *     @type int    $order           Line item order/sequence
     *     @type array  $taxname         Tax information (empty array for now)
     * }
     * 
     * @throws Exception When line items cannot be extracted from the Invoice object
     * 
     * @since 1.0.0
     */
    private function _parse_invoice_line_items($invoice, $document_type)
    {
        $items = [];

        // Get line items using the Invoice object - let exceptions bubble up
        $lines = $invoice->getLines();

        $order = 1;
        foreach ($lines as $line) {
            $items[] = [
                'description' => clear_textarea_breaks($line->getName()),
                'long_description' => clear_textarea_breaks($line->getDescription()),
                'qty' => $line->getQuantity() ?: $line->getBaseQuantity(),
                'rate' => $line->getPrice(),
                'unit' => 1, //$line->getUnit(), // default to "C62"
                'order' => $order++,
                'taxname' => [] // Tax handling can be enhanced later
            ];
        }

        return $items;
    }

    /**
     * Extract financial totals from Invoice object
     * 
     * @param Invoice $invoice The Invoice object
     * 
     * @return array Financial totals structure
     * 
     * @since 1.0.0
     */
    private function _parse_invoice_totals($invoice)
    {
        $totals = $invoice->getTotals();

        return [
            'subtotal' => $totals->taxExclusiveAmount ?? 0,
            'tax_amount' => $totals->vatAmount ?? 0,
            'total' => $totals->payableAmount ?? 0
        ];
    }

    /**
     * Extract party information from Invoice object
     * 
     * @param Invoice $invoice The Invoice object
     * @param string $party_type Either 'buyer' or 'seller'
     * 
     * @return array Complete party information structure
     * 
     * @since 1.0.0
     */
    private function _parse_invoice_party_info($invoice, $party_type)
    {
        if ($party_type === 'buyer') {
            $party = $invoice->getBuyer();
        } else {
            $party = $invoice->getSeller();
        }

        return [
            'name' => $party->getName(),
            'identifier' => $party->getElectronicAddress(),
            'scheme' => $party->getElectronicAddressScheme(),
            'vat_number' => $party->getVatNumber(),
            'email' => $party->getEmailAddress(),
            'address' => $party->getAddress(),
            'city' => $party->getCity(),
            'postal_code' => $party->getPostalCode(),
            'country_code' => $party->getCountryCode(),
            'telephone' => $party->getPhone(),
            'website' => ''
        ];
    }


    /**
     * Extract billing reference from Invoice object
     * 
     * @param Invoice $invoice The Invoice object
     * 
     * @return string Billing reference or empty string
     * 
     * @since 1.0.0
     */
    private function _get_billing_reference($invoice)
    {
        $references = $invoice->getPrecedingInvoiceReferences();
        if ($references && is_array($references) && !empty($references)) {
            return $references[0]->getValue() ?? '';
        }
        return '';
    }
}