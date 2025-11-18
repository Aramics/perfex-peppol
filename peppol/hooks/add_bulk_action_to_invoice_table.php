<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Invoice Table Hooks - Simple bulk actions
 */

/**
 * Add PEPPOL status column to invoice table
 */
hooks()->add_filter('invoices_table_columns', function ($columns) {
    if (!staff_can('view', 'peppol')) {
        return $columns;
    }

    $columns[] = _l('peppol_status');
    return $columns;
});

/**
 * Add PEPPOL status data to invoice table rows
 */
hooks()->add_filter('invoices_table_row_data', function ($row, $aRow = []) {
    if (!staff_can('view', 'peppol')) {
        return $row;
    }

    $CI = &get_instance();
    $CI->load->model('peppol/peppol_model');

    $peppol_invoice = $CI->peppol_model->get_peppol_document('invoice', $aRow['id']);

    if ($peppol_invoice) {
        $status = $peppol_invoice->status;
        $row[] = render_peppol_status_column($aRow['id'], $status);
    } else {
        $row[] = render_peppol_status_column($aRow['id']);
    }

    return $row;
}, 10, 2);

/**
 * Add PEPPOL bulk actions to invoice list page
 */
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();

    // Show on invoice list page or client invoices page
    $controller = $CI->router->fetch_class();
    $method = $CI->router->fetch_method();
    $group = $CI->input->get('group');

    $is_invoices_page = $controller === 'invoices' && in_array($method, ['index', 'table']);
    $is_client_invoices_page = $controller === 'clients' && $method === 'client' && $group === 'invoices';

    if ($is_invoices_page) {
        if (staff_can('view', 'peppol')) {
            $data = ['document_type' => 'invoice'];
            $CI->load->view(PEPPOL_MODULE_NAME . '/document_bulk_actions', $data);
        }
    } elseif ($is_client_invoices_page) {
        if (staff_can('view', 'peppol')) {
            $data = ['document_type' => 'client_invoice'];
            $CI->load->view(PEPPOL_MODULE_NAME . '/document_bulk_actions', $data);
        }
    }
});

/**
 * Add PEPPOL actions to single invoice view
 */
hooks()->add_action('before_invoice_preview_more_menu_button', function ($invoice) {
    if (!staff_can('view', 'peppol')) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('clients_model');
    $CI->load->model('peppol/peppol_model');

    $client = $CI->clients_model->get($invoice->clientid);
    $peppol_invoice = $CI->peppol_model->get_peppol_document('invoice', $invoice->id);

    // Only show if client has PEPPOL identifier
    if (!$client || empty($client->peppol_identifier)) {
        // return;
    }

    $data = [
        'document_type' => 'invoice',
        'document' => $invoice,
        'client' => $client,
        'peppol_document' => $peppol_invoice
    ];

    $CI->load->view(PEPPOL_MODULE_NAME . '/document_dropdown_actions', $data);
});