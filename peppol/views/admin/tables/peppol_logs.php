<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'pl.id',
    'pl.message',
    'pl.created_at'
];

$CI = &get_instance();

$sTable       = db_prefix() . 'peppol_logs pl';
$sIndexColumn = 'pl.id';

$join = [];
$where = [];

if (!staff_can('view', 'peppol_logs')) {
    $where[] = 'AND 1=0';
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, []);

$output  = $result['output'];
$rResult = $result['rResult'];

// Ensure aaData is initialized
if (!isset($output['aaData'])) {
    $output['aaData'] = [];
}

foreach ($rResult as $aRow) {
    $row = [];

    // ID
    $row[] = $aRow['id'];

    // Message (truncated)
    $message = e($aRow['message'] ?? '');
    if (strlen($message) > 100) {
        $message = substr($message, 0, 100) . '...';
    }
    $row[] = '<span title="' . e($aRow['message'] ?? '') . '">' . $message . '</span>';

    // Date - using raw datetime for sorting with formatted display
    $dateValue = $aRow['created_at'] ?? '';
    if (!empty($dateValue)) {
        // Convert to ISO format for proper sorting, but display formatted
        $isoDate = date('Y-m-d\TH:i:s', strtotime($dateValue));
        $formattedDate = _dt($dateValue);
        $row[] = '<span data-order="' . $isoDate . '">' . $formattedDate . '</span>';
    } else {
        $row[] = '-';
    }

    $output['aaData'][] = $row;
}