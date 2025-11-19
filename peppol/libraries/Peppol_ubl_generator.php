<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load the PEPPOL module's own autoloader
if (file_exists(FCPATH . 'modules/peppol/vendor/autoload.php')) {
    require_once(FCPATH . 'modules/peppol/vendor/autoload.php');
}

use Einvoicing\Invoice;
use Einvoicing\Party;
use Einvoicing\InvoiceLine;
use Einvoicing\Identifier;
use Einvoicing\Writers\UblWriter;

class Peppol_ubl_generator
{
    /**
     * Generate UBL Invoice XML using Josemmo/Einvoicing library
     */
    public function generate_invoice_ubl($invoice, $client, $invoice_items, $sender_info, $receiver_info)
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

            // Add buyer reference (required by PEPPOL)
            $buyer_reference = format_invoice_number($invoice->id);
            $ublInvoice->setBuyerReference($buyer_reference);

            // Create and set supplier (seller) party from enriched data
            $seller = $this->_create_party_from_data($sender_info);
            $ublInvoice->setSeller($seller);

            // Create and set customer (buyer) party from enriched data
            $buyer = $this->_create_party_from_data($receiver_info);
            $ublInvoice->setBuyer($buyer);

            // Add invoice lines
            foreach ($invoice_items as $item) {
                $itemTax =  get_invoice_item_taxes($item['id']);

                $line = new InvoiceLine();
                $line->setName($item['description'] ?: '-');
                $description = clear_textarea_breaks($item['long_description']);
                if (!empty($description)) {
                    $line->setDescription($description);
                }
                $line->setPrice((float)$item['rate']);
                $line->setQuantity((float)$item['qty']);
                if (isset($itemTax[0]['taxrate']))
                    $line->setVatRate($itemTax[0]['taxrate'] ?: 0);

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
    public function generate_credit_note_ubl($credit_note, $client, $credit_note_items, $sender_info, $receiver_info)
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

            // Add buyer reference (required by PEPPOL)
            $buyer_reference = format_credit_note_number($credit_note->id);
            $ublCreditNote->setBuyerReference($buyer_reference);

            // Create and set supplier (seller) party from enriched data
            $seller = $this->_create_party_from_data($sender_info);
            $ublCreditNote->setSeller($seller);

            // Create and set customer (buyer) party from enriched data
            $buyer = $this->_create_party_from_data($receiver_info);
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
     * Create party from enriched data provided by service
     * 
     * @param array $party_info Complete party information from service
     * @return Party Configured party object for UBL generation
     */
    private function _create_party_from_data($party_info)
    {
        $party = new Party();

        // Set party name
        if (!empty($party_info['name'])) {
            $party->setName($party_info['name']);
        }

        if (!empty($party_info['contact_name'])) {
            $party->setContactPhone($party_info['contact_name']);
        }

        if (!empty($party_info['phone'])) {
            $party->setContactPhone($party_info['phone']);
        }

        if (!empty($party_info['email'])) {
            $party->setContactEmail($party_info['email']);
        }

        // Set electronic address (PEPPOL identifier)
        if (!empty($party_info['identifier']) && !empty($party_info['scheme'])) {
            $electronicAddress = new Identifier($party_info['identifier'], $party_info['scheme']);
            $party->setElectronicAddress($electronicAddress);
        }

        // Set postal address
        $hasAddress = !empty($party_info['address']) || !empty($party_info['city']) ||
            !empty($party_info['postal_code']) || !empty($party_info['country_code']);

        if ($hasAddress) {
            // Set address lines (up to 3 lines)
            $addressLines = [];
            if (!empty($party_info['address'])) {
                $addressLines[] = $party_info['address'];
            }
            $party->setAddress($addressLines);

            // Set other address components separately
            if (!empty($party_info['city'])) {
                $party->setCity($party_info['city']);
            }
            if (!empty($party_info['postal_code'])) {
                $party->setPostalCode($party_info['postal_code']);
            }
            if (!empty($party_info['country_code'])) {
                $party->setCountry($party_info['country_code']);
            }
        }

        // Set VAT identifier
        if (!empty($party_info['vat_number'])) {
            $party->setVatNumber($party_info['vat_number']);
        }

        return $party;
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
