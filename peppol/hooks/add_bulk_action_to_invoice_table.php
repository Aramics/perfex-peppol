<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Multiple selection: Add checkbox to the invoice table
hooks()->add_filter('invoices_table_columns', function ($table_data) {
    $table_data[0] = '<span class="tw-inline-block tw-pr-2"><input type="checkbox"
        id="multiple-invoice-toggle" /></span>' . $table_data[0];
    return $table_data;
});

hooks()->add_filter('invoices_table_row_data', function ($row, $aRow = []) {
    $row[0] = '<span class="tw-inline-block tw-pr-2"><input type="checkbox" class="multiple-invoice-toggle"
        value="' . ($aRow['id'] ?? '') . '" /></span>' . $row[0];
    return $row;
}, 10, 2);

/**
 * Register admin footer hook
 */
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();
    $CI->load->view(PEPPOL_MODULE_NAME . '/scripts/multiple_invoice_action');
});