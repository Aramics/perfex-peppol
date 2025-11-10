<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Invoice Lifecycle Hooks
 */

/**
 * Hook when invoice is updated
 */
hooks()->add_action('after_invoice_updated', function ($data) {
    $CI = &get_instance();
    $CI->load->model('peppol/peppol_model');

    // Check if auto-send is enabled and invoice meets criteria
    if (get_option('peppol_auto_send_enabled') == '1') {
        $invoice_id = $data['invoice_id'];
        $invoice = $CI->invoices_model->get($invoice_id);

        if ($invoice && $invoice->status == 2) { // Status 2 = Sent
            $CI->peppol_model->queue_invoice_for_sending($invoice_id);
        }
    }
});

/**
 * Hook when invoice is added
 */
hooks()->add_action('after_invoice_added', function ($invoice_id) {
    $CI = &get_instance();
    $CI->load->model('peppol/peppol_model');

    // Log invoice creation for PEPPOL tracking
    $CI->peppol_model->log_invoice_event($invoice_id, 'created', 'Invoice created in CRM');
});

/**
 * Add PEPPOL action to invoice view
 */
hooks()->add_action('after_invoice_view_as_client_link', function ($invoice) {
    if (!staff_can('create', 'peppol') || !is_peppol_configured()) {
        return;
    }

    render_peppol_invoice_action($invoice);
});