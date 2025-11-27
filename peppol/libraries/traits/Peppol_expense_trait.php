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
     * @return array Response with success flag and message
     */
    public function create_expense_from_document($document_id)
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

            // Check if expense already created
            $metadata = $document->provider_metadata;
            if (!empty($metadata['expense_id'])) {
                return [
                    'success' => false,
                    'message' => _l('peppol_expense_already_created')
                ];
            }

            // Get enriched document with UBL data
            $enriched_document = $this->get_enriched_document($document_id);
            if (empty($enriched_document->id)) {
                return ['success' => false, 'message' => _l('peppol_failed_to_parse_document')];
            }

            // Extract data from UBL document
            $ubl_data = $enriched_document->ubl_document['data'] ?? [];

            // Calculate amount (positive for invoices, negative for credit notes)
            $amount = $ubl_data['totals']['totalAmount'] ?? 0;
            if ($document->document_type === 'credit_note') {
                $amount = -abs($amount); // Ensure negative for credit notes
            } else {
                $amount = abs($amount); // Ensure positive for invoices
            }

            // Prepare expense data
            $expense_data = [
                'category' => $this->get_default_expense_category(),
                'amount' => $amount,
                'currency' => $ubl_data['currency'] ?? get_base_currency()->id,
                'date' => $document->received_at ? date('Y-m-d', strtotime($document->received_at)) : date('Y-m-d'),
                'expense_name' => $this->generate_expense_name($document, $ubl_data),
                'note' => $this->generate_expense_note($document, $ubl_data),
                'clientid' => 0, // No client for supplier expenses
                'project_id' => 0,
                'billable' => 0,
                'invoiceid' => 0,
                'paymentmode' => '',
                'reference_no' => $ubl_data['documentNumber'] ?? '',
                'tax' => 0,
                'tax2' => 0,
                'recurring_type' => '',
                'repeat_every' => 0,
                'recurring' => 0,
                'cycles' => 0,
                'total_cycles' => 0,
                'custom_recurring' => 0,
                'last_recurring_date' => null,
                'create_invoice_billable' => 0,
                'send_invoice_to_customer' => 0,
                'recurring_from' => null,
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => get_staff_user_id()
            ];

            // Load expenses model and create expense
            $this->CI->load->model('expenses_model');
            $expense_id = $this->CI->expenses_model->add($expense_data);

            if ($expense_id) {
                // Update PEPPOL document metadata with expense ID
                $updated_metadata = array_merge($metadata, [
                    'expense_id' => $expense_id,
                    'expense_created_at' => date('Y-m-d H:i:s'),
                    'expense_amount' => $amount
                ]);

                $this->CI->peppol_model->update_peppol_document($document_id, [
                    'provider_metadata' => json_encode($updated_metadata)
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
        $categories = $this->CI->expenses_model->get_categories();
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
        $supplier = $ubl_data['supplier']['name'] ?? 'Unknown Supplier';
        $doc_number = $ubl_data['documentNumber'] ?? '#' . $document->id;
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
            'Transmission ID: ' . ($document->provider_document_id ?? 'N/A')
        ];

        if (!empty($ubl_data['supplier']['name'])) {
            $notes[] = 'Supplier: ' . $ubl_data['supplier']['name'];
        }

        if (!empty($ubl_data['description'])) {
            $notes[] = 'Description: ' . $ubl_data['description'];
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
        $metadata = $document->provider_metadata ?? [];
        return $metadata['expense_id'] ?? null;
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
}