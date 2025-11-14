<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('peppol/peppol_model');
    }

    /**
     * Send invoice via PEPPOL
     */
    public function send_invoice($invoice_id)
    {
        try {
            // Load invoice
            $this->CI->load->model('invoices_model');
            $invoice = $this->CI->invoices_model->get($invoice_id);

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => _l('peppol_invoice_not_found')
                ];
            }

            // Check if client has PEPPOL identifier
            $this->CI->load->model('clients_model');
            $client = $this->CI->clients_model->get($invoice->clientid);

            if (!$client || empty($client->peppol_identifier)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_client_no_identifier')
                ];
            }

            // Check if already sent
            $existing = $this->CI->peppol_model->get_peppol_invoice_by_invoice($invoice_id);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => _l('peppol_invoice_already_processed')
                ];
            }

            // Create PEPPOL invoice record
            $peppol_data = [
                'invoice_id' => $invoice_id,
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $peppol_id = $this->CI->peppol_model->create_peppol_invoice($peppol_data);

            // Log activity
            $this->CI->peppol_model->log_activity([
                'type' => 'invoice_sent',
                'invoice_id' => $invoice_id,
                'message' => _l('peppol_invoice_sent_activity'),
                'staff_id' => get_staff_user_id()
            ]);

            return [
                'success' => true,
                'message' => _l('peppol_invoice_sent_successfully'),
                'peppol_id' => $peppol_id
            ];

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
            // Load credit note
            $this->CI->load->model('credit_notes_model');
            $credit_note = $this->CI->credit_notes_model->get($credit_note_id);

            if (!$credit_note) {
                return [
                    'success' => false,
                    'message' => _l('peppol_credit_note_not_found')
                ];
            }

            // Check if client has PEPPOL identifier
            $this->CI->load->model('clients_model');
            $client = $this->CI->clients_model->get($credit_note->clientid);

            if (!$client || empty($client->peppol_identifier)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_client_no_identifier')
                ];
            }

            // Check if already sent
            $existing = $this->CI->peppol_model->get_peppol_credit_note_by_credit_note($credit_note_id);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => _l('peppol_credit_note_already_processed')
                ];
            }

            // Create PEPPOL credit note record
            $peppol_data = [
                'credit_note_id' => $credit_note_id,
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $peppol_id = $this->CI->peppol_model->create_peppol_credit_note($peppol_data);

            // Log activity
            $this->CI->peppol_model->log_activity([
                'type' => 'credit_note_sent',
                'credit_note_id' => $credit_note_id,
                'message' => _l('peppol_credit_note_sent_activity'),
                'staff_id' => get_staff_user_id()
            ]);

            return [
                'success' => true,
                'message' => _l('peppol_credit_note_sent_successfully'),
                'peppol_id' => $peppol_id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate UBL for invoice
     */
    public function generate_invoice_ubl($invoice)
    {
        try {
            // Load required models
            $this->CI->load->model('invoices_model');
            $this->CI->load->model('clients_model');

            // Get client data
            $client = $this->CI->clients_model->get($invoice->clientid);
            if (!$client) {
                throw new Exception('Client not found for invoice');
            }

            // Get invoice items
            $invoice_items = get_items_by_type('invoice', $invoice->id);
            if (empty($invoice_items)) {
                throw new Exception('Invoice must have at least one item');
            }

            return $this->_generate_invoice_ubl_xml($invoice, $client, $invoice_items);

        } catch (Exception $e) {
            throw new Exception('Error generating invoice UBL: ' . $e->getMessage());
        }
    }

    /**
     * Generate UBL for credit note
     */
    public function generate_credit_note_ubl($credit_note)
    {
        try {
            // Load required models
            $this->CI->load->model('credit_notes_model');
            $this->CI->load->model('clients_model');

            // Get client data
            $client = $this->CI->clients_model->get($credit_note->clientid);
            if (!$client) {
                throw new Exception('Client not found for credit note');
            }

            // Get credit note items
            $credit_note_items = get_items_by_type('credit_note', $credit_note->id);
            if (empty($credit_note_items)) {
                throw new Exception('Credit note must have at least one item');
            }

            return $this->_generate_credit_note_ubl_xml($credit_note, $client, $credit_note_items);

        } catch (Exception $e) {
            throw new Exception('Error generating credit note UBL: ' . $e->getMessage());
        }
    }

    /**
     * Private method to generate invoice UBL XML
     */
    private function _generate_invoice_ubl_xml($invoice, $client, $invoice_items)
    {
        // Get company data
        $company_name = get_option('company_name');
        $company_address = get_option('company_address');
        $company_city = get_option('company_city');
        $company_zip = get_option('company_zip');
        $company_country = get_option('company_country');
        $company_vat = get_option('company_vat');
        
        // Get PEPPOL identifiers
        $supplier_identifier = get_option('peppol_company_identifier');
        $supplier_scheme = get_option('peppol_company_scheme') ?: '0208';
        $customer_identifier = $client->peppol_identifier;
        $customer_scheme = $client->peppol_scheme ?: '0208';

        // Validate required data
        if (empty($supplier_identifier)) {
            throw new Exception('Company PEPPOL identifier is not configured');
        }

        // Generate unique document ID
        $document_id = format_invoice_number($invoice->id) . '-' . date('Ymd');

        // Get currency
        $currency = $invoice->currency_name ? get_currency($invoice->currency_name) : get_base_currency();
        $currency_code = $currency ? $currency->name : 'EUR';

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
        $xml .= '    <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>' . "\n";
        
        if (!empty($invoice->terms)) {
            $xml .= '    <cbc:Note>' . htmlspecialchars($invoice->terms) . '</cbc:Note>' . "\n";
        }
        
        $xml .= '    <cbc:DocumentCurrencyCode>' . htmlspecialchars($currency_code) . '</cbc:DocumentCurrencyCode>' . "\n";

        // Add supplier and customer parties
        $xml .= $this->_generate_supplier_party_xml($company_name, $company_address, $company_city, $company_zip, $company_country, $company_vat, $supplier_identifier, $supplier_scheme);
        $xml .= $this->_generate_customer_party_xml($client, $customer_identifier, $customer_scheme);

        // Payment means
        $xml .= '    <cac:PaymentMeans>' . "\n";
        $xml .= '        <cbc:PaymentMeansCode>30</cbc:PaymentMeansCode>' . "\n";
        $xml .= '        <cbc:PaymentDueDate>' . to_sql_date($invoice->duedate) . '</cbc:PaymentDueDate>' . "\n";
        $xml .= '    </cac:PaymentMeans>' . "\n";

        // Tax totals
        $total_tax = $invoice->total - $invoice->subtotal;
        $xml .= $this->_generate_tax_total_xml($total_tax, $invoice->subtotal, $currency_code, $currency);

        // Legal monetary total
        $xml .= $this->_generate_monetary_total_xml($invoice->subtotal, $invoice->total, $currency_code, $currency);

        // Invoice lines
        $xml .= $this->_generate_invoice_lines_xml($invoice_items, $currency_code, $currency);

        $xml .= '</Invoice>';

        return $xml;
    }

    /**
     * Private method to generate credit note UBL XML
     */
    private function _generate_credit_note_ubl_xml($credit_note, $client, $credit_note_items)
    {
        // Get company data
        $company_name = get_option('company_name');
        $company_address = get_option('company_address');
        $company_city = get_option('company_city');
        $company_zip = get_option('company_zip');
        $company_country = get_option('company_country');
        $company_vat = get_option('company_vat');
        
        // Get PEPPOL identifiers
        $supplier_identifier = get_option('peppol_company_identifier');
        $supplier_scheme = get_option('peppol_company_scheme') ?: '0208';
        $customer_identifier = $client->peppol_identifier;
        $customer_scheme = $client->peppol_scheme ?: '0208';

        // Validate required data
        if (empty($supplier_identifier)) {
            throw new Exception('Company PEPPOL identifier is not configured');
        }

        // Generate unique document ID
        $document_id = format_credit_note_number($credit_note->id) . '-' . date('Ymd');

        // Get currency
        $currency = $credit_note->currency_name ? get_currency($credit_note->currency_name) : get_base_currency();
        $currency_code = $currency ? $currency->name : 'EUR';

        // Start building UBL XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2"' . "\n";
        $xml .= '            xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' . "\n";
        $xml .= '            xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">' . "\n";

        // Customization and Profile ID (PEPPOL BIS Billing 3.0)
        $xml .= '    <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0</cbc:CustomizationID>' . "\n";
        $xml .= '    <cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>' . "\n";

        // Basic credit note information
        $xml .= '    <cbc:ID>' . htmlspecialchars($document_id) . '</cbc:ID>' . "\n";
        $xml .= '    <cbc:IssueDate>' . to_sql_date($credit_note->date) . '</cbc:IssueDate>' . "\n";
        $xml .= '    <cbc:CreditNoteTypeCode>381</cbc:CreditNoteTypeCode>' . "\n";
        
        if (!empty($credit_note->terms)) {
            $xml .= '    <cbc:Note>' . htmlspecialchars($credit_note->terms) . '</cbc:Note>' . "\n";
        }
        
        $xml .= '    <cbc:DocumentCurrencyCode>' . htmlspecialchars($currency_code) . '</cbc:DocumentCurrencyCode>' . "\n";

        // Add supplier and customer parties
        $xml .= $this->_generate_supplier_party_xml($company_name, $company_address, $company_city, $company_zip, $company_country, $company_vat, $supplier_identifier, $supplier_scheme, 'AccountingSupplierParty');
        $xml .= $this->_generate_customer_party_xml($client, $customer_identifier, $customer_scheme, 'AccountingCustomerParty');

        // Tax totals
        $total_tax = $credit_note->total - $credit_note->subtotal;
        $xml .= $this->_generate_tax_total_xml($total_tax, $credit_note->subtotal, $currency_code, $currency);

        // Legal monetary total
        $xml .= $this->_generate_monetary_total_xml($credit_note->subtotal, $credit_note->total, $currency_code, $currency, 'LegalMonetaryTotal');

        // Credit note lines
        $xml .= $this->_generate_credit_note_lines_xml($credit_note_items, $currency_code, $currency);

        $xml .= '</CreditNote>';

        return $xml;
    }

    /**
     * Generate supplier party XML (reusable for invoice and credit note)
     */
    private function _generate_supplier_party_xml($company_name, $company_address, $company_city, $company_zip, $company_country, $company_vat, $supplier_identifier, $supplier_scheme, $element_name = 'AccountingSupplierParty')
    {
        $xml = "    <cac:$element_name>\n";
        $xml .= "        <cac:Party>\n";
        
        if ($supplier_identifier) {
            $xml .= '            <cbc:EndpointID schemeID="' . htmlspecialchars($supplier_scheme) . '">' . htmlspecialchars($supplier_identifier) . '</cbc:EndpointID>' . "\n";
        }
        
        if ($company_vat) {
            $xml .= "            <cac:PartyIdentification>\n";
            $xml .= '                <cbc:ID schemeID="VAT">' . htmlspecialchars($company_vat) . '</cbc:ID>' . "\n";
            $xml .= "            </cac:PartyIdentification>\n";
        }
        
        $xml .= "            <cac:PartyName>\n";
        $xml .= '                <cbc:Name>' . htmlspecialchars($company_name) . '</cbc:Name>' . "\n";
        $xml .= "            </cac:PartyName>\n";
        
        $xml .= "            <cac:PostalAddress>\n";
        $xml .= '                <cbc:StreetName>' . htmlspecialchars($company_address) . '</cbc:StreetName>' . "\n";
        $xml .= '                <cbc:CityName>' . htmlspecialchars($company_city) . '</cbc:CityName>' . "\n";
        $xml .= '                <cbc:PostalZone>' . htmlspecialchars($company_zip) . '</cbc:PostalZone>' . "\n";
        
        if ($company_country) {
            $this->CI->load->model('countries_model');
            $country = $this->CI->countries_model->get($company_country);
            if ($country) {
                $xml .= "                <cac:Country>\n";
                $xml .= '                    <cbc:IdentificationCode>' . htmlspecialchars($country->iso2) . '</cbc:IdentificationCode>' . "\n";
                $xml .= "                </cac:Country>\n";
            }
        }
        
        $xml .= "            </cac:PostalAddress>\n";
        
        if ($company_vat) {
            $xml .= "            <cac:PartyTaxScheme>\n";
            $xml .= '                <cbc:CompanyID>' . htmlspecialchars($company_vat) . '</cbc:CompanyID>' . "\n";
            $xml .= "                <cac:TaxScheme>\n";
            $xml .= "                    <cbc:ID>VAT</cbc:ID>\n";
            $xml .= "                </cac:TaxScheme>\n";
            $xml .= "            </cac:PartyTaxScheme>\n";
        }
        
        $xml .= "            <cac:PartyLegalEntity>\n";
        $xml .= '                <cbc:RegistrationName>' . htmlspecialchars($company_name) . '</cbc:RegistrationName>' . "\n";
        $xml .= "            </cac:PartyLegalEntity>\n";
        $xml .= "        </cac:Party>\n";
        $xml .= "    </cac:$element_name>\n";

        return $xml;
    }

    /**
     * Generate customer party XML (reusable for invoice and credit note)
     */
    private function _generate_customer_party_xml($client, $customer_identifier, $customer_scheme, $element_name = 'AccountingCustomerParty')
    {
        $xml = "    <cac:$element_name>\n";
        $xml .= "        <cac:Party>\n";
        
        if ($customer_identifier) {
            $xml .= '            <cbc:EndpointID schemeID="' . htmlspecialchars($customer_scheme) . '">' . htmlspecialchars($customer_identifier) . '</cbc:EndpointID>' . "\n";
        }
        
        if ($client->vat) {
            $xml .= "            <cac:PartyIdentification>\n";
            $xml .= '                <cbc:ID schemeID="VAT">' . htmlspecialchars($client->vat) . '</cbc:ID>' . "\n";
            $xml .= "            </cac:PartyIdentification>\n";
        }
        
        $xml .= "            <cac:PartyName>\n";
        $xml .= '                <cbc:Name>' . htmlspecialchars($client->company ?: ($client->firstname . ' ' . $client->lastname)) . '</cbc:Name>' . "\n";
        $xml .= "            </cac:PartyName>\n";
        
        $xml .= "            <cac:PostalAddress>\n";
        $xml .= '                <cbc:StreetName>' . htmlspecialchars($client->address ?: '') . '</cbc:StreetName>' . "\n";
        $xml .= '                <cbc:CityName>' . htmlspecialchars($client->city ?: '') . '</cbc:CityName>' . "\n";
        $xml .= '                <cbc:PostalZone>' . htmlspecialchars($client->zip ?: '') . '</cbc:PostalZone>' . "\n";
        
        if ($client->country) {
            $this->CI->load->model('countries_model');
            $country = $this->CI->countries_model->get($client->country);
            if ($country) {
                $xml .= "                <cac:Country>\n";
                $xml .= '                    <cbc:IdentificationCode>' . htmlspecialchars($country->iso2) . '</cbc:IdentificationCode>' . "\n";
                $xml .= "                </cac:Country>\n";
            }
        }
        
        $xml .= "            </cac:PostalAddress>\n";
        
        if ($client->vat) {
            $xml .= "            <cac:PartyTaxScheme>\n";
            $xml .= '                <cbc:CompanyID>' . htmlspecialchars($client->vat) . '</cbc:CompanyID>' . "\n";
            $xml .= "                <cac:TaxScheme>\n";
            $xml .= "                    <cbc:ID>VAT</cbc:ID>\n";
            $xml .= "                </cac:TaxScheme>\n";
            $xml .= "            </cac:PartyTaxScheme>\n";
        }
        
        $xml .= "            <cac:PartyLegalEntity>\n";
        $xml .= '                <cbc:RegistrationName>' . htmlspecialchars($client->company ?: ($client->firstname . ' ' . $client->lastname)) . '</cbc:RegistrationName>' . "\n";
        $xml .= "            </cac:PartyLegalEntity>\n";
        $xml .= "        </cac:Party>\n";
        $xml .= "    </cac:$element_name>\n";

        return $xml;
    }

    /**
     * Generate tax total XML
     */
    private function _generate_tax_total_xml($total_tax, $subtotal, $currency_code, $currency)
    {
        $xml = "    <cac:TaxTotal>\n";
        $xml .= '        <cbc:TaxAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($total_tax, $currency, true) . '</cbc:TaxAmount>' . "\n";
        
        if ($total_tax > 0) {
            $xml .= "        <cac:TaxSubtotal>\n";
            $xml .= '            <cbc:TaxableAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($subtotal, $currency, true) . '</cbc:TaxableAmount>' . "\n";
            $xml .= '            <cbc:TaxAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($total_tax, $currency, true) . '</cbc:TaxAmount>' . "\n";
            $xml .= "            <cac:TaxCategory>\n";
            $xml .= "                <cbc:ID>S</cbc:ID>\n";
            $xml .= '                <cbc:Percent>' . number_format(($total_tax / $subtotal) * 100, 2, '.', '') . '</cbc:Percent>' . "\n";
            $xml .= "                <cac:TaxScheme>\n";
            $xml .= "                    <cbc:ID>VAT</cbc:ID>\n";
            $xml .= "                </cac:TaxScheme>\n";
            $xml .= "            </cac:TaxCategory>\n";
            $xml .= "        </cac:TaxSubtotal>\n";
        }
        
        $xml .= "    </cac:TaxTotal>\n";

        return $xml;
    }

    /**
     * Generate monetary total XML
     */
    private function _generate_monetary_total_xml($subtotal, $total, $currency_code, $currency, $element_name = 'LegalMonetaryTotal')
    {
        $xml = "    <cac:$element_name>\n";
        $xml .= '        <cbc:LineExtensionAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($subtotal, $currency, true) . '</cbc:LineExtensionAmount>' . "\n";
        $xml .= '        <cbc:TaxExclusiveAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($subtotal, $currency, true) . '</cbc:TaxExclusiveAmount>' . "\n";
        $xml .= '        <cbc:TaxInclusiveAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($total, $currency, true) . '</cbc:TaxInclusiveAmount>' . "\n";
        $xml .= '        <cbc:PayableAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($total, $currency, true) . '</cbc:PayableAmount>' . "\n";
        $xml .= "    </cac:$element_name>\n";

        return $xml;
    }

    /**
     * Generate invoice lines XML
     */
    private function _generate_invoice_lines_xml($invoice_items, $currency_code, $currency)
    {
        $xml = '';
        $line_number = 1;
        
        foreach ($invoice_items as $item) {
            $xml .= "    <cac:InvoiceLine>\n";
            $xml .= '        <cbc:ID>' . $line_number . '</cbc:ID>' . "\n";
            $xml .= '        <cbc:InvoicedQuantity unitCode="C62">' . number_format($item['qty'], 2, '.', '') . '</cbc:InvoicedQuantity>' . "\n";
            $xml .= '        <cbc:LineExtensionAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($item['qty'] * $item['rate'], $currency, true) . '</cbc:LineExtensionAmount>' . "\n";
            
            $xml .= "        <cac:Item>\n";
            $xml .= '            <cbc:Description>' . htmlspecialchars($item['description']) . '</cbc:Description>' . "\n";
            $xml .= '            <cbc:Name>' . htmlspecialchars($item['description']) . '</cbc:Name>' . "\n";
            
            $xml .= "            <cac:ClassifiedTaxCategory>\n";
            $xml .= "                <cbc:ID>S</cbc:ID>\n";
            $xml .= "                <cbc:Percent>0</cbc:Percent>\n";
            $xml .= "                <cac:TaxScheme>\n";
            $xml .= "                    <cbc:ID>VAT</cbc:ID>\n";
            $xml .= "                </cac:TaxScheme>\n";
            $xml .= "            </cac:ClassifiedTaxCategory>\n";
            $xml .= "        </cac:Item>\n";
            
            $xml .= "        <cac:Price>\n";
            $xml .= '            <cbc:PriceAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($item['rate'], $currency, true) . '</cbc:PriceAmount>' . "\n";
            $xml .= "        </cac:Price>\n";
            $xml .= "    </cac:InvoiceLine>\n";
            
            $line_number++;
        }

        return $xml;
    }

    /**
     * Generate credit note lines XML
     */
    private function _generate_credit_note_lines_xml($credit_note_items, $currency_code, $currency)
    {
        $xml = '';
        $line_number = 1;
        
        foreach ($credit_note_items as $item) {
            $xml .= "    <cac:CreditNoteLine>\n";
            $xml .= '        <cbc:ID>' . $line_number . '</cbc:ID>' . "\n";
            $xml .= '        <cbc:CreditedQuantity unitCode="C62">' . number_format($item['qty'], 2, '.', '') . '</cbc:CreditedQuantity>' . "\n";
            $xml .= '        <cbc:LineExtensionAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($item['qty'] * $item['rate'], $currency, true) . '</cbc:LineExtensionAmount>' . "\n";
            
            $xml .= "        <cac:Item>\n";
            $xml .= '            <cbc:Description>' . htmlspecialchars($item['description']) . '</cbc:Description>' . "\n";
            $xml .= '            <cbc:Name>' . htmlspecialchars($item['description']) . '</cbc:Name>' . "\n";
            
            $xml .= "            <cac:ClassifiedTaxCategory>\n";
            $xml .= "                <cbc:ID>S</cbc:ID>\n";
            $xml .= "                <cbc:Percent>0</cbc:Percent>\n";
            $xml .= "                <cac:TaxScheme>\n";
            $xml .= "                    <cbc:ID>VAT</cbc:ID>\n";
            $xml .= "                </cac:TaxScheme>\n";
            $xml .= "            </cac:ClassifiedTaxCategory>\n";
            $xml .= "        </cac:Item>\n";
            
            $xml .= "        <cac:Price>\n";
            $xml .= '            <cbc:PriceAmount currencyID="' . htmlspecialchars($currency_code) . '">' . app_format_money($item['rate'], $currency, true) . '</cbc:PriceAmount>' . "\n";
            $xml .= "        </cac:Price>\n";
            $xml .= "    </cac:CreditNoteLine>\n";
            
            $line_number++;
        }

        return $xml;
    }
}