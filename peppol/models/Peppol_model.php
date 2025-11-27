<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_model extends App_Model
{
    /**
     * PEPPOL Document Status Values (API Notification Codes)
     * 
     * Direction determined by local_reference_id:
     * - Outbound: local_reference_id IS NOT NULL (our invoice/credit note sent out)
     * - Inbound:  local_reference_id IS NULL (external document received)
     * 
     * Timeline fields:
     * - sent_at: When we sent our document (outbound only)
     * - received_at: When we received external document OR response to our document
     * 
     * OUTBOUND Status Values (InvoiceSendingNotificationRO):
     *   - QUEUED: Waiting to be sent to the Buyer
     *   - SENT: Acknowledged by the receiver Access Point C3
     *   - SEND_FAILED: Failed to send to the receiver's Access Point C3
     * 
     * OUTBOUND MLR Status (MLRReceivingNotificationRO):
     *   - TECHNICAL_ACCEPTANCE: Received by Buyer's Access Point, not yet read
     *   - REJECTED: MLR reject due to validation errors
     * 
     * INBOUND Status (InvoiceReceivingNotificationRO):
     *   - received: Document received from external party (our internal status)
     * 
     * OUTBOUND Response Status (InvoiceResponseReceivingNotificationRO):
     *   - BUYER_ACKNOWLEDGE: Buyer received readable invoice, submitted for processing
     *   - IN_PROCESS: Invoice processing started in Buyer's system
     *   - UNDER_QUERY: Buyer needs additional information before accepting
     *   - CONDITIONALLY_ACCEPTED: Buyer accepts under conditions in Status Reason
     *   - REJECTED: Buyer rejects invoice (won't process further)
     *   - ACCEPTED: Buyer gives final approval, next step is payment
     *   - PARTIALLY_PAID: Buyer initiated partial payment
     *   - FULLY_PAID: Buyer initiated full payment
     * 
     * SIMPLIFIED APPROACH:
     * - Use API status codes as-is in database
     * - Use display function for colored/formatted output
     * - Only track 'received' for inbound documents initially
     * 
     * EXPENSE CREATION RULES:
     *   - Invoices: local_reference_id IS NULL AND status = 'FULLY_PAID' (we received and paid invoice)
     *   - Credit Notes: local_reference_id IS NULL AND status = 'ACCEPTED' (we received and accepted credit note)
     */

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get PEPPOL document by type and document ID
     */
    public function get_peppol_document($document_type, $document_id)
    {
        return $this->db->where('document_type', $document_type)
            ->where('local_reference_id', $document_id)
            ->get(db_prefix() . 'peppol_documents')
            ->row();
    }

    /**
     * Get PEPPOL document by provider document ID
     */
    public function get_peppol_document_by_provider_id($provider_document_id, $provider = null)
    {
        $this->db->where('provider_document_id', $provider_document_id);

        if ($provider !== null) {
            $this->db->where('provider', $provider);
        }

        return $this->db->get(db_prefix() . 'peppol_documents')->row();
    }

    /**
     * Get PEPPOL document by transmission ID
     * This is useful when processing notifications that include transmission ID
     */
    public function get_peppol_document_by_transmission_id($transmission_id, $provider = null)
    {
        $this->db->where('provider_document_transmission_id', $transmission_id);

        if ($provider !== null) {
            $this->db->where('provider', $provider);
        }

        return $this->db->get(db_prefix() . 'peppol_documents')->row();
    }

    /**
     * Find PEPPOL document by searching in metadata
     * 
     * @param string $key The metadata key to search for
     * @param mixed $value The value to search for
     * @param string $provider Optional provider filter
     * @return object|null The first matching document or null
     */
    public function get_peppol_document_by_metadata($key, $value, $provider = null)
    {
        // Use JSON_EXTRACT to search in the metadata
        $this->db->where("JSON_UNQUOTE(JSON_EXTRACT(provider_metadata, '$.$key')) =", $value);

        if ($provider !== null) {
            $this->db->where('provider', $provider);
        }

        return $this->db->get(db_prefix() . 'peppol_documents')->row();
    }


    /**
     * Create PEPPOL document record
     */
    public function create_peppol_document($data)
    {
        $this->db->insert(db_prefix() . 'peppol_documents', $data);
        return $this->db->insert_id();
    }


    /**
     * Update PEPPOL document
     */
    public function update_peppol_document($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'peppol_documents', $data);
    }

    /**
     * Get documents by provider
     */
    public function get_documents_by_provider($provider_id, $limit = null, $offset = null)
    {
        $this->db->where('provider', $provider_id);
        $this->db->order_by('created_at', 'DESC');

        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get(db_prefix() . 'peppol_documents')->result();
    }

    /**
     * Get provider metadata for a document
     */
    public function get_provider_metadata($document_type, $document_id)
    {
        $this->db->select('provider_metadata');
        $this->db->where('document_type', $document_type);
        $this->db->where('local_reference_id', $document_id);
        $result = $this->db->get(db_prefix() . 'peppol_documents')->row();

        if ($result && !empty($result->provider_metadata)) {
            return json_decode($result->provider_metadata, true);
        }

        return null;
    }

    /**
     * Update provider metadata for a document
     */
    public function update_provider_metadata($document_type, $document_id, $metadata)
    {
        $existing = $this->get_peppol_document($document_type, $document_id);
        if ($existing) {
            // Merge with existing metadata
            $current_metadata = json_decode($existing->provider_metadata ?? '{}', true);
            $updated_metadata = array_merge($current_metadata, $metadata);

            $this->db->where('document_type', $document_type);
            $this->db->where('local_reference_id', $document_id);
            return $this->db->update(db_prefix() . 'peppol_documents', [
                'provider_metadata' => json_encode($updated_metadata),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        return false;
    }


    /**
     * Log PEPPOL activity
     */
    public function log_activity($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'peppol_logs', $data);
        return $this->db->insert_id();
    }

    // ================================
    // BULK OPERATION QUERY METHODS
    // ================================

    /**
     * Count documents for bulk action statistics
     */
    public function count_documents_for_action($document_type, $action, $client_id = null)
    {
        $table_map = [
            'invoice' => 'invoices',
            'credit_note' => 'creditnotes'
        ];

        $table = $table_map[$document_type];

        switch ($action) {
            case 'send_unsent':
                // Count documents that don't have PEPPOL records (using subquery - no JOIN)
                $this->db->select("COUNT(d.id) as count");
                $this->db->from(db_prefix() . $table . ' d');
                $this->db->where("d.id NOT IN (
                    SELECT COALESCE(local_reference_id, 0) 
                    FROM " . db_prefix() . "peppol_documents 
                    WHERE document_type = " . $this->db->escape($document_type) . " 
                    AND local_reference_id IS NOT NULL
                )");

                if ($document_type === 'invoice') {
                    $this->db->where_in('d.status', [Invoices_model::STATUS_UNPAID, Invoices_model::STATUS_PAID, Invoices_model::STATUS_OVERDUE]);
                } else {
                    $this->db->where('d.status >=', 1);
                }

                if ($client_id) {
                    $this->db->where('d.clientid', $client_id);
                }
                break;

            case 'retry_failed':
                // Count documents with 'failed' status (using subquery - no JOIN)
                $this->db->select('COUNT(*) as count');
                $this->db->from(db_prefix() . 'peppol_documents pd');
                $this->db->where('pd.document_type', $document_type);
                $this->db->where('pd.status', 'failed');

                if ($client_id) {
                    $this->db->where("pd.local_reference_id IN (
                        SELECT id FROM " . db_prefix() . $table . " 
                        WHERE clientid = " . (int)$client_id . "
                    )");
                }
                break;

            case 'download_sent':
                // Count documents with 'sent' or 'delivered' status (using subquery - no JOIN)
                $this->db->select('COUNT(*) as count');
                $this->db->from(db_prefix() . 'peppol_documents pd');
                $this->db->where('pd.document_type', $document_type);
                $this->db->where_in('pd.status', ['sent', 'delivered']);

                if ($client_id) {
                    $this->db->where("pd.local_reference_id IN (
                        SELECT id FROM " . db_prefix() . $table . " 
                        WHERE clientid = " . (int)$client_id . "
                    )");
                }
                break;

            case 'download_all_ubl':
                // Count all valid documents (sent and unsent)
                $this->db->select("COUNT(d.id) as count");
                $this->db->from(db_prefix() . $table . ' d');

                if ($document_type === 'invoice') {
                    $this->db->where_in('d.status', [Invoices_model::STATUS_UNPAID, Invoices_model::STATUS_PAID, Invoices_model::STATUS_OVERDUE]);
                } else {
                    $this->db->where('d.status >=', 1);
                }

                if ($client_id) {
                    $this->db->where('d.clientid', $client_id);
                }
                break;

            default:
                return 0;
        }

        $result = $this->db->get()->row();
        return (int)$result->count;
    }

    /**
     * Get document IDs for bulk operations
     */
    public function get_document_ids_for_action($document_type, $action, $client_id = null)
    {
        $table_map = [
            'invoice' => 'invoices',
            'credit_note' => 'creditnotes'
        ];

        $table = $table_map[$document_type];

        switch ($action) {
            case 'send_unsent':
                // Get documents without PEPPOL records (using subquery - no JOIN)
                $this->db->select('d.id');
                $this->db->from(db_prefix() . $table . ' d');
                $this->db->where("d.id NOT IN (
                    SELECT COALESCE(local_reference_id, 0) 
                    FROM " . db_prefix() . "peppol_documents 
                    WHERE document_type = " . $this->db->escape($document_type) . " 
                    AND local_reference_id IS NOT NULL
                )");

                if ($document_type === 'invoice') {
                    $this->db->where_in('d.status', [Invoices_model::STATUS_UNPAID, Invoices_model::STATUS_PAID, Invoices_model::STATUS_OVERDUE]);
                } else {
                    $this->db->where('d.status >=', 1);
                }

                if ($client_id) {
                    $this->db->where('d.clientid', $client_id);
                }

                $results = $this->db->get()->result();
                return array_column($results, 'id');

            case 'retry_failed':
                // Get failed PEPPOL documents
                $this->db->select('pd.local_reference_id');
                $this->db->from(db_prefix() . 'peppol_documents pd');
                $this->db->where('pd.document_type', $document_type);
                $this->db->where('pd.status', 'failed');

                if ($client_id) {
                    $this->db->join(db_prefix() . $table . ' d', 'd.id = pd.local_reference_id');
                    $this->db->where('d.clientid', $client_id);
                }

                $results = $this->db->get()->result();
                return array_column($results, 'local_reference_id');

            default:
                return [];
        }
    }

    /**
     * Get documents by status for a document type
     */
    public function get_documents_by_status($document_type, $status)
    {
        return $this->db->where('document_type', $document_type)
            ->where('status', $status)
            ->get(db_prefix() . 'peppol_documents')
            ->result();
    }

    /**
     * Get documents by multiple statuses for a document type
     */
    public function get_documents_by_statuses($document_type, $statuses)
    {
        return $this->db->where('document_type', $document_type)
            ->where_in('status', $statuses)
            ->get(db_prefix() . 'peppol_documents')
            ->result();
    }

    /**
     * Get all PEPPOL documents for a document type
     */
    public function get_all_documents($document_type, $limit = null, $offset = 0)
    {
        $this->db->where('document_type', $document_type);
        $this->db->order_by('created_at', 'DESC');

        if ($limit) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get(db_prefix() . 'peppol_documents')->result();
    }

    /**
     * Get statistics for a document type
     */
    public function get_document_statistics($document_type)
    {
        // Count by status
        $this->db->select('status, COUNT(*) as count');
        $this->db->from(db_prefix() . 'peppol_documents');
        $this->db->where('document_type', $document_type);
        $this->db->group_by('status');
        $status_counts = $this->db->get()->result();


        // Initialize stats with API status codes only
        $stats = [
            'QUEUED' => 0,
            'SENT' => 0,
            'SEND_FAILED' => 0,
            'TECHNICAL_ACCEPTANCE' => 0,
            'BUYER_ACKNOWLEDGE' => 0,
            'IN_PROCESS' => 0,
            'UNDER_QUERY' => 0,
            'CONDITIONALLY_ACCEPTED' => 0,
            'ACCEPTED' => 0,
            'PARTIALLY_PAID' => 0,
            'FULLY_PAID' => 0,
            'REJECTED' => 0,
            'RECEIVED' => 0,
            'total_processed' => 0
        ];

        foreach ($status_counts as $row) {
            $status = $row->status;
            $count = $row->count;

            // Use status directly - all should be API codes
            if (isset($stats[$status])) {
                $stats[$status] = $count;
            } else {
                // For any unknown statuses, add them dynamically
                $stats[$status] = $count;
            }

            $stats['total_processed'] += $count;
        }

        // Calculate total as only PEPPOL documents (not including unsent)
        $stats['total'] = $stats['total_processed'];

        return $stats;
    }

    /**
     * Get latest error logs for a specific document
     */
    public function get_document_errors($document_type, $document_id, $limit = 5)
    {
        $this->db->select('message, data, created_at');
        $this->db->from(db_prefix() . 'peppol_logs');
        $this->db->where('document_type', $document_type);
        $this->db->where('local_reference_id', $document_id);
        $this->db->where('type', 'error');
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    /**
     * Get PEPPOL document by ID with optional metadata decoding
     * 
     * @param int $id Document ID
     * @param bool $decode_metadata Whether to decode JSON metadata (default: true)
     * @return object|null Document object with decoded metadata or null if not found
     */
    public function get_peppol_document_by_id($id, $decode_metadata = true)
    {
        $this->db->where('id', $id);
        $document = $this->db->get(db_prefix() . 'peppol_documents')->row();

        if ($document && $decode_metadata) {
            $document->metadata = (array)json_decode($document->provider_metadata ?? '', true);
        }

        return $document;
    }

    /**
     * Get PEPPOL documents that have associated expenses
     * 
     * @param int|null $limit Limit number of results
     * @param int $offset Offset for pagination
     * @return array Array of documents with expense information
     */
    public function get_documents_with_expenses($limit = null, $offset = 0)
    {
        $this->db->select('pd.*, e.expense_name, e.amount as expense_amount, e.date as expense_date');
        $this->db->from(db_prefix() . 'peppol_documents pd');
        $this->db->join(db_prefix() . 'expenses e', 'e.id = pd.expense_id', 'inner');
        $this->db->where('pd.expense_id IS NOT NULL');
        $this->db->order_by('pd.created_at', 'DESC');

        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result();
    }

    /**
     * Get count of PEPPOL documents that have been converted to expenses
     * 
     * @return int Count of documents with expenses
     */
    public function count_documents_with_expenses()
    {
        $this->db->where('expense_id IS NOT NULL');
        return $this->db->count_all_results(db_prefix() . 'peppol_documents');
    }

    /**
     * Get expense statistics for PEPPOL documents
     * 
     * @return array Statistics including counts and total amounts for expenses
     */
    public function get_expense_statistics()
    {
        $stats = [
            'total_expenses' => 0,
            'invoice_expenses' => 0,
            'credit_note_expenses' => 0,
            'total_amount' => 0,
            'invoice_amount' => 0,
            'credit_note_amount' => 0
        ];

        // Get count and amount for all expenses
        $this->db->select('
            COUNT(pd.expense_id) as total_expenses,
            SUM(CASE WHEN e.amount IS NOT NULL THEN e.amount ELSE 0 END) as total_amount
        ');
        $this->db->from(db_prefix() . 'peppol_documents pd');
        $this->db->join(db_prefix() . 'expenses e', 'e.id = pd.expense_id', 'left');
        $this->db->where('pd.expense_id IS NOT NULL');

        $result = $this->db->get()->row();
        if ($result) {
            $stats['total_expenses'] = (int)$result->total_expenses;
            $stats['total_amount'] = (float)$result->total_amount;
        }

        // Get count and amount for invoice expenses
        $this->db->select('
            COUNT(pd.expense_id) as invoice_expenses,
            SUM(CASE WHEN e.amount IS NOT NULL THEN e.amount ELSE 0 END) as invoice_amount
        ');
        $this->db->from(db_prefix() . 'peppol_documents pd');
        $this->db->join(db_prefix() . 'expenses e', 'e.id = pd.expense_id', 'left');
        $this->db->where('pd.document_type', 'invoice');
        $this->db->where('pd.expense_id IS NOT NULL');

        $result = $this->db->get()->row();
        if ($result) {
            $stats['invoice_expenses'] = (int)$result->invoice_expenses;
            $stats['invoice_amount'] = (float)$result->invoice_amount;
        }

        // Get count and amount for credit note expenses
        $this->db->select('
            COUNT(pd.expense_id) as credit_note_expenses,
            SUM(CASE WHEN e.amount IS NOT NULL THEN e.amount ELSE 0 END) as credit_note_amount
        ');
        $this->db->from(db_prefix() . 'peppol_documents pd');
        $this->db->join(db_prefix() . 'expenses e', 'e.id = pd.expense_id', 'left');
        $this->db->where('pd.document_type', 'credit_note');
        $this->db->where('pd.expense_id IS NOT NULL');

        $result = $this->db->get()->row();
        if ($result) {
            $stats['credit_note_expenses'] = (int)$result->credit_note_expenses;
            $stats['credit_note_amount'] = (float)$result->credit_note_amount;
        }

        return $stats;
    }

    /**
     * Get documents that can be converted to expenses by document type
     * 
     * @param string $document_type Document type (invoice or credit_note)
     * @return array Statistics for documents ready for expense conversion
     */
    public function get_expense_eligible_statistics($document_type)
    {
        $stats = [
            'eligible_count' => 0,
            'already_converted' => 0,
            'not_eligible' => 0
        ];

        $table_name = db_prefix() . 'peppol_documents';

        // Count documents already converted to expenses
        $this->db->where('document_type', $document_type);
        $this->db->where('expense_id IS NOT NULL');
        $stats['already_converted'] = $this->db->count_all_results($table_name);

        if ($document_type === 'invoice') {
            // For invoices: eligible are inbound (local_reference_id IS NULL) with 'FULLY_PAID' status
            $this->db->where('document_type', 'invoice');
            $this->db->where('local_reference_id IS NULL'); // Inbound direction
            $this->db->where('status', 'FULLY_PAID'); // API status for paid
            $this->db->where('expense_id IS NULL');
            $stats['eligible_count'] = $this->db->count_all_results($table_name);

            // Count not eligible (inbound but not paid)
            $this->db->where('document_type', 'invoice');
            $this->db->where('local_reference_id IS NULL'); // Inbound direction
            $this->db->where('status !=', 'FULLY_PAID'); // Not paid
            $this->db->where('expense_id IS NULL');
            $stats['not_eligible'] = $this->db->count_all_results($table_name);
        } else {
            // For credit notes: eligible are inbound with 'ACCEPTED' status
            $this->db->where('document_type', 'credit_note');
            $this->db->where('local_reference_id IS NULL'); // Inbound direction
            $this->db->where('status', 'ACCEPTED'); // API status for accepted
            $this->db->where('expense_id IS NULL');
            $stats['eligible_count'] = $this->db->count_all_results($table_name);

            // Count not eligible (inbound but not accepted)
            $this->db->where('document_type', 'credit_note');
            $this->db->where('local_reference_id IS NULL'); // Inbound direction
            $this->db->where('status !=', 'ACCEPTED'); // Not accepted
            $this->db->where('expense_id IS NULL');
            $stats['not_eligible'] = $this->db->count_all_results($table_name);
        }

        return $stats;
    }

    /**
     * Get formatted display information for PEPPOL document status
     * 
     * @param string $status API status code
     * @return array Display information with label, color class, and description
     */
    public function get_status_display($status)
    {
        // Outbound statuses (our documents sent to others)
        $outbound_statuses = [
            'QUEUED' => [
                'label' => 'Queued',
                'color' => 'label-warning',
                'description' => 'Waiting to be sent to the Buyer'
            ],
            'SENT' => [
                'label' => 'Sent',
                'color' => 'label-primary',
                'description' => 'Acknowledged by receiver Access Point'
            ],
            'SEND_FAILED' => [
                'label' => 'Send Failed',
                'color' => 'label-danger',
                'description' => 'Failed to send to receiver\'s Access Point'
            ],
            'TECHNICAL_ACCEPTANCE' => [
                'label' => 'Delivered',
                'color' => 'label-info',
                'description' => 'Received by Buyer\'s Access Point, not yet read'
            ],
            // Response statuses for our outbound documents
            'BUYER_ACKNOWLEDGE' => [
                'label' => 'Acknowledged',
                'color' => 'label-info',
                'description' => 'Buyer received and submitted for processing'
            ],
            'IN_PROCESS' => [
                'label' => 'In Process',
                'color' => 'label-warning',
                'description' => 'Processing started in Buyer\'s system'
            ],
            'UNDER_QUERY' => [
                'label' => 'Under Query',
                'color' => 'label-warning',
                'description' => 'Buyer needs additional information'
            ],
            'CONDITIONALLY_ACCEPTED' => [
                'label' => 'Conditionally Accepted',
                'color' => 'label-warning',
                'description' => 'Accepted under stated conditions'
            ],
            'ACCEPTED' => [
                'label' => 'Accepted',
                'color' => 'label-success',
                'description' => 'Final approval given, next step is payment'
            ],
            'PARTIALLY_PAID' => [
                'label' => 'Partially Paid',
                'color' => 'label-warning',
                'description' => 'Partial payment initiated'
            ],
            'FULLY_PAID' => [
                'label' => 'Fully Paid',
                'color' => 'label-success',
                'description' => 'Full payment initiated'
            ],
            'REJECTED' => [
                'label' => 'Rejected',
                'color' => 'label-danger',
                'description' => 'Document rejected'
            ]
        ];

        // Inbound statuses (documents received from others)
        $inbound_statuses = [
            'received' => [
                'label' => 'Received',
                'color' => 'label-info',
                'description' => 'Document received from external party'
            ],
            // When we respond to received documents
            'ACCEPTED' => [
                'label' => 'Accepted',
                'color' => 'label-success',
                'description' => 'We accepted the received document'
            ],
            'FULLY_PAID' => [
                'label' => 'Paid',
                'color' => 'label-success',
                'description' => 'We paid the received invoice'
            ],
            'REJECTED' => [
                'label' => 'Rejected',
                'color' => 'label-danger',
                'description' => 'We rejected the received document'
            ]
        ];

        // Default fallback
        $default = [
            'label' => ucfirst(strtolower(str_replace('_', ' ', $status))),
            'color' => 'label-default',
            'description' => 'Unknown status'
        ];

        // Try outbound statuses first, then inbound, then default
        return $outbound_statuses[$status] ?? $inbound_statuses[$status] ?? $default;
    }

    /**
     * Map legacy PEPPOL codes to API status codes (for backward compatibility)
     * 
     * @param string $legacy_code Legacy PEPPOL code (PD, AP, AB, RE, etc.)
     * @return string API status code
     */
    public function map_legacy_peppol_to_api_status($legacy_code)
    {
        $mapping = [
            'PD' => 'FULLY_PAID',
            'AP' => 'ACCEPTED',
            'AB' => 'BUYER_ACKNOWLEDGE',
            'RE' => 'REJECTED',
            'IP' => 'IN_PROCESS',
        ];

        return $mapping[$legacy_code] ?? $legacy_code;
    }

    /**
     * Check if document is inbound (received from external party)
     * 
     * @param object $document PEPPOL document
     * @return bool True if inbound, false if outbound
     */
    public function is_inbound_document($document)
    {
        return empty($document->local_reference_id);
    }

    /**
     * Check if document is outbound (sent to external party)
     * 
     * @param object $document PEPPOL document
     * @return bool True if outbound, false if inbound
     */
    public function is_outbound_document($document)
    {
        return !empty($document->local_reference_id);
    }

    /**
     * Get error summary for failed documents in bulk operations
     */
    public function get_bulk_operation_errors($document_type, $document_ids)
    {
        if (empty($document_ids)) {
            return [];
        }

        $this->db->select('local_reference_id, message, data, created_at');
        $this->db->from(db_prefix() . 'peppol_logs');
        $this->db->where('document_type', $document_type);
        $this->db->where_in('local_reference_id', $document_ids);
        $this->db->where('type', 'error');
        $this->db->order_by('local_reference_id, created_at DESC');

        $results = $this->db->get()->result();

        // Group by local_reference_id and return latest error for each
        $errors = [];
        foreach ($results as $log) {
            if (!isset($errors[$log->local_reference_id])) {
                $errors[$log->local_reference_id] = $log;
            }
        }

        return $errors;
    }
}