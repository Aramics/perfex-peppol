<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Cron Job Functions
 * Add these to your main cron job or call them from Perfex CRM's cron system
 */

if (!function_exists('peppol_process_pending_invoices')) {
    /**
     * Process pending PEPPOL invoices
     */
    function peppol_process_pending_invoices()
    {
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_service');
        $CI->load->model('peppol/peppol_model');
        
        if (!is_peppol_configured()) {
            log_message('info', 'PEPPOL cron: Module not configured, skipping');
            return;
        }
        
        try {
            $processed = $CI->peppol_service->process_pending_invoices();
            
            if ($processed > 0) {
                log_message('info', 'PEPPOL cron: Processed ' . $processed . ' pending invoices');
            }
            
            $CI->peppol_model->log_activity([
                'provider' => get_active_peppol_provider(),
                'action' => 'cron_process_pending',
                'status' => 'info',
                'message' => 'Processed ' . $processed . ' pending invoices'
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'PEPPOL cron error: ' . $e->getMessage());
            
            $CI->peppol_model->log_activity([
                'provider' => get_active_peppol_provider(),
                'action' => 'cron_error',
                'status' => 'error',
                'message' => 'Cron job failed: ' . $e->getMessage()
            ]);
        }
    }
}

if (!function_exists('peppol_process_received_documents')) {
    /**
     * Process unprocessed received documents
     */
    function peppol_process_received_documents()
    {
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_service');
        $CI->load->model('peppol/peppol_model');
        
        if (!is_peppol_configured()) {
            return;
        }
        
        try {
            $unprocessed = $CI->peppol_model->get_unprocessed_documents();
            $processed_count = 0;
            
            foreach ($unprocessed as $document) {
                $result = $CI->peppol_service->process_received_document($document->id);
                
                if ($result['success']) {
                    $processed_count++;
                }
            }
            
            if ($processed_count > 0) {
                log_message('info', 'PEPPOL cron: Processed ' . $processed_count . ' received documents');
            }
            
            $CI->peppol_model->log_activity([
                'provider' => get_active_peppol_provider(),
                'action' => 'cron_process_received',
                'status' => 'info',
                'message' => 'Processed ' . $processed_count . ' received documents'
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'PEPPOL received documents cron error: ' . $e->getMessage());
            
            $CI->peppol_model->log_activity([
                'provider' => get_active_peppol_provider(),
                'action' => 'cron_received_error',
                'status' => 'error',
                'message' => 'Received documents cron failed: ' . $e->getMessage()
            ]);
        }
    }
}

if (!function_exists('peppol_update_delivery_status')) {
    /**
     * Update delivery status for sent invoices
     */
    function peppol_update_delivery_status()
    {
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_service');
        $CI->load->model('peppol/peppol_model');
        
        if (!is_peppol_configured()) {
            return;
        }
        
        try {
            // Get invoices that are sent but not yet delivered
            $sent_invoices = $CI->peppol_model->get_peppol_invoices(['status' => 'sent']);
            $updated_count = 0;
            
            foreach ($sent_invoices as $peppol_invoice) {
                if (!$peppol_invoice->peppol_document_id) {
                    continue;
                }
                
                $status_result = $CI->peppol_service->get_delivery_status($peppol_invoice->id);
                
                if ($status_result['success'] && $status_result['status'] != $peppol_invoice->status) {
                    $updated_count++;
                }
                
                // Add delay to avoid overwhelming the API
                usleep(200000); // 0.2 seconds
            }
            
            if ($updated_count > 0) {
                log_message('info', 'PEPPOL cron: Updated status for ' . $updated_count . ' invoices');
            }
            
            $CI->peppol_model->log_activity([
                'provider' => get_active_peppol_provider(),
                'action' => 'cron_status_update',
                'status' => 'info',
                'message' => 'Updated status for ' . $updated_count . ' invoices'
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'PEPPOL status update cron error: ' . $e->getMessage());
            
            $CI->peppol_model->log_activity([
                'provider' => get_active_peppol_provider(),
                'action' => 'cron_status_error',
                'status' => 'error',
                'message' => 'Status update cron failed: ' . $e->getMessage()
            ]);
        }
    }
}

if (!function_exists('peppol_clean_old_logs')) {
    /**
     * Clean old logs
     */
    function peppol_clean_old_logs()
    {
        $CI = &get_instance();
        $CI->load->model('peppol/peppol_model');
        
        try {
            $days = get_option('peppol_log_retention_days', 90);
            $deleted = $CI->peppol_model->clean_old_logs($days);
            
            if ($deleted > 0) {
                log_message('info', 'PEPPOL cron: Cleaned ' . $deleted . ' old log entries');
            }
            
        } catch (Exception $e) {
            log_message('error', 'PEPPOL log cleanup cron error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('run_peppol_cron')) {
    /**
     * Main PEPPOL cron function - call this from your main cron job
     */
    function run_peppol_cron()
    {
        log_message('info', 'PEPPOL cron: Starting PEPPOL cron jobs');
        
        // Process pending invoices
        peppol_process_pending_invoices();
        
        // Process received documents (if auto-processing is disabled)
        if (get_option('peppol_auto_process_received') != '1') {
            peppol_process_received_documents();
        }
        
        // Update delivery status
        peppol_update_delivery_status();
        
        // Clean old logs (run once per day)
        if (date('H') == '02') { // Run at 2 AM
            peppol_clean_old_logs();
        }
        
        log_message('info', 'PEPPOL cron: Completed PEPPOL cron jobs');
    }
}