<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'pd.document_type',
    'pd.provider_document_id',
    'pd.local_reference_id',
    'COALESCE(i.number, cn.number) as document_number',
    'c.company as client_name',
    'COALESCE(i.total, cn.total) as document_total',
    'pd.status',
    'pd.provider',
    'COALESCE(pd.sent_at, pd.received_at) as date',
    'pd.expense_id'
];

$CI = &get_instance();

$sTable       = db_prefix() . 'peppol_documents pd';
$sIndexColumn = 'pd.id';

$join = [
    'LEFT JOIN ' . db_prefix() . 'invoices i ON pd.document_type = "invoice" AND pd.local_reference_id = i.id',
    'LEFT JOIN ' . db_prefix() . 'creditnotes cn ON pd.document_type = "credit_note" AND pd.local_reference_id = cn.id',
    'LEFT JOIN ' . db_prefix() . 'clients c ON c.userid = COALESCE(i.clientid, cn.clientid)',
    'LEFT JOIN ' . db_prefix() . 'expenses e ON pd.expense_id = e.id',
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
    'pd.local_reference_id',
    'pd.provider_document_id',
    'pd.sent_at',
    'pd.received_at',
    'pd.created_at',
    'pd.provider_metadata',
    'e.expense_name'
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Document type with badge
    $typeClass = $aRow['document_type'] === 'invoice' ? 'default' : 'default';
    $dirIcon = empty($aRow['received_at']) ? 'down text-danger' : 'up text-success';
    $dirText = empty($aRow['received_at']) ? _l('peppol_inbound') : _l('peppol_outbound');
    $row[] = '<span class="label label-' . $typeClass . '" data-toggle="tooltip" title="' . e($dirText) . '">' .
        ucfirst(str_replace('_', ' ', $aRow['document_type'])) . ' <i class="tw-ml-1 fa fa-arrow-' . ($dirIcon) . '"></i>' . '</span>';

    // Provider Document ID
    $row[] = !empty($aRow['provider_document_id']) ? e($aRow['provider_document_id']) : '-';

    // Local Reference ID with link
    $localRefDisplay = '-';
    if (!empty($aRow['local_reference_id'])) {
        $documentLink = '';
        if ($aRow['document_type'] === 'invoice') {
            $documentLink = admin_url('invoices/list_invoices/' . $aRow['local_reference_id']);
        } elseif ($aRow['document_type'] === 'credit_note') {
            $documentLink = admin_url('credit_notes/list_credit_notes/' . $aRow['local_reference_id']);
        }

        $localRefDisplay = $documentLink ?
            '<a href="' . $documentLink . '" target="_blank">#' . e($aRow['local_reference_id']) . '</a>' :
            '#' . e($aRow['local_reference_id']);
    }
    $row[] = $localRefDisplay;

    // Client name
    $row[] = !empty($aRow['client_name']) ? e($aRow['client_name']) : '-';

    // Document total
    $row[] = !empty($aRow['document_total']) ?
        app_format_money($aRow['document_total'], get_base_currency()) : '-';

    // Status with badge
    $row[] =  render_peppol_status_column($aRow['id'], $aRow['status']);

    // Expense Reference
    $expenseDisplay = '-';
    if (!empty($aRow['expense_id'])) {
        $expenseLink = admin_url('expenses/expense/' . $aRow['expense_id']);
        $expenseText = !empty($aRow['expense_name']) ? e($aRow['expense_name']) : '#' . $aRow['expense_id'];
        $expenseDisplay = '<a href="' . $expenseLink . '" target="_blank">' . $expenseText . '</a>';
    }
    $row[] = $expenseDisplay;

    // Provider
    $row[] = ucfirst($aRow['provider']);

    // Date - with proper sorting support
    $dateValue = $aRow['date'] ?? '';
    if (!empty($dateValue)) {
        $isoDate = date('Y-m-d\TH:i:s', strtotime($dateValue));
        $formattedDate = _dt($dateValue);
        $row[] = '<span data-order="' . $isoDate . '">' . $formattedDate . '</span>';
    } else {
        $row[] = '-';
    }

    // Actions
    $actions = '<div class="tw-flex tw-items-center tw-space-x-1">';
    if (staff_can('view', 'peppol')) {
        // View document details (sidewise view)
        $actions .= '<a href="' . admin_url('peppol/view_document/' . $aRow['id']) . '" class="btn btn-default btn-icon" data-toggle="tooltip" title="' . _l('view') . '">';
        $actions .= '<i class="fa fa-eye"></i></a>';
    }

    $actions .= '</div>';

    $row[] = $actions;

    $output['aaData'][] = $row;
}