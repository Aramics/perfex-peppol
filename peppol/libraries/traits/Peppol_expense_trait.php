<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Expense Creation Trait
 * 
 * This trait provides methods for creating expense records from PEPPOL documents.
 * It handles the conversion of received invoices and credit notes into expense records
 * with proper amount handling and metadata tracking.
 */
trait Peppol_expense_trait
{
    /**
     * Create expense from PEPPOL document
     * 
     * @param int $document_id PEPPOL document ID
     * @param array $override_data Optional override data for user customization
     * @return array Response with success flag and message
     */
    public function create_expense_from_document($document_id, $override_data = [])
    {
        try {
            $document = $this->CI->peppol_model->get_peppol_document_by_id($document_id);

            if (!$document) {
                return ['success' => false, 'message' => _l('peppol_document_not_found')];
            }

            // Only allow expense creation for received documents
            if (empty($document->received_at)) {
                return ['success' => false, 'message' => _l('peppol_expense_only_received_documents')];
            }

            // Validate document status based on type
            $status_validation = $this->validate_document_status_for_expense($document);
            if (!$status_validation['valid']) {
                return ['success' => false, 'message' => $status_validation['message']];
            }

            // Check if expense already created and still exists
            if (!empty($document->expense_id)) {
                $this->CI->load->model('expenses_model');
                $existing_expense = $this->CI->expenses_model->get($document->expense_id);

                if ($existing_expense) {
                    // Expense record still exists, prevent creation
                    return [
                        'success' => false,
                        'message' => _l('peppol_expense_already_created')
                    ];
                } else {
                    // Expense record was deleted, clear the expense_id and allow creation
                    $this->CI->peppol_model->update_peppol_document($document_id, [
                        'expense_id' => null
                    ]);
                }
            }

            // Get enriched document with UBL data
            $enriched_document = $this->get_enriched_document($document_id);
            if (empty($enriched_document->id)) {
                return ['success' => false, 'message' => _l('peppol_failed_to_parse_document')];
            }

            // Extract data from UBL document
            $ubl_data = $enriched_document->ubl_document['data'] ?? [];

            // Calculate amount (positive for invoices, negative for credit notes)
            $amount = $ubl_data['totals']['total'] ?? 0;
            if ($document->document_type === 'credit_note') {
                $amount = -abs($amount); // Ensure negative for credit notes
            } else {
                $amount = abs($amount); // Ensure positive for invoices
            }

            // Extract tax information from UBL data
            $tax_info = $this->extract_tax_information($ubl_data);

            // Extract payment mode from UBL data
            $payment_mode = $this->extract_payment_mode($ubl_data);

            // Apply user overrides if provided
            if (!empty($override_data['category'])) {
                $category_id = $override_data['category'];
            } else {
                $category_id = $this->get_default_expense_category();
            }

            if (!empty($override_data['paymentmode'])) {
                $payment_mode = $override_data['paymentmode'];
            }

            if (isset($override_data['tax_rate'])) {
                $tax_info['tax1_rate'] = (float) $override_data['tax_rate'];
            }

            if (isset($override_data['tax2_rate'])) {
                $tax_info['tax2_rate'] = (float) $override_data['tax2_rate'];
            }

            // Prepare expense data
            $expense_data = [
                'category' => $category_id,
                'amount' => $amount,
                'currency' => get_base_currency()->id,
                'date' => $document->received_at ? date('Y-m-d', strtotime($document->received_at)) : date('Y-m-d'),
                'expense_name' => $this->generate_expense_name($document, $ubl_data),
                'note' => $this->generate_expense_note($document, $ubl_data),
                'clientid' => 0, // No client for supplier expenses
                'project_id' => 0,
                'billable' => 0,
                'invoiceid' => 0,
                'paymentmode' => $payment_mode,
                'reference_no' => $ubl_data['document_number'] ?? '',
                'tax' => $tax_info['tax1_rate'],
                'tax2' => $tax_info['tax2_rate'],
                'create_invoice_billable' => 0,
                'send_invoice_to_customer' => 0,
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => get_staff_user_id()
            ];

            // Load expenses model and create expense
            $this->CI->load->model('expenses_model');
            $expense_id = $this->CI->expenses_model->add($expense_data);

            if ($expense_id) {
                // Update PEPPOL document with expense ID
                $this->CI->peppol_model->update_peppol_document($document_id, [
                    'expense_id' => $expense_id
                ]);

                return [
                    'success' => true,
                    'message' => _l('peppol_expense_created_successfully'),
                    'expense_id' => $expense_id
                ];
            } else {
                return ['success' => false, 'message' => _l('peppol_failed_to_create_expense')];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating expense: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get default expense category (create if doesn't exist)
     * Uses caching to avoid repeated database lookups
     * 
     * @return int Category ID
     */
    protected function get_default_expense_category()
    {
        $this->CI->load->model('expenses_model');

        // First check if we have cached category ID in options
        $cached_category_id = get_option('peppol_expense_category_id');

        if ($cached_category_id) {
            // Verify category still exists by ID lookup (more efficient than name search)
            $category = $this->CI->expenses_model->get_category($cached_category_id);
            if ($category) {
                return $cached_category_id;
            } else {
                // Category was deleted, clear the cached option
                delete_option('peppol_expense_category_id');
            }
        }

        // Fallback: Search for existing PEPPOL category by name
        $categories = $this->CI->expenses_model->get_category();
        foreach ($categories as $category) {
            if (strtolower($category['name']) === 'peppol') {
                // Found existing category, cache its ID for future use
                update_option('peppol_expense_category_id', $category['id']);
                return $category['id'];
            }
        }

        // Create new PEPPOL category if it doesn't exist
        $category_data = [
            'name' => 'PEPPOL',
            'description' => 'Expenses created from PEPPOL documents'
        ];

        $this->CI->db->insert(db_prefix() . 'expenses_categories', $category_data);
        $category_id = $this->CI->db->insert_id();

        // Cache the new category ID for future lookups
        update_option('peppol_expense_category_id', $category_id);

        return $category_id;
    }

    /**
     * Generate expense name from document data
     * 
     * @param object $document PEPPOL document
     * @param array $ubl_data Parsed UBL data
     * @return string Generated expense name
     */
    protected function generate_expense_name($document, $ubl_data)
    {
        $supplier = $ubl_data['seller']['name'] ?? $ubl_data['seller']['vat_number'] ?? 'Unknown Supplier';
        $doc_number = $ubl_data['document_number'] ?? $document->provider_document_id ?? '#' . $document->id;
        $type = ucfirst(str_replace('_', ' ', $document->document_type));

        return sprintf('%s - %s %s', $supplier, $type, $doc_number);
    }

    /**
     * Generate expense note from document data
     * 
     * @param object $document PEPPOL document
     * @param array $ubl_data Parsed UBL data
     * @return string Generated expense note
     */
    protected function generate_expense_note($document, $ubl_data)
    {
        $notes = [
            'Created from PEPPOL ' . $document->document_type,
            'Provider: ' . $document->provider,
            'Transmission ID: ' . ($document->metadata['transmissionId'] ?? 'N/A')
        ];

        if (!empty($ubl_data['seller']['name'])) {
            $notes[] = 'Supplier: ' . $ubl_data['seller']['name'];
        }

        if (!empty($ubl_data['seller']['vat_number'])) {
            $notes[] = 'VAT: ' . $ubl_data['seller']['vat_number'];
        }

        if (!empty($ubl_data['notes'])) {
            $notes[] = 'Notes: ' . $ubl_data['notes'];
        }

        if (!empty($ubl_data['payment_terms'])) {
            $notes[] = 'Payment Terms: ' . $ubl_data['payment_terms'];
        }

        return implode("\n", $notes);
    }

    /**
     * Check if document has associated expense record
     * 
     * @param object $document PEPPOL document
     * @return int|null Expense ID if exists, null otherwise
     */
    protected function get_document_expense_id($document)
    {
        return $document->expense_id ?? null;
    }

    /**
     * Get expense record for PEPPOL document
     * 
     * @param int $document_id PEPPOL document ID
     * @return object|null Expense record if exists
     */
    protected function get_document_expense($document_id)
    {
        $document = $this->CI->peppol_model->get_peppol_document_by_id($document_id);
        if (!$document) {
            return null;
        }

        $expense_id = $this->get_document_expense_id($document);
        if (!$expense_id) {
            return null;
        }

        $this->CI->load->model('expenses_model');
        return $this->CI->expenses_model->get($expense_id);
    }

    /**
     * Validate document status for expense creation
     * 
     * @param object $document PEPPOL document
     * @return array Validation result with 'valid' boolean and 'message'
     */
    protected function validate_document_status_for_expense($document)
    {
        // Only allow expense creation for inbound documents (local_reference_id IS NULL)
        if (!empty($document->local_reference_id)) {
            return [
                'valid' => false,
                'message' => _l('peppol_expense_only_received_documents')
            ];
        }

        if ($document->document_type === 'invoice') {
            // For invoices, check if status is 'FULLY_PAID'
            if ($document->status !== 'FULLY_PAID') {
                return [
                    'valid' => false,
                    'message' => _l('peppol_expense_invoice_not_paid')
                ];
            }
        } elseif ($document->document_type === 'credit_note') {
            // For credit notes, check if status is 'ACCEPTED'
            if ($document->status !== 'ACCEPTED') {
                return [
                    'valid' => false,
                    'message' => _l('peppol_expense_credit_note_not_accepted')
                ];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Extract tax information from UBL document data
     * 
     * @param array $ubl_data Parsed UBL data
     * @return array Tax information with tax1_rate and tax2_rate
     */
    protected function extract_tax_information($ubl_data)
    {
        $tax_info = [
            'tax1_rate' => 0,
            'tax2_rate' => 0
        ];

        // Extract tax information from UBL line items (most reliable source)
        if (isset($ubl_data['items']) && is_array($ubl_data['items'])) {
            $tax_rates = [];

            foreach ($ubl_data['items'] as $item) {
                if (!empty($item['taxname']) && is_array($item['taxname'])) {
                    foreach ($item['taxname'] as $tax_string) {
                        // Parse tax string format: "VAT|21" or similar
                        if (strpos($tax_string, '|') !== false) {
                            list(, $tax_rate) = explode('|', $tax_string, 2);
                            $rate = (float) $tax_rate;
                            if ($rate > 0) {
                                $tax_rates[] = $rate;
                            }
                        }
                    }
                }
            }

            // Remove duplicates and sort rates to get highest rates first
            $tax_rates = array_unique($tax_rates);
            rsort($tax_rates);

            // Assign first two rates
            if (count($tax_rates) > 0) {
                $tax_info['tax1_rate'] = $tax_rates[0];
            }
            if (count($tax_rates) > 1) {
                $tax_info['tax2_rate'] = $tax_rates[1];
            }
        }

        // Alternative: Extract from totals if item-level tax not available
        if ($tax_info['tax1_rate'] == 0 && isset($ubl_data['totals'])) {
            $totals = $ubl_data['totals'];
            $net_amount = $totals['subtotal'] ?? 0;
            $tax_amount = $totals['tax_amount'] ?? 0;

            if ($net_amount > 0 && $tax_amount > 0) {
                $tax_info['tax1_rate'] = round(($tax_amount / $net_amount) * 100, 2);
            }
        }

        return $tax_info;
    }

    /**
     * Extract payment mode from UBL document data
     * 
     * @param array $ubl_data Parsed UBL data
     * @return string Payment mode ID or empty string
     */
    protected function extract_payment_mode($ubl_data)
    {
        // Try to extract payment method from UBL payments array
        if (isset($ubl_data['payments']) && is_array($ubl_data['payments'])) {
            foreach ($ubl_data['payments'] as $payment) {
                $payment_code = $payment['payment_means_code'] ?? '';

                // Map PEPPOL payment means codes to local payment modes
                $payment_mode_id = $this->map_payment_means_code_to_mode($payment_code);
                if ($payment_mode_id) {
                    return $payment_mode_id;
                }

                // Check instruction notes for payment hints
                if (!empty($payment['instruction_note'])) {
                    $note = strtolower($payment['instruction_note']);
                    if (strpos($note, 'bank') !== false || strpos($note, 'transfer') !== false) {
                        return $this->get_bank_transfer_payment_mode();
                    } elseif (strpos($note, 'cash') !== false) {
                        return $this->get_cash_payment_mode();
                    }
                }

                // Check for bank transfer details in transfers
                if (!empty($payment['transfers']) && is_array($payment['transfers'])) {
                    // If we have bank transfer details, it's likely a bank transfer
                    foreach ($payment['transfers'] as $transfer) {
                        if (!empty($transfer['account_id']) || !empty($transfer['bank_bic'])) {
                            return $this->get_bank_transfer_payment_mode();
                        }
                    }
                }
            }
        }

        // Fallback: Try to detect from payment terms
        if (!empty($ubl_data['payment_terms'])) {
            $payment_terms = strtolower($ubl_data['payment_terms']);
            if (strpos($payment_terms, 'bank') !== false || strpos($payment_terms, 'transfer') !== false) {
                return $this->get_bank_transfer_payment_mode();
            } elseif (strpos($payment_terms, 'cash') !== false) {
                return $this->get_cash_payment_mode();
            }
        }

        // Return empty if no payment mode detected
        return '1';
    }

    /**
     * Map PEPPOL payment means code to local payment mode
     * 
     * @param string $payment_code PEPPOL payment means code
     * @return string Payment mode ID or empty string
     */
    protected function map_payment_means_code_to_mode($payment_code)
    {
        // Common PEPPOL payment means codes
        $payment_mapping = [
            '1'  => 'bank_transfer', // Bank transfer/offline payment
            '10' => 'cash',          // In cash
            '20' => 'check',         // Cheque
            '23' => 'bank_transfer', // Credit card
            '30' => 'bank_transfer', // Credit transfer
            '31' => 'bank_transfer', // Debit transfer  
            '42' => 'bank_transfer', // Payment to bank account
            '48' => 'bank_transfer', // Bank card
            '49' => 'bank_transfer', // Direct debit
        ];

        if (isset($payment_mapping[$payment_code])) {
            $mode_name = $payment_mapping[$payment_code];
            return $this->get_payment_mode_by_name($mode_name);
        }

        return '';
    }

    /**
     * Get payment mode ID by name
     * 
     * @param string $mode_name Payment mode name
     * @return string Payment mode ID or empty string
     */
    protected function get_payment_mode_by_name($mode_name)
    {
        $this->CI->load->model('payment_modes_model');
        $modes = $this->CI->payment_modes_model->get('', ['name' => $mode_name]);

        if (!empty($modes)) {
            return $modes[0]['id'];
        }

        return '';
    }

    /**
     * Get bank transfer payment mode ID
     * 
     * @return string Payment mode ID or empty string
     */
    protected function get_bank_transfer_payment_mode()
    {
        return $this->get_payment_mode_by_name('bank_transfer') ?:
            $this->get_payment_mode_by_name('Bank Transfer') ?:
            $this->get_payment_mode_by_name('Wire Transfer') ?: '';
    }

    /**
     * Get cash payment mode ID
     * 
     * @return string Payment mode ID or empty string
     */
    protected function get_cash_payment_mode()
    {
        return $this->get_payment_mode_by_name('cash') ?:
            $this->get_payment_mode_by_name('Cash') ?: '';
    }

    /**
     * Prepare expense form data for UI display
     * 
     * @param int $document_id PEPPOL document ID
     * @return array Form data including document info and auto-detected values
     */
    public function prepare_expense_form_data($document_id)
    {
        try {
            $document = $this->CI->peppol_model->get_peppol_document_by_id($document_id);

            if (!$document) {
                return ['success' => false, 'message' => _l('peppol_document_not_found')];
            }

            // Validate document status
            $status_validation = $this->validate_document_status_for_expense($document);
            if (!$status_validation['valid']) {
                // return ['success' => false, 'message' => $status_validation['message']];
            }

            // Check if expense already exists and still exists in database
            if (!empty($document->expense_id)) {
                $this->CI->load->model('expenses_model');
                $existing_expense = $this->CI->expenses_model->get($document->expense_id);

                if ($existing_expense) {
                    // Expense record still exists, prevent creation
                    return [
                        'success' => false,
                        'message' => _l('peppol_expense_already_created')
                    ];
                } else {
                    // Expense record was deleted, clear the expense_id and allow creation
                    $this->CI->peppol_model->update_peppol_document($document_id, [
                        'expense_id' => null
                    ]);
                }
            }

            // Get enriched document with UBL data
            $enriched_document = $this->get_enriched_document($document_id);
            if (empty($enriched_document->id)) {
                return ['success' => false, 'message' => _l('peppol_failed_to_parse_document')];
            }

            $ubl_data = $enriched_document->ubl_document['data'] ?? [];

            // Calculate amount
            $amount = $ubl_data['totals']['total'] ?? 0;
            if ($document->document_type === 'credit_note') {
                $amount = -abs($amount);
            } else {
                $amount = abs($amount);
            }

            // Auto-detect values
            $tax_info = $this->extract_tax_information($ubl_data);
            $payment_mode = $this->extract_payment_mode($ubl_data);
            $default_category = $this->get_default_expense_category();

            // Prepare expense data for form
            $expense_data = [
                'amount' => $amount,
                'currency' => get_base_currency(), //$ubl_data['currency_code'] ?? 
                'date' => $document->received_at ? date('Y-m-d', strtotime($document->received_at)) : date('Y-m-d'),
                'expense_name' => $this->generate_expense_name($document, $ubl_data),
                'note' => $this->generate_expense_note($document, $ubl_data),
                'reference_no' => $ubl_data['document_number'] ?? '',
                'category' => $default_category,
                'paymentmode' => $payment_mode,
                'tax1_rate' => $tax_info['tax1_rate'],
                'tax2_rate' => $tax_info['tax2_rate'],
            ];

            // Get form options
            $this->CI->load->model(['expenses_model', 'payment_modes_model']);
            $expense_categories = $this->CI->expenses_model->get_category();
            $payment_modes = $this->CI->payment_modes_model->get();

            return [
                'success' => true,
                'document' => $document,
                'expense_data' => $expense_data,
                'expense_categories' => $expense_categories,
                'payment_modes' => $payment_modes,
                'ubl_data' => $ubl_data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}