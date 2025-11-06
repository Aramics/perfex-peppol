<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Ubl_generator
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Generate UBL Invoice XML from Perfex CRM invoice
     */
    public function generate_invoice_ubl($invoice, $client)
    {
        // Validate input parameters
        if (!$invoice || !$client) {
            throw new Exception('Invoice and client data are required');
        }

        if (empty($client->peppol_identifier)) {
            throw new Exception('Client must have a PEPPOL identifier');
        }

        // Get invoice items
        $this->CI->load->model('invoices_model');
        $invoice_items = $this->CI->invoices_model->get_invoice_items($invoice->id);

        if (empty($invoice_items)) {
            throw new Exception('Invoice must have at least one item');
        }

        // Get company data
        $company_name = get_option('company_name');
        $company_address = get_option('company_address');
        $company_city = get_option('company_city');
        $company_zip = get_option('company_zip');
        $company_country = get_option('company_country');
        $company_vat = get_option('company_vat');
        
        // Get PEPPOL identifiers
        $supplier_identifier = get_option('peppol_company_identifier');
        $supplier_scheme = get_option('peppol_company_scheme', '0088');
        $customer_identifier = $client->peppol_identifier;
        $customer_scheme = $client->peppol_scheme ?: '0088';

        // Validate company PEPPOL identifier
        if (empty($supplier_identifier)) {
            throw new Exception('Company PEPPOL identifier is not configured');
        }

        // Generate unique invoice ID using Perfex's format function
        $document_id = format_invoice_number($invoice->id) . '-' . date('Ymd');

        // Start building UBL XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' . "\n";
        $xml .= '         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' . "\n";
        $xml .= '         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">' . "\n";

        // Customization and Profile ID (PEPPOL BIS Billing 3.0)
        $xml .= '    <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0</cbc:CustomizationID>' . "\n";
        $xml .= '    <cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>' . "\n";

        // Basic invoice information
        $xml .= '    <cbc:ID>' . htmlspecialchars($document_id) . '</cbc:ID>' . "\n";
        $xml .= '    <cbc:IssueDate>' . to_sql_date($invoice->date) . '</cbc:IssueDate>' . "\n";
        $xml .= '    <cbc:DueDate>' . to_sql_date($invoice->duedate) . '</cbc:DueDate>' . "\n";
        $xml .= '    <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>' . "\n"; // Commercial invoice
        
        if (!empty($invoice->terms)) {
            $xml .= '    <cbc:Note>' . htmlspecialchars($invoice->terms) . '</cbc:Note>' . "\n";
        }
        
        // Get currency using Perfex helper
        $currency = $invoice->currency_name ? get_currency($invoice->currency_name) : get_base_currency();
        $currency_code = $currency ? $currency->name : 'EUR';
        
        $xml .= '    <cbc:DocumentCurrencyCode>' . htmlspecialchars($currency_code) . '</cbc:DocumentCurrencyCode>' . "\n";

        // Invoice period (if applicable)
        if (!empty($invoice->project_id)) {
            $xml .= '    <cac:InvoicePeriod>' . "\n";
            $xml .= '        <cbc:StartDate>' . date('Y-m-d', strtotime(to_sql_date($invoice->date) . ' -1 month')) . '</cbc:StartDate>' . "\n";
            $xml .= '        <cbc:EndDate>' . to_sql_date($invoice->date) . '</cbc:EndDate>' . "\n";
            $xml .= '    </cac:InvoicePeriod>' . "\n";
        }

        // Supplier (AccountingSupplierParty)
        $xml .= '    <cac:AccountingSupplierParty>' . "\n";
        $xml .= '        <cac:Party>' . "\n";
        
        if ($supplier_identifier) {
            $xml .= '            <cbc:EndpointID schemeID="' . htmlspecialchars($supplier_scheme) . '">' . htmlspecialchars($supplier_identifier) . '</cbc:EndpointID>' . "\n";
        }
        
        if ($company_vat) {
            $xml .= '            <cac:PartyIdentification>' . "\n";
            $xml .= '                <cbc:ID schemeID="VAT">' . htmlspecialchars($company_vat) . '</cbc:ID>' . "\n";
            $xml .= '            </cac:PartyIdentification>' . "\n";
        }
        
        $xml .= '            <cac:PartyName>' . "\n";
        $xml .= '                <cbc:Name>' . htmlspecialchars($company_name) . '</cbc:Name>' . "\n";
        $xml .= '            </cac:PartyName>' . "\n";
        
        $xml .= '            <cac:PostalAddress>' . "\n";
        $xml .= '                <cbc:StreetName>' . htmlspecialchars($company_address) . '</cbc:StreetName>' . "\n";
        $xml .= '                <cbc:CityName>' . htmlspecialchars($company_city) . '</cbc:CityName>' . "\n";
        $xml .= '                <cbc:PostalZone>' . htmlspecialchars($company_zip) . '</cbc:PostalZone>' . "\n";
        
        if ($company_country) {
            $this->CI->load->model('countries_model');
            $country = $this->CI->countries_model->get($company_country);
            if ($country) {
                $xml .= '                <cac:Country>' . "\n";
                $xml .= '                    <cbc:IdentificationCode>' . htmlspecialchars($country->iso2) . '</cbc:IdentificationCode>' . "\n";
                $xml .= '                </cac:Country>' . "\n";
            }
        }
        
        $xml .= '            </cac:PostalAddress>' . "\n";
        
        if ($company_vat) {
            $xml .= '            <cac:PartyTaxScheme>' . "\n";
            $xml .= '                <cbc:CompanyID>' . htmlspecialchars($company_vat) . '</cbc:CompanyID>' . "\n";
            $xml .= '                <cac:TaxScheme>' . "\n";
            $xml .= '                    <cbc:ID>VAT</cbc:ID>' . "\n";
            $xml .= '                </cac:TaxScheme>' . "\n";
            $xml .= '            </cac:PartyTaxScheme>' . "\n";
        }
        
        $xml .= '            <cac:PartyLegalEntity>' . "\n";
        $xml .= '                <cbc:RegistrationName>' . htmlspecialchars($company_name) . '</cbc:RegistrationName>' . "\n";
        $xml .= '            </cac:PartyLegalEntity>' . "\n";
        $xml .= '        </cac:Party>' . "\n";
        $xml .= '    </cac:AccountingSupplierParty>' . "\n";

        // Customer (AccountingCustomerParty)
        $xml .= '    <cac:AccountingCustomerParty>' . "\n";
        $xml .= '        <cac:Party>' . "\n";
        
        if ($customer_identifier) {
            $xml .= '            <cbc:EndpointID schemeID="' . htmlspecialchars($customer_scheme) . '">' . htmlspecialchars($customer_identifier) . '</cbc:EndpointID>' . "\n";
        }
        
        if ($client->vat) {
            $xml .= '            <cac:PartyIdentification>' . "\n";
            $xml .= '                <cbc:ID schemeID="VAT">' . htmlspecialchars($client->vat) . '</cbc:ID>' . "\n";
            $xml .= '            </cac:PartyIdentification>' . "\n";
        }
        
        $xml .= '            <cac:PartyName>' . "\n";
        $xml .= '                <cbc:Name>' . htmlspecialchars($client->company ?: ($client->firstname . ' ' . $client->lastname)) . '</cbc:Name>' . "\n";
        $xml .= '            </cac:PartyName>' . "\n";
        
        $xml .= '            <cac:PostalAddress>' . "\n";
        $xml .= '                <cbc:StreetName>' . htmlspecialchars($client->address ?: '') . '</cbc:StreetName>' . "\n";
        $xml .= '                <cbc:CityName>' . htmlspecialchars($client->city ?: '') . '</cbc:CityName>' . "\n";
        $xml .= '                <cbc:PostalZone>' . htmlspecialchars($client->zip ?: '') . '</cbc:PostalZone>' . "\n";
        
        if ($client->country) {
            $this->CI->load->model('countries_model');
            $country = $this->CI->countries_model->get($client->country);
            if ($country) {
                $xml .= '                <cac:Country>' . "\n";
                $xml .= '                    <cbc:IdentificationCode>' . htmlspecialchars($country->iso2) . '</cbc:IdentificationCode>' . "\n";
                $xml .= '                </cac:Country>' . "\n";
            }
        }
        
        $xml .= '            </cac:PostalAddress>' . "\n";
        
        if ($client->vat) {
            $xml .= '            <cac:PartyTaxScheme>' . "\n";
            $xml .= '                <cbc:CompanyID>' . htmlspecialchars($client->vat) . '</cbc:CompanyID>' . "\n";
            $xml .= '                <cac:TaxScheme>' . "\n";
            $xml .= '                    <cbc:ID>VAT</cbc:ID>' . "\n";
            $xml .= '                </cac:TaxScheme>' . "\n";
            $xml .= '            </cac:PartyTaxScheme>' . "\n";
        }
        
        $xml .= '            <cac:PartyLegalEntity>' . "\n";
        $xml .= '                <cbc:RegistrationName>' . htmlspecialchars($client->company ?: ($client->firstname . ' ' . $client->lastname)) . '</cbc:RegistrationName>' . "\n";
        $xml .= '            </cac:PartyLegalEntity>' . "\n";
        $xml .= '        </cac:Party>' . "\n";
        $xml .= '    </cac:AccountingCustomerParty>' . "\n";

        // Payment means
        $xml .= '    <cac:PaymentMeans>' . "\n";
        $xml .= '        <cbc:PaymentMeansCode>30</cbc:PaymentMeansCode>' . "\n"; // Credit transfer
        $xml .= '        <cbc:PaymentDueDate>' . to_sql_date($invoice->duedate) . '</cbc:PaymentDueDate>' . "\n";
        $xml .= '    </cac:PaymentMeans>' . "\n";

        // Tax totals
        $total_tax = $invoice->total - $invoice->subtotal;
        $xml .= '    <cac:TaxTotal>' . "\n";
        $xml .= '        <cbc:TaxAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($total_tax, $currency, true) . '</cbc:TaxAmount>' . "\n";
        
        if ($total_tax > 0) {
            $xml .= '        <cac:TaxSubtotal>' . "\n";
            $xml .= '            <cbc:TaxableAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($invoice->subtotal, $currency, true) . '</cbc:TaxableAmount>' . "\n";
            $xml .= '            <cbc:TaxAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($total_tax, $currency, true) . '</cbc:TaxAmount>' . "\n";
            $xml .= '            <cac:TaxCategory>' . "\n";
            $xml .= '                <cbc:ID>S</cbc:ID>' . "\n"; // Standard rate
            $xml .= '                <cbc:Percent>' . number_format(($total_tax / $invoice->subtotal) * 100, 2, '.', '') . '</cbc:Percent>' . "\n";
            $xml .= '                <cac:TaxScheme>' . "\n";
            $xml .= '                    <cbc:ID>VAT</cbc:ID>' . "\n";
            $xml .= '                </cac:TaxScheme>' . "\n";
            $xml .= '            </cac:TaxCategory>' . "\n";
            $xml .= '        </cac:TaxSubtotal>' . "\n";
        }
        
        $xml .= '    </cac:TaxTotal>' . "\n";

        // Legal monetary total
        $xml .= '    <cac:LegalMonetaryTotal>' . "\n";
        $xml .= '        <cbc:LineExtensionAmount currencyID="' . htmlspecialchars($invoice->currency_name ?: get_base_currency()->name) . '">' . app_format_money($invoice->subtotal, $currency, true) . '</cbc:LineExtensionAmount>' . "\n";
        $xml .= '        <cbc:TaxExclusiveAmount currencyID="' . htmlspecialchars($invoice->currency_name ?: get_base_currency()->name) . '">' . app_format_money($invoice->subtotal, $currency, true) . '</cbc:TaxExclusiveAmount>' . "\n";
        $xml .= '        <cbc:TaxInclusiveAmount currencyID="' . htmlspecialchars($invoice->currency_name ?: get_base_currency()->name) . '">' . app_format_money($invoice->total, $currency, true) . '</cbc:TaxInclusiveAmount>' . "\n";
        $xml .= '        <cbc:PayableAmount currencyID="' . htmlspecialchars($invoice->currency_name ?: get_base_currency()->name) . '">' . app_format_money($invoice->total, $currency, true) . '</cbc:PayableAmount>' . "\n";
        $xml .= '    </cac:LegalMonetaryTotal>' . "\n";

        // Invoice lines
        $line_number = 1;
        foreach ($invoice_items as $item) {
            $xml .= '    <cac:InvoiceLine>' . "\n";
            $xml .= '        <cbc:ID>' . $line_number . '</cbc:ID>' . "\n";
            $xml .= '        <cbc:InvoicedQuantity unitCode="C62">' . number_format($item['qty'], 2, '.', '') . '</cbc:InvoicedQuantity>' . "\n";
            $xml .= '        <cbc:LineExtensionAmount currencyID="' . htmlspecialchars($invoice->currency_name ?: get_base_currency()->name) . '">' . app_format_money($item['qty'] * $item['rate'], $currency, true) . '</cbc:LineExtensionAmount>' . "\n";
            
            $xml .= '        <cac:Item>' . "\n";
            $xml .= '            <cbc:Description>' . htmlspecialchars($item['description']) . '</cbc:Description>' . "\n";
            $xml .= '            <cbc:Name>' . htmlspecialchars($item['description']) . '</cbc:Name>' . "\n";
            
            $xml .= '            <cac:ClassifiedTaxCategory>' . "\n";
            $xml .= '                <cbc:ID>S</cbc:ID>' . "\n";
            $xml .= '                <cbc:Percent>0</cbc:Percent>' . "\n"; // Simplified, should calculate actual tax
            $xml .= '                <cac:TaxScheme>' . "\n";
            $xml .= '                    <cbc:ID>VAT</cbc:ID>' . "\n";
            $xml .= '                </cac:TaxScheme>' . "\n";
            $xml .= '            </cac:ClassifiedTaxCategory>' . "\n";
            $xml .= '        </cac:Item>' . "\n";
            
            $xml .= '        <cac:Price>' . "\n";
            $xml .= '            <cbc:PriceAmount currencyID="' . htmlspecialchars($invoice->currency_name ?: get_base_currency()->name) . '">' . app_format_money($item['rate'], $currency, true) . '</cbc:PriceAmount>' . "\n";
            $xml .= '        </cac:Price>' . "\n";
            $xml .= '    </cac:InvoiceLine>' . "\n";
            
            $line_number++;
        }

        $xml .= '</Invoice>';

        return $xml;
    }

    /**
     * Validate UBL XML
     */
    public function validate_ubl($ubl_content)
    {
        // Basic XML validation
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$dom->loadXML($ubl_content)) {
            $errors = libxml_get_errors();
            $error_messages = [];
            foreach ($errors as $error) {
                $error_messages[] = $error->message;
            }
            return ['valid' => false, 'errors' => $error_messages];
        }
        
        return ['valid' => true, 'errors' => []];
    }
}