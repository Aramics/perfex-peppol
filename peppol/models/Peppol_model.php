<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get PEPPOL invoices
     */
    public function get_peppol_invoices($where = [])
    {
        $this->db->select('pi.*, i.number as invoice_number, i.total, i.status as invoice_status, 
                          c.company as client_name, CONCAT(c.firstname, " ", c.lastname) as contact_name');
        $this->db->from(db_prefix() . 'peppol_invoices pi');
        $this->db->join(db_prefix() . 'invoices i', 'i.id = pi.invoice_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = i.clientid', 'left');
        
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        $this->db->order_by('pi.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Get single PEPPOL invoice
     */
    public function get_peppol_invoice($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'peppol_invoices')->row();
    }

    /**
     * Get PEPPOL invoice by invoice ID and provider
     */
    public function get_peppol_invoice_by_invoice($invoice_id, $provider = null)
    {
        $this->db->where('invoice_id', $invoice_id);
        
        if ($provider) {
            $this->db->where('provider', $provider);
        }
        
        return $this->db->get(db_prefix() . 'peppol_invoices')->row();
    }

    /**
     * Create or update PEPPOL invoice record
     */
    public function save_peppol_invoice($data)
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'peppol_invoices', $data);
            
            return $id;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->insert(db_prefix() . 'peppol_invoices', $data);
            
            return $this->db->insert_id();
        }
    }

    /**
     * Update PEPPOL invoice status
     */
    public function update_peppol_invoice_status($id, $status, $additional_data = [])
    {
        $data = array_merge(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], $additional_data);
        
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'peppol_invoices', $data);
    }

    /**
     * Queue invoice for sending
     */
    public function queue_invoice_for_sending($invoice_id)
    {
        $provider = get_active_peppol_provider();
        
        // Check if already exists
        $existing = $this->get_peppol_invoice_by_invoice($invoice_id, $provider);
        
        if ($existing) {
            // Update if in failed state
            if ($existing->status == 'failed') {
                $this->update_peppol_invoice_status($existing->id, 'pending');
            }
            return $existing->id;
        }
        
        // Create new record
        return $this->save_peppol_invoice([
            'invoice_id' => $invoice_id,
            'provider' => $provider,
            'status' => 'pending'
        ]);
    }

    /**
     * Get received documents
     */
    public function get_received_documents($where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        $this->db->order_by('received_at', 'DESC');
        
        return $this->db->get(db_prefix() . 'peppol_received_documents')->result();
    }

    /**
     * Get single received document
     */
    public function get_received_document($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'peppol_received_documents')->row();
    }

    /**
     * Save received document
     */
    public function save_received_document($data)
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'peppol_received_documents', $data);
            
            return $id;
        } else {
            $data['received_at'] = date('Y-m-d H:i:s');
            
            $this->db->insert(db_prefix() . 'peppol_received_documents', $data);
            
            return $this->db->insert_id();
        }
    }

    /**
     * Mark received document as processed
     */
    public function mark_document_processed($id, $invoice_id = null, $error_message = null)
    {
        $data = [
            'processed' => 1,
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($invoice_id) {
            $data['invoice_id'] = $invoice_id;
        }
        
        if ($error_message) {
            $data['error_message'] = $error_message;
        }
        
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'peppol_received_documents', $data);
    }

    /**
     * Get PEPPOL logs
     */
    public function get_logs($where = [])
    {
        $this->db->select('pl.*, i.number as invoice_number');
        $this->db->from(db_prefix() . 'peppol_logs pl');
        $this->db->join(db_prefix() . 'invoices i', 'i.id = pl.invoice_id', 'left');
        
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        $this->db->order_by('pl.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Log PEPPOL activity
     */
    public function log_activity($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert(db_prefix() . 'peppol_logs', $data);
    }

    /**
     * Log invoice event
     */
    public function log_invoice_event($invoice_id, $action, $message, $status = 'info', $additional_data = [])
    {
        $data = array_merge([
            'invoice_id' => $invoice_id,
            'provider' => get_active_peppol_provider(),
            'action' => $action,
            'status' => $status,
            'message' => $message
        ], $additional_data);
        
        return $this->log_activity($data);
    }

    /**
     * Get pending invoices for sending
     */
    public function get_pending_invoices()
    {
        $this->db->where('status', 'pending');
        $this->db->or_where('status', 'queued');
        
        return $this->db->get(db_prefix() . 'peppol_invoices')->result();
    }

    /**
     * Get unprocessed received documents
     */
    public function get_unprocessed_documents()
    {
        $this->db->where('processed', 0);
        
        return $this->db->get(db_prefix() . 'peppol_received_documents')->result();
    }

    /**
     * Get invoice statistics
     */
    public function get_invoice_statistics()
    {
        $stats = [];
        
        // Total sent invoices
        $this->db->where('status', 'sent');
        $this->db->or_where('status', 'delivered');
        $stats['total_sent'] = $this->db->count_all_results(db_prefix() . 'peppol_invoices');
        
        // Failed invoices
        $this->db->where('status', 'failed');
        $stats['total_failed'] = $this->db->count_all_results(db_prefix() . 'peppol_invoices');
        
        // Pending invoices
        $this->db->where('status', 'pending');
        $this->db->or_where('status', 'queued');
        $stats['total_pending'] = $this->db->count_all_results(db_prefix() . 'peppol_invoices');
        
        // Received documents
        $stats['total_received'] = $this->db->count_all_results(db_prefix() . 'peppol_received_documents');
        
        // Unprocessed documents
        $this->db->where('processed', 0);
        $stats['unprocessed_received'] = $this->db->count_all_results(db_prefix() . 'peppol_received_documents');
        
        return $stats;
    }

    /**
     * Clean old logs
     */
    public function clean_old_logs($days = 90)
    {
        $date = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        
        $this->db->where('created_at <', $date);
        return $this->db->delete(db_prefix() . 'peppol_logs');
    }

    /**
     * Delete PEPPOL invoice record
     */
    public function delete_peppol_invoice($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'peppol_invoices');
    }

    /**
     * Delete received document
     */
    public function delete_received_document($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'peppol_received_documents');
    }

    /**
     * Get PEPPOL invoice by document ID
     */
    public function get_peppol_invoice_by_document_id($document_id)
    {
        $this->db->where('peppol_document_id', $document_id);
        return $this->db->get(db_prefix() . 'peppol_invoices')->row();
    }
}