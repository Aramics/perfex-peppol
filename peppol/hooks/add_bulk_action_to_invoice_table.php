<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Add PEPPOL Status column to the invoice table
hooks()->add_filter('invoices_table_columns', function ($table_data) {
    // Add PEPPOL Status as a new column
    $table_data[] = _l('peppol_status');
    return $table_data;
});

hooks()->add_filter('invoices_table_row_data', function ($row, $aRow = []) {
    $CI = &get_instance();
    $CI->load->helper(PEPPOL_MODULE_NAME . '/' . PEPPOL_MODULE_NAME);
    $CI->load->model('peppol/peppol_model');
    
    // Get PEPPOL status for this invoice
    $peppol_invoice = $CI->peppol_model->get_peppol_invoice_by_invoice($aRow['id']);
    
    $status_html = '';
    
    if (function_exists('render_peppol_status_column')) {
        if ($peppol_invoice) {
            $status = $peppol_invoice->status;
            $status_html = render_peppol_status_column($status, $aRow['id'], $peppol_invoice);
        } else {
            // No PEPPOL record exists - show send action
            $status_html = render_peppol_status_column(null, $aRow['id']);
        }
    } else {
        // Fallback if function not available
        $status_html = '<span class="text-muted">-</span>';
    }
    
    $row[] = $status_html;
    return $row;
}, 10, 2);

/**
 * Register admin footer hook
 */
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();
    $CI->load->view(PEPPOL_MODULE_NAME . '/scripts/multiple_invoice_action');
});