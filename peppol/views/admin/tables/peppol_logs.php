<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'pl.id',
    'pl.type',
    'pl.document_type',
    'pl.local_reference_id',
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

// Apply filters
if ($CI->input->post('filter_type') && $CI->input->post('filter_type') !== '') {
    $where[] = 'AND pl.type = ' . $CI->db->escape($CI->input->post('filter_type'));
}

if ($CI->input->post('filter_document_type') && $CI->input->post('filter_document_type') !== '') {
    $where[] = 'AND pl.document_type = ' . $CI->db->escape($CI->input->post('filter_document_type'));
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'pl.staff_id'
]);

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

    // Date
    $row[] = _dt($aRow['created_at'] ?? '');

    $output['aaData'][] = $row;
}