<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'pl.created_at',
    'i.number as invoice_number',
    'pl.provider',
    'pl.action',
    'pl.status',
    'pl.message',
    'pl.document_id'
];

$sIndexColumn = 'pl.id';
$sTable = db_prefix() . 'peppol_logs pl';

$join = [
    'LEFT JOIN ' . db_prefix() . 'invoices i ON i.id = pl.invoice_id'
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], [
    'pl.id',
    'pl.invoice_id',
    'pl.request_data',
    'pl.response_data'
]);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Created date
    $row[] = _dt($aRow['created_at']);

    // Invoice number with link
    if (!empty($aRow['invoice_id']) && !empty($aRow['invoice_number'])) {
        $invoice_link = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoice_id']) . '" target="_blank">' . 
                       $aRow['invoice_number'] . '</a>';
        $row[] = $invoice_link;
    } else {
        $row[] = '-';
    }

    // Provider
    $row[] = ucfirst($aRow['provider']);

    // Action
    $row[] = ucfirst(str_replace('_', ' ', $aRow['action']));

    // Status with badge
    $status_class = '';
    switch ($aRow['status']) {
        case 'success':
            $status_class = 'success';
            break;
        case 'error':
            $status_class = 'danger';
            break;
        case 'warning':
            $status_class = 'warning';
            break;
        case 'info':
            $status_class = 'info';
            break;
        default:
            $status_class = 'default';
    }
    
    $status_badge = '<span class="label label-' . $status_class . '">' . ucfirst($aRow['status']) . '</span>';
    $row[] = $status_badge;

    // Message with tooltip for long messages
    $message = $aRow['message'];
    if (strlen($message) > 50) {
        $short_message = substr($message, 0, 50) . '...';
        $message_display = '<span data-toggle="tooltip" title="' . htmlspecialchars($message) . '">' . 
                          htmlspecialchars($short_message) . '</span>';
    } else {
        $message_display = htmlspecialchars($message);
    }
    $row[] = $message_display;

    // Document ID
    $row[] = $aRow['document_id'] ?: '-';

    $output['aaData'][] = $row;
}

echo json_encode($output);