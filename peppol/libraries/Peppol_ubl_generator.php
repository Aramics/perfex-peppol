<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load the PEPPOL module's own autoloader
if (file_exists(FCPATH . 'modules/peppol/vendor/autoload.php')) {
    require_once(FCPATH . 'modules/peppol/vendor/autoload.php');
}

use Einvoicing\Invoice;
use Einvoicing\Party;
use Einvoicing\InvoiceLine;
use Einvoicing\Writers\UblWriter;

class Peppol_ubl_generator
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Generate UBL Invoice XML using Josemmo/Einvoicing library
     */
    public function generate_invoice_ubl($invoice, $client, $invoice_items)
    {
        try {
            // Check if library is available
            if (!$this->is_library_available()) {
                throw new Exception('Einvoicing library is not available. Please ensure vendor/autoload.php exists.');
            }

            // Create invoice instance with PEPPOL preset
            $ublInvoice = new Invoice('Einvoicing\\Presets\\Peppol');

            // Basic invoice information
            $document_id = format_invoice_number($invoice->id) . '-' . date('Ymd');
            $ublInvoice->setNumber($document_id);
            $ublInvoice->setIssueDate(new DateTime(to_sql_date($invoice->date)));
            $ublInvoice->setDueDate(new DateTime(to_sql_date($invoice->duedate ?? $invoice->date)));

            // Set currency
            $currency = $invoice->currency_name ? get_currency($invoice->currency_name) : get_base_currency();
            $currency_code = $currency ? $currency->name : 'EUR';
            $ublInvoice->setCurrency($currency_code);

            // Add notes if available
            if (!empty($invoice->terms)) {
                $ublInvoice->addNote($invoice->terms);
            }

            // Create and set supplier (seller) party
            $seller = $this->_create_supplier_party();
            $ublInvoice->setSeller($seller);

            // Create and set customer (buyer) party
            $buyer = $this->_create_customer_party($client);
            $ublInvoice->setBuyer($buyer);

            // Add invoice lines
            foreach ($invoice_items as $item) {
                $line = new InvoiceLine();
                $line->setName($item['description']);
                $line->setDescription($item['description']);
                $line->setPrice($item['rate']);
                $line->setQuantity($item['qty']);

                $ublInvoice->addLine($line);
            }

            // Generate UBL XML
            $writer = new UblWriter();
            return $writer->export($ublInvoice);
        } catch (Exception $e) {
            throw new Exception('Error generating invoice UBL with Einvoicing library: ' . $e->getMessage());
        }
    }

    /**
     * Generate UBL Credit Note XML using Josemmo/Einvoicing library
     */
    public function generate_credit_note_ubl($credit_note, $client, $credit_note_items)
    {
        try {
            // Check if library is available
            if (!$this->is_library_available()) {
                throw new Exception('Einvoicing library is not available. Please ensure vendor/autoload.php exists.');
            }

            // Create credit note instance using Invoice class with credit note type
            $ublCreditNote = new Invoice('Einvoicing\\Presets\\Peppol');

            // Set as credit note type
            $ublCreditNote->setType(Invoice::TYPE_CREDIT_NOTE); // Credit Note type code

            // Basic credit note information
            $document_id = format_credit_note_number($credit_note->id) . '-' . date('Ymd');
            $ublCreditNote->setNumber($document_id);
            $ublCreditNote->setIssueDate(new DateTime(to_sql_date($credit_note->date)));

            // Set currency
            $currency = $credit_note->currency_name ? get_currency($credit_note->currency_name) : get_base_currency();
            $currency_code = $currency ? $currency->name : 'EUR';
            $ublCreditNote->setCurrency($currency_code);

            // Add notes if available
            if (!empty($credit_note->terms)) {
                $ublCreditNote->addNote($credit_note->terms);
            }

            // Create and set supplier (seller) party
            $seller = $this->_create_supplier_party();
            $ublCreditNote->setSeller($seller);

            // Create and set customer (buyer) party
            $buyer = $this->_create_customer_party($client);
            $ublCreditNote->setBuyer($buyer);

            // Add credit note lines
            foreach ($credit_note_items as $item) {
                $line = new InvoiceLine();
                $line->setName($item['description']);
                $line->setDescription($item['description']);
                $line->setPrice($item['rate']);
                $line->setQuantity($item['qty']);

                $ublCreditNote->addLine($line);
            }

            // Generate UBL XML
            $writer = new UblWriter();
            return $writer->export($ublCreditNote);
        } catch (Exception $e) {
            throw new Exception('Error generating credit note UBL with Einvoicing library: ' . $e->getMessage());
        }
    }

    /**
     * Create supplier party from company settings
     */
    private function _create_supplier_party()
    {
        $seller = new Party();

        // Basic company information
        $company_name = get_option('company_name');
        $company_address = get_option('company_address');
        $company_city = get_option('company_city');
        $company_zip = get_option('company_zip');
        $company_country = get_option('company_country');
        $company_vat = get_option('company_vat');

        // PEPPOL identifiers
        $supplier_identifier = get_option('peppol_company_identifier');
        $supplier_scheme = get_option('peppol_company_scheme') ?: '0208';

        $seller->setName($company_name);

        // Set electronic address (PEPPOL identifier)
        if ($supplier_identifier) {
            $seller->setElectronicAddress($supplier_identifier, $supplier_scheme);
        }

        // Set postal address
        if ($company_address || $company_city || $company_zip || $company_country) {
            // Set address lines (up to 3 lines)
            $addressLines = [];
            if ($company_address) {
                $addressLines[] = $company_address;
            }
            $seller->setAddress($addressLines);

            // Set other address components separately
            if ($company_city) {
                $seller->setCity($company_city);
            }
            if ($company_zip) {
                $seller->setPostalCode($company_zip);
            }
            if ($company_country) {
                $seller->setCountry($this->_get_country_code($company_country));
            }
        }

        // Set VAT identifier
        if ($company_vat) {
            $seller->setVatNumber($company_vat);
        }

        return $seller;
    }

    /**
     * Create customer party from client data
     */
    private function _create_customer_party($client)
    {
        $buyer = new Party();

        // Get PEPPOL identifiers from custom fields
        $customer_identifier = $this->_get_client_custom_field($client->userid, 'customers_peppol_identifier');
        $customer_scheme = $this->_get_client_custom_field($client->userid, 'customers_peppol_scheme') ?: '0208';

        // Client name (company or individual)
        $client_name = $client->company ?: ($client->firstname . ' ' . $client->lastname);
        $buyer->setName($client_name);

        // Set electronic address (PEPPOL identifier)
        if ($customer_identifier) {
            $buyer->setElectronicAddress($customer_identifier, $customer_scheme);
        }

        // Set postal address
        if ($client->address || $client->city || $client->zip || $client->country) {
            // Set address lines (up to 3 lines)
            $addressLines = [];
            if ($client->address) {
                $addressLines[] = $client->address;
            }
            $buyer->setAddress($addressLines);

            // Set other address components separately
            if ($client->city) {
                $buyer->setCity($client->city);
            }
            if ($client->zip) {
                $buyer->setPostalCode($client->zip);
            }
            if ($client->country) {
                $buyer->setCountry($this->_get_country_code($client->country));
            }
        }

        // Set VAT identifier
        if ($client->vat) {
            $buyer->setVatNumber($client->vat);
        }

        return $buyer;
    }

    /**
     * Get ISO2 country code from country ID
     */
    private function _get_country_code($country_id)
    {
        if (!$country_id) {
            return 'BE'; // Default to Belgium
        }

        $country = get_country($country_id);

        return $country ? $country->iso2 : 'BE';
    }

    /**
     * Get client custom field value
     */
    private function _get_client_custom_field($client_id, $field_slug)
    {
        // Get custom field ID for the given field slug
        $this->CI->db->where('fieldto', 'customers');
        $this->CI->db->where('slug', $field_slug);
        $custom_field = $this->CI->db->get(db_prefix() . 'customfields')->row();

        if (!$custom_field) {
            return '';
        }

        // Get custom field value for the client
        $this->CI->db->where('relid', $client_id);
        $this->CI->db->where('fieldid', $custom_field->id);
        $value_row = $this->CI->db->get(db_prefix() . 'customfieldsvalues')->row();

        return $value_row ? $value_row->value : '';
    }

    /**
     * Validate that the Einvoicing library is available
     */
    public function is_library_available()
    {
        return class_exists('Einvoicing\Invoice') &&
            class_exists('Einvoicing\Writers\UblWriter');
    }
}