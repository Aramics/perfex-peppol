<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'c.company',
    'c.vat',
    '(SELECT cfv_s.value FROM ' . db_prefix() . 'customfieldsvalues cfv_s 
      LEFT JOIN ' . db_prefix() . 'customfields cf_s ON cf_s.id = cfv_s.fieldid 
      WHERE cfv_s.relid = c.userid AND cfv_s.fieldto = "customers" AND cf_s.slug = "customers_peppol_scheme" LIMIT 1) as peppol_scheme',
    '(SELECT cfv_i.value FROM ' . db_prefix() . 'customfieldsvalues cfv_i 
      LEFT JOIN ' . db_prefix() . 'customfields cf_i ON cf_i.id = cfv_i.fieldid 
      WHERE cfv_i.relid = c.userid AND cfv_i.fieldto = "customers" AND cf_i.slug = "customers_peppol_identifier" LIMIT 1) as peppol_identifier',
    'c.active',
    'c.userid'
];

$CI = &get_instance();

$sTable       = db_prefix() . 'clients c';
$sIndexColumn = 'c.userid';

$join = [];

$where = [
    'AND c.company IS NOT NULL',
    'AND c.company != ""'
];

if (!staff_can('view', 'peppol')) {
    $where[] = 'AND 1=0'; // No access
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'c.userid',
    'c.company', 
    'c.vat',
    'c.country',
    'c.active'
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Company name (clickable)
    $company_link = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '" target="_blank">' . 
                    e($aRow['company']) . '</a>';
    $row[] = $company_link;

    // VAT number
    $vat_display = !empty($aRow['vat']) ? e($aRow['vat']) : '<span class="text-muted">' . _l('peppol_no_vat') . '</span>';
    $row[] = $vat_display;

    // Peppol Scheme
    $peppol_scheme = !empty($aRow['peppol_scheme']) ? 
        '<span class="label label-info">' . e($aRow['peppol_scheme']) . '</span>' : 
        '<span class="text-muted">-</span>';
    $row[] = $peppol_scheme;

    // Peppol Identifier
    $peppol_identifier = !empty($aRow['peppol_identifier']) ? 
        '<code>' . e($aRow['peppol_identifier']) . '</code>' : 
        '<span class="text-muted">-</span>';
    $row[] = $peppol_identifier;

    // Status
    $status_label = $aRow['active'] == 1 ? 
        '<span class="label label-success">Active</span>' : 
        '<span class="label label-default">Inactive</span>';
    $row[] = $status_label;

    // Actions
    $actions = '<button class="btn btn-sm btn-info" onclick="PeppolLookup.singleCustomerLookup(' . $aRow['userid'] . ')" title="Auto lookup">
                <i class="fa fa-search"></i>
                </button>';
    $row[] = $actions;

    $output['aaData'][] = $row;
}