<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hook to handle expense deletion cleanup for PEPPOL documents
 * 
 * When an expense is deleted, this hook will automatically clear the expense_id
 * from any PEPPOL documents that were linked to it. This ensures data integrity
 * and allows users to recreate expenses from the same PEPPOL document if needed.
 */

hooks()->add_action('after_expense_deleted', 'peppol_cleanup_deleted_expense');

/**
 * Clean up PEPPOL document references when an expense is deleted
 * 
 * @param int $expense_id The ID of the deleted expense
 */
function peppol_cleanup_deleted_expense($expense_id)
{
    $CI = &get_instance();
    
    try {
        // Update any PEPPOL documents that reference this deleted expense
        $CI->db->where('expense_id', $expense_id);
        $affected_rows = $CI->db->update(db_prefix() . 'peppol_documents', [
            'expense_id' => null
        ]);
        
        // Log the cleanup activity for audit trail
        if ($affected_rows > 0) {
            $CI->load->model('peppol/peppol_model');
            $CI->peppol_model->log_activity([
                'type' => 'expense_cleanup',
                'message' => "Cleared expense_id reference from {$affected_rows} PEPPOL document(s) after expense #{$expense_id} was deleted",
                'data' => json_encode([
                    'deleted_expense_id' => $expense_id,
                    'affected_documents' => $affected_rows,
                    'cleanup_date' => date('Y-m-d H:i:s')
                ]),
                'staff_id' => get_staff_user_id()
            ]);
            
            log_activity("PEPPOL: Cleared expense references from {$affected_rows} document(s) after expense #{$expense_id} deletion");
        }
    } catch (Exception $e) {
        // Log any errors that occur during cleanup
        log_activity("PEPPOL: Error during expense cleanup for expense #{$expense_id}: " . $e->getMessage());
        
        // Optionally, you could also log to PEPPOL logs if the model is available
        if (isset($CI->peppol_model) || $CI->load->model('peppol/peppol_model')) {
            $CI->peppol_model->log_activity([
                'type' => 'error',
                'message' => "Failed to cleanup expense references after expense #{$expense_id} deletion: " . $e->getMessage(),
                'data' => json_encode([
                    'deleted_expense_id' => $expense_id,
                    'error' => $e->getMessage(),
                    'error_date' => date('Y-m-d H:i:s')
                ]),
                'staff_id' => get_staff_user_id()
            ]);
        }
    }
}