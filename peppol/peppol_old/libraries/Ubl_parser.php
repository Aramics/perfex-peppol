<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Ubl_parser
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Parse UBL Invoice XML into array structure
     */
    public function parse_invoice($ubl_content)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$dom->loadXML($ubl_content)) {
            $errors = libxml_get_errors();
            throw new Exception('Invalid UBL XML: ' . implode(', ', array_column($errors, 'message')));
        }

        $xpath = new DOMXPath($dom);
        
        // Register namespaces
        $xpath->registerNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $invoice_data = [];

        try {
            // Basic invoice information
            $invoice_data['invoice_number'] = $this->getXPathValue($xpath, '//cbc:ID');
            $invoice_data['issue_date'] = $this->getXPathValue($xpath, '//cbc:IssueDate');
            $invoice_data['due_date'] = $this->getXPathValue($xpath, '//cbc:DueDate');
            $invoice_data['currency'] = $this->getXPathValue($xpath, '//cbc:DocumentCurrencyCode');
            $invoice_data['terms'] = $this->getXPathValue($xpath, '//cbc:Note');

            // Supplier information
            $invoice_data['supplier_data'] = $this->parsePartyData($xpath, '//cac:AccountingSupplierParty/cac:Party');

            // Customer information
            $invoice_data['client_data'] = $this->parsePartyData($xpath, '//cac:AccountingCustomerParty/cac:Party');

            // Monetary totals
            $invoice_data['subtotal'] = (float) $this->getXPathValue($xpath, '//cac:LegalMonetaryTotal/cbc:LineExtensionAmount');
            $invoice_data['tax_amount'] = (float) $this->getXPathValue($xpath, '//cac:TaxTotal/cbc:TaxAmount');
            $invoice_data['total'] = (float) $this->getXPathValue($xpath, '//cac:LegalMonetaryTotal/cbc:PayableAmount');

            // Invoice lines
            $invoice_data['items'] = $this->parseInvoiceLines($xpath);

            return $invoice_data;

        } catch (Exception $e) {
            throw new Exception('Error parsing UBL invoice: ' . $e->getMessage());
        }
    }

    /**
     * Parse party data (supplier or customer)
     */
    private function parsePartyData($xpath, $base_path)
    {
        $party_data = [];

        // PEPPOL identifier
        $party_data['peppol_identifier'] = $this->getXPathValue($xpath, $base_path . '/cbc:EndpointID');
        $party_data['peppol_scheme'] = $this->getXPathAttribute($xpath, $base_path . '/cbc:EndpointID', 'schemeID');

        // Company name
        $party_data['company'] = $this->getXPathValue($xpath, $base_path . '/cac:PartyName/cbc:Name');
        $party_data['legal_name'] = $this->getXPathValue($xpath, $base_path . '/cac:PartyLegalEntity/cbc:RegistrationName');

        // If no company name, use legal name
        if (empty($party_data['company']) && !empty($party_data['legal_name'])) {
            $party_data['company'] = $party_data['legal_name'];
        }

        // Contact details (if available)
        $party_data['contact_name'] = $this->getXPathValue($xpath, $base_path . '/cac:Contact/cbc:Name');
        $party_data['email'] = $this->getXPathValue($xpath, $base_path . '/cac:Contact/cbc:ElectronicMail');
        $party_data['phone'] = $this->getXPathValue($xpath, $base_path . '/cac:Contact/cbc:Telephone');

        // Split contact name into first and last name if needed
        if (!empty($party_data['contact_name'])) {
            $name_parts = explode(' ', $party_data['contact_name'], 2);
            $party_data['firstname'] = $name_parts[0] ?? '';
            $party_data['lastname'] = $name_parts[1] ?? '';
        }

        // Address
        $party_data['address'] = $this->getXPathValue($xpath, $base_path . '/cac:PostalAddress/cbc:StreetName');
        $party_data['city'] = $this->getXPathValue($xpath, $base_path . '/cac:PostalAddress/cbc:CityName');
        $party_data['zip'] = $this->getXPathValue($xpath, $base_path . '/cac:PostalAddress/cbc:PostalZone');
        $party_data['country_code'] = $this->getXPathValue($xpath, $base_path . '/cac:PostalAddress/cac:Country/cbc:IdentificationCode');

        // Convert country code to country ID
        if (!empty($party_data['country_code'])) {
            $this->CI->load->model('countries_model');
            $country = $this->CI->countries_model->get('', ['iso2' => $party_data['country_code']]);
            $party_data['country'] = $country ? $country->country_id : 0;
        } else {
            $party_data['country'] = 0;
        }

        // VAT number
        $party_data['vat'] = $this->getXPathValue($xpath, $base_path . '/cac:PartyTaxScheme/cbc:CompanyID');

        return $party_data;
    }

    /**
     * Parse invoice lines
     */
    private function parseInvoiceLines($xpath)
    {
        $items = [];
        $line_nodes = $xpath->query('//cac:InvoiceLine');

        foreach ($line_nodes as $line_node) {
            $line_xpath = new DOMXPath($line_node->ownerDocument);
            $line_xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $line_xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

            $item = [];
            
            // Set context to current line
            $line_path = $this->getNodePath($line_node);
            
            $item['description'] = $this->getXPathValue($line_xpath, $line_path . '/cac:Item/cbc:Name');
            if (empty($item['description'])) {
                $item['description'] = $this->getXPathValue($line_xpath, $line_path . '/cac:Item/cbc:Description');
            }
            
            $item['qty'] = (float) $this->getXPathValue($line_xpath, $line_path . '/cbc:InvoicedQuantity');
            $item['rate'] = (float) $this->getXPathValue($line_xpath, $line_path . '/cac:Price/cbc:PriceAmount');
            
            // Calculate total for this line
            $line_total = (float) $this->getXPathValue($line_xpath, $line_path . '/cbc:LineExtensionAmount');
            
            // If rate is 0, calculate from line total and quantity
            if ($item['rate'] == 0 && $item['qty'] > 0) {
                $item['rate'] = $line_total / $item['qty'];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Get XPath value safely
     */
    private function getXPathValue($xpath, $expression)
    {
        $nodes = $xpath->query($expression);
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    /**
     * Get XPath attribute value safely
     */
    private function getXPathAttribute($xpath, $expression, $attribute)
    {
        $nodes = $xpath->query($expression);
        return $nodes->length > 0 ? $nodes->item(0)->getAttribute($attribute) : '';
    }

    /**
     * Get node path for XPath queries
     */
    private function getNodePath($node)
    {
        $path = '';
        $current = $node;
        
        while ($current && $current->nodeType === XML_ELEMENT_NODE) {
            $name = $current->nodeName;
            $index = 1;
            
            // Find position among siblings with same name
            $sibling = $current->previousSibling;
            while ($sibling) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && $sibling->nodeName === $name) {
                    $index++;
                }
                $sibling = $sibling->previousSibling;
            }
            
            $path = '/' . $name . '[' . $index . ']' . $path;
            $current = $current->parentNode;
        }
        
        return $path;
    }

    /**
     * Validate received UBL content
     */
    public function validate_ubl($ubl_content)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$dom->loadXML($ubl_content)) {
            $errors = libxml_get_errors();
            return [
                'valid' => false,
                'errors' => array_map(function($error) {
                    return trim($error->message);
                }, $errors)
            ];
        }

        // Check if it's a valid PEPPOL UBL invoice
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $customization_id = $xpath->query('//cbc:CustomizationID')->item(0);
        
        if (!$customization_id) {
            return [
                'valid' => false,
                'errors' => ['Missing CustomizationID']
            ];
        }

        $customization_value = $customization_id->textContent;
        
        // Check if it's a PEPPOL BIS document
        if (strpos($customization_value, 'urn:fdc:peppol.eu') === false) {
            return [
                'valid' => false,
                'errors' => ['Not a valid PEPPOL BIS document']
            ];
        }

        return [
            'valid' => true,
            'errors' => []
        ];
    }
}