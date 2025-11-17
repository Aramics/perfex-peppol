<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_model extends App_Model
{
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
                       ->where('document_id', $document_id)
                       ->get(db_prefix() . 'peppol_documents')
                       ->row();
    }

    /**
     * Get PEPPOL invoice by invoice ID (legacy wrapper)
     */
    public function get_peppol_invoice_by_invoice($invoice_id)
    {
        return $this->get_peppol_document('invoice', $invoice_id);
    }

    /**
     * Get PEPPOL credit note by credit note ID (legacy wrapper)
     */
    public function get_peppol_credit_note_by_credit_note($credit_note_id)
    {
        return $this->get_peppol_document('credit_note', $credit_note_id);
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
     * Create PEPPOL invoice record (legacy wrapper)
     */
    public function create_peppol_invoice($data)
    {
        $data['document_type'] = 'invoice';
        $data['document_id'] = $data['invoice_id'];
        unset($data['invoice_id']);
        return $this->create_peppol_document($data);
    }

    /**
     * Create PEPPOL credit note record (legacy wrapper)
     */
    public function create_peppol_credit_note($data)
    {
        $data['document_type'] = 'credit_note';
        $data['document_id'] = $data['credit_note_id'];
        unset($data['credit_note_id']);
        return $this->create_peppol_document($data);
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
     * Update PEPPOL invoice (legacy wrapper)
     */
    public function update_peppol_invoice($id, $data)
    {
        return $this->update_peppol_document($id, $data);
    }

    /**
     * Update PEPPOL credit note (legacy wrapper)
     */
    public function update_peppol_credit_note($id, $data)
    {
        return $this->update_peppol_document($id, $data);
    }

    /**
     * Log PEPPOL activity
     */
    public function log_activity($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Handle legacy invoice_id/credit_note_id fields
        if (isset($data['invoice_id']) && !isset($data['document_type'])) {
            $data['document_type'] = 'invoice';
            $data['document_id'] = $data['invoice_id'];
            unset($data['invoice_id']);
        }
        
        if (isset($data['credit_note_id']) && !isset($data['document_type'])) {
            $data['document_type'] = 'credit_note';
            $data['document_id'] = $data['credit_note_id'];
            unset($data['credit_note_id']);
        }
        
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
                // Count documents that don't have PEPPOL records
                $this->db->select("COUNT(d.id) as count");
                $this->db->from(db_prefix() . $table . ' d');
                $this->db->join(db_prefix() . 'peppol_documents pd', 
                    "pd.document_id = d.id AND pd.document_type = '$document_type'", 'left');
                $this->db->where('pd.id IS NULL');
                
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
                // Count documents with 'failed' status
                $this->db->select('COUNT(*) as count');
                $this->db->from(db_prefix() . 'peppol_documents pd');
                $this->db->where('pd.document_type', $document_type);
                $this->db->where('pd.status', 'failed');
                
                if ($client_id) {
                    $this->db->join(db_prefix() . $table . ' d', 'd.id = pd.document_id');
                    $this->db->where('d.clientid', $client_id);
                }
                break;

            case 'download_sent':
                // Count documents with 'sent' or 'delivered' status
                $this->db->select('COUNT(*) as count');
                $this->db->from(db_prefix() . 'peppol_documents pd');
                $this->db->where('pd.document_type', $document_type);
                $this->db->where_in('pd.status', ['sent', 'delivered']);
                
                if ($client_id) {
                    $this->db->join(db_prefix() . $table . ' d', 'd.id = pd.document_id');
                    $this->db->where('d.clientid', $client_id);
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
                // Get documents without PEPPOL records
                $this->db->select('d.id');
                $this->db->from(db_prefix() . $table . ' d');
                $this->db->join(db_prefix() . 'peppol_documents pd', 
                    "pd.document_id = d.id AND pd.document_type = '$document_type'", 'left');
                $this->db->where('pd.id IS NULL');
                
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
                $this->db->select('pd.document_id');
                $this->db->from(db_prefix() . 'peppol_documents pd');
                $this->db->where('pd.document_type', $document_type);
                $this->db->where('pd.status', 'failed');
                
                if ($client_id) {
                    $this->db->join(db_prefix() . $table . ' d', 'd.id = pd.document_id');
                    $this->db->where('d.clientid', $client_id);
                }
                
                $results = $this->db->get()->result();
                return array_column($results, 'document_id');

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

        // Count total unsent (documents not in peppol_documents)
        $table_map = [
            'invoice' => 'invoices',
            'credit_note' => 'creditnotes'
        ];
        
        $table = $table_map[$document_type];
        $this->db->select("COUNT(d.id) as count");
        $this->db->from(db_prefix() . $table . ' d');
        $this->db->join(db_prefix() . 'peppol_documents pd', 
            "pd.document_id = d.id AND pd.document_type = '$document_type'", 'left');
        $this->db->where('pd.id IS NULL');
        
        if ($document_type === 'invoice') {
            $this->db->where_in('d.status', [Invoices_model::STATUS_UNPAID, Invoices_model::STATUS_PAID, Invoices_model::STATUS_OVERDUE]);
        } else {
            $this->db->where('d.status >=', 1);
        }
        
        $unsent_count = $this->db->get()->row()->count;

        // Format results
        $stats = [
            'unsent' => $unsent_count,
            'pending' => 0,
            'sent' => 0,
            'delivered' => 0,
            'failed' => 0,
            'total_processed' => 0
        ];

        foreach ($status_counts as $row) {
            $stats[$row->status] = $row->count;
            $stats['total_processed'] += $row->count;
        }

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
        $this->db->where('document_id', $document_id);
        $this->db->where('type', 'error');
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    /**
     * Get latest error for a specific document (one liner)
     */
    public function get_latest_document_error($document_type, $document_id)
    {
        $errors = $this->get_document_errors($document_type, $document_id, 1);
        return !empty($errors) ? $errors[0] : null;
    }

    /**
     * Get error summary for failed documents in bulk operations
     */
    public function get_bulk_operation_errors($document_type, $document_ids)
    {
        if (empty($document_ids)) {
            return [];
        }

        $this->db->select('document_id, message, data, created_at');
        $this->db->from(db_prefix() . 'peppol_logs');
        $this->db->where('document_type', $document_type);
        $this->db->where_in('document_id', $document_ids);
        $this->db->where('type', 'error');
        $this->db->order_by('document_id, created_at DESC');
        
        $results = $this->db->get()->result();
        
        // Group by document_id and return latest error for each
        $errors = [];
        foreach ($results as $log) {
            if (!isset($errors[$log->document_id])) {
                $errors[$log->document_id] = $log;
            }
        }
        
        return $errors;
    }
}