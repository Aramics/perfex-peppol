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
use Einvoicing\Payments\Payment;
use Einvoicing\Payments\Transfer;
use Einvoicing\InvoiceReference;

class Peppol_ubl_generator
{
    /**
     * Generate UBL Invoice XML using Josemmo/Einvoicing library
     */
    public function generate_invoice_ubl($invoice, $invoice_items, $sender_info, $receiver_info)
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

                $taxRate = (float)($itemTax[0]['taxrate'] ?? 0);
                $line->setVatRate($taxRate);

                if ($taxRate == 0) { // If no tax, set as zero tax category
                    $line->setVatCategory('Z');
                }

                $ublInvoice->addLine($line);
            }

            // Add payment information if invoice has payments
            if (isset($invoice->payments) && !empty($invoice->payments)) {
                $this->_add_payment_information($ublInvoice, $invoice);
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
    public function generate_credit_note_ubl($credit_note, $credit_note_items, $sender_info, $receiver_info)
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

            // Add credit note reason code (BT-21) if available
            if (isset($credit_note->credit_note_reason) && !empty($credit_note->credit_note_reason['code'])) {
                $ublCreditNote->setCreditNoteTypeCode($credit_note->credit_note_reason['code']);
            }

            // Add notes - use reason text if available, otherwise use terms
            $note_text = '';
            if (isset($credit_note->credit_note_reason) && !empty($credit_note->credit_note_reason['text'])) {
                $note_text = $credit_note->credit_note_reason['text'];
            } elseif (!empty($credit_note->terms)) {
                $note_text = $credit_note->terms;
            }
            
            if ($note_text) {
                $ublCreditNote->addNote($note_text);
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

            // Add billing references (BT-25) - Preceding Invoice Reference
            if (isset($credit_note->billing_references) && !empty($credit_note->billing_references)) {
                foreach ($credit_note->billing_references as $reference) {
                    $invoice_ref = new InvoiceReference($reference['id']);
                    if (!empty($reference['issue_date'])) {
                        $invoice_ref->setIssueDate(new DateTime(to_sql_date($reference['issue_date'])));
                    }
                    $ublCreditNote->addPrecedingInvoiceReference($invoice_ref);
                }
            }

            // Add credit note lines
            foreach ($credit_note_items as $item) {
                $itemTax =  get_credit_note_item_taxes($item['id']);

                $line = new InvoiceLine();
                $line->setName($item['description']);
                $line->setDescription($item['description']);
                $line->setPrice($item['rate']);
                $line->setQuantity($item['qty']);

                $taxRate = (float)($itemTax[0]['taxrate'] ?? 0);
                $line->setVatRate($taxRate);

                if ($taxRate == 0) { // If no tax, set as zero tax category
                    $line->setVatCategory('Z');
                }

                $ublCreditNote->addLine($line);
            }

            // Add payment information if credit note has refunds
            if (isset($credit_note->refunds) && !empty($credit_note->refunds)) {
                $this->_add_credit_note_payment_information($ublCreditNote, $credit_note);
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
     * Add payment information to UBL invoice
     * 
     * @param Invoice $ublInvoice UBL Invoice object
     * @param object $invoice Perfex invoice object with payments property
     */
    private function _add_payment_information($ublInvoice, $invoice)
    {
        $total_paid = 0;
        $payment_dates = [];

        // Process each payment record from invoice->payments
        foreach ($invoice->payments as $payment_record) {
            $payment = new Payment();

            // Determine payment method and PEPPOL code
            $payment_method = '';
            $means_code = '';

            // Check if paymentmode is '1' (offline payment/bank transfer)
            if ($payment_record['paymentmode'] === '1' || $payment_record['paymentmode'] === 1) {
                $payment_method = 'Bank Transfer';
                $means_code = '30'; // Credit transfer
            } else {
                // Any other paymentmode indicates online payment service
                $payment_method = $payment_record['name'] ?: $payment_record['paymentmethod'] ?: 'Online Payment';
                $means_code = '68'; // Online payment service
            }

            $payment->setMeansCode($means_code);

            if ($payment_method) {
                $payment->setMeansText($payment_method);
            }

            // Add payment ID if transaction ID exists
            if (!empty($payment_record['transactionid'])) {
                $payment->setId($payment_record['transactionid']);
            }

            // Track totals for payment terms
            $total_paid += (float) $payment_record['amount'];
            $payment_dates[] = $payment_record['date'];

            // Only add bank transfer details for offline payments (paymentmode = 1)
            if ($payment_record['paymentmode'] === '1' || $payment_record['paymentmode'] === 1) {
                // Check if bank account is configured - Required by PEPPOL BR-61 and BR-50
                $bank_details = isset($invoice->bank_details) ? $invoice->bank_details : null;
                $account_number = $bank_details['account_number'] ?? '';

                // If no bank account is configured, skip adding payment info to avoid PEPPOL validation errors
                if (empty($account_number)) {
                    continue; // Skip this payment record
                }

                $transfer = new Transfer();

                // Set Payment Account Identifier (BT-84)
                $transfer->setAccountId($account_number);

                // Set bank account name
                if (!empty($bank_details['account_name'])) {
                    $transfer->setAccountName($bank_details['account_name']);
                }

                // Set BIC/SWIFT code if available
                $bank_bic = $bank_details['bank_bic'] ?? '';
                if (!empty($bank_bic)) {
                    $transfer->setProvider($bank_bic);
                }

                $payment->addTransfer($transfer);
            }

            $ublInvoice->addPayment($payment);
        }

        // Calculate balance due
        $balance_due = (float) $invoice->total - $total_paid;
        $is_paid = $total_paid >= (float) $invoice->total;

        // Get the latest payment date (most recent chronologically)
        $latest_payment_date = !empty($payment_dates) ? max($payment_dates) : date('Y-m-d');

        // Set payment terms based on payment status (only if templates are provided)
        if (isset($invoice->payment_terms_templates)) {
            if ($balance_due > 0 && isset($invoice->payment_terms_templates['partial'])) {
                $payment_terms = sprintf(
                    $invoice->payment_terms_templates['partial'],
                    number_format($total_paid, 2),
                    $latest_payment_date,
                    number_format($balance_due, 2)
                );
                $ublInvoice->setPaymentTerms($payment_terms);
            } elseif ($is_paid && isset($invoice->payment_terms_templates['paid'])) {
                $payment_terms = sprintf(
                    $invoice->payment_terms_templates['paid'],
                    number_format($total_paid, 2),
                    $latest_payment_date
                );
                $ublInvoice->setPaymentTerms($payment_terms);
            }
        }
    }

    /**
     * Add payment information to UBL credit note (for refunds)
     * 
     * @param Invoice $ublCreditNote UBL Credit Note object
     * @param object $credit_note Perfex credit note object with refunds property
     */
    private function _add_credit_note_payment_information($ublCreditNote, $credit_note)
    {
        $total_refunded = 0;
        $payment_dates = [];

        // Process each refund record from credit note->refunds
        foreach ($credit_note->refunds as $payment_record) {
            $payment = new Payment();

            // Determine refund payment method and PEPPOL code
            $payment_method = '';
            $means_code = '';

            // For credit notes, typically credit transfer back to customer
            if ($payment_record['paymentmode'] === '1' || $payment_record['paymentmode'] === 1) {
                $payment_method = 'Credit Transfer Refund';
                $means_code = '30'; // Credit transfer
            } else {
                // Other refund methods (credit card refund, etc.)
                $payment_method = 'Refund - ' . ($payment_record['name'] ?: $payment_record['paymentmethod'] ?: 'Other Method');
                $means_code = '68'; // Online payment service refund
            }

            $payment->setMeansCode($means_code);

            if ($payment_method) {
                $payment->setMeansText($payment_method);
            }

            // Add payment ID if transaction ID exists
            if (!empty($payment_record['transactionid'])) {
                $payment->setId($payment_record['transactionid']);
            }

            // Track totals for payment terms
            $total_refunded += (float) $payment_record['amount'];
            $payment_dates[] = $payment_record['date'];

            // Add bank transfer details for credit transfer refunds (paymentmode = 1)
            if ($payment_record['paymentmode'] === '1' || $payment_record['paymentmode'] === 1) {
                // Check if bank account is configured
                $bank_details = isset($credit_note->bank_details) ? $credit_note->bank_details : null;
                $account_number = $bank_details['account_number'] ?? '';
                
                // Only add transfer details if bank account is configured
                if (!empty($account_number)) {
                    $transfer = new Transfer();

                    // Set Payment Account Identifier for refund
                    $transfer->setAccountId($account_number);

                    // Set bank account name
                    if (!empty($bank_details['account_name'])) {
                        $transfer->setAccountName($bank_details['account_name']);
                    }

                    // Set BIC/SWIFT code if available
                    $bank_bic = $bank_details['bank_bic'] ?? '';
                    if (!empty($bank_bic)) {
                        $transfer->setProvider($bank_bic);
                    }

                    $payment->addTransfer($transfer);
                }
            }

            $ublCreditNote->addPayment($payment);
        }

        // Get the latest refund date (most recent chronologically)
        $latest_refund_date = !empty($payment_dates) ? max($payment_dates) : date('Y-m-d');

        // Set payment terms for credit note refunds (only if templates are provided)
        if (isset($credit_note->payment_terms_templates) && isset($credit_note->payment_terms_templates['refund'])) {
            $payment_terms = sprintf(
                $credit_note->payment_terms_templates['refund'],
                number_format($total_refunded, 2),
                $latest_refund_date
            );
            $ublCreditNote->setPaymentTerms($payment_terms);
        }
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