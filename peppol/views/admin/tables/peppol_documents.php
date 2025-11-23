<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'pd.document_type',
    'COALESCE(i.number, cn.number) as document_number',
    'c.company as client_name',
    'COALESCE(i.total, cn.total) as document_total',
    'pd.status',
    'pd.provider',
    'COALESCE(pd.sent_at, pd.received_at) as date'
];

$CI = &get_instance();

$sTable       = db_prefix() . 'peppol_documents pd';
$sIndexColumn = 'pd.id';

$join = [
    'LEFT JOIN ' . db_prefix() . 'invoices i ON pd.document_type = "invoice" AND pd.document_id = i.id',
    'LEFT JOIN ' . db_prefix() . 'creditnotes cn ON pd.document_type = "credit_note" AND pd.document_id = cn.id',
    'LEFT JOIN ' . db_prefix() . 'clients c ON c.userid = COALESCE(i.clientid, cn.clientid)',
];

$where = [];

if (!staff_can('view', 'peppol')) {
    $where[] = 'AND 1=0'; // No access
}

// Apply filters
if ($CI->input->post('filter_document_type') && $CI->input->post('filter_document_type') !== '') {
    $where[] = 'AND pd.document_type = ' . $CI->db->escape($CI->input->post('filter_document_type'));
}

if ($CI->input->post('filter_status') && $CI->input->post('filter_status') !== '') {
    $where[] = 'AND pd.status = ' . $CI->db->escape($CI->input->post('filter_status'));
}

if ($CI->input->post('filter_provider') && $CI->input->post('filter_provider') !== '') {
    $where[] = 'AND pd.provider = ' . $CI->db->escape($CI->input->post('filter_provider'));
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'pd.id',
    'pd.document_id',
    'pd.provider_document_id',
    'pd.sent_at',
    'pd.received_at',
    'pd.created_at',
    'pd.provider_metadata'
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Document type with badge
    $typeClass = $aRow['document_type'] === 'invoice' ? 'primary' : 'info';
    $row[] = '<span class="label label-' . $typeClass . '">' . 
             ucfirst(str_replace('_', ' ', $aRow['document_type'])) . '</span>';

    // Document number with link
    $documentLink = '';
    if ($aRow['document_type'] === 'invoice' && !empty($aRow['document_id'])) {
        $documentLink = admin_url('invoices/list_invoices/' . $aRow['document_id']);
    } elseif ($aRow['document_type'] === 'credit_note' && !empty($aRow['document_id'])) {
        $documentLink = admin_url('credit_notes/list_credit_notes/' . $aRow['document_id']);
    }
    
    $documentNumber = $aRow['document_number'] ?: '#' . $aRow['document_id'];
    $row[] = $documentLink ? 
             '<a href="' . $documentLink . '" target="_blank">' . e($documentNumber) . '</a>' :
             e($documentNumber);

    // Client name
    $row[] = !empty($aRow['client_name']) ? e($aRow['client_name']) : '-';

    // Document total
    $row[] = !empty($aRow['document_total']) ? 
             app_format_money($aRow['document_total'], get_base_currency()) : '-';

    // Status with badge
    $statusClass = '';
    switch ($aRow['status']) {
        case 'sent':
        case 'delivered':
            $statusClass = 'label-success';
            break;
        case 'pending':
        case 'queued':
            $statusClass = 'label-warning';
            break;
        case 'failed':
            $statusClass = 'label-danger';
            break;
        case 'received':
            $statusClass = 'label-info';
            break;
        default:
            $statusClass = 'label-default';
    }
    $row[] = '<span class="label ' . $statusClass . '">' . ucfirst($aRow['status']) . '</span>';

    // Provider
    $row[] = ucfirst($aRow['provider']);

    // Date
    $row[] = !empty($aRow['date']) ? _dt($aRow['date']) : '-';

    // Actions
    $actions = '';
    if (staff_can('view', 'peppol')) {
        $actions .= '<a href="#" onclick="viewPeppolDocument(' . $aRow['id'] . ')" class="btn btn-default btn-icon" data-toggle="tooltip" title="' . _l('view') . '">';
        $actions .= '<i class="fa fa-eye"></i></a>';
    }
    $row[] = $actions;

    $output['aaData'][] = $row;
}
?>