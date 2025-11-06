<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'i.number as invoice_number',
    'CONCAT(c.firstname, " ", c.lastname) as client_name', 
    'pi.status',
    'pi.provider',
    'pi.peppol_document_id',
    'pi.sent_at'
];

$sIndexColumn = 'pi.id';
$sTable = db_prefix() . 'peppol_invoices pi';

$join = [
    'LEFT JOIN ' . db_prefix() . 'invoices i ON i.id = pi.invoice_id',
    'LEFT JOIN ' . db_prefix() . 'clients c ON c.userid = i.clientid'
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], [
    'pi.id',
    'pi.invoice_id',
    'i.total',
    'c.company',
    'pi.error_message',
    'pi.ubl_content'
]);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Invoice number with link
    $invoice_number = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoice_id']) . '" target="_blank">' . 
                     $aRow['invoice_number'] . '</a>';
    $row[] = $invoice_number;

    // Client name (company or contact name)
    $client_name = !empty($aRow['company']) ? $aRow['company'] : $aRow['client_name'];
    $row[] = $client_name;

    // Status with badge
    $status_class = '';
    switch ($aRow['status']) {
        case 'sent':
        case 'delivered':
            $status_class = 'success';
            break;
        case 'failed':
            $status_class = 'danger';
            break;
        case 'pending':
        case 'queued':
            $status_class = 'warning';
            break;
        case 'sending':
            $status_class = 'info';
            break;
        default:
            $status_class = 'default';
    }
    
    $status_badge = '<span class="label label-' . $status_class . '">' . _l('peppol_status_' . $aRow['status']) . '</span>';
    
    if ($aRow['status'] == 'failed' && !empty($aRow['error_message'])) {
        $status_badge .= ' <i class="fa fa-info-circle" data-toggle="tooltip" title="' . 
                        htmlspecialchars($aRow['error_message']) . '"></i>';
    }
    
    $row[] = $status_badge;

    // Provider
    $row[] = ucfirst($aRow['provider']);

    // PEPPOL Document ID
    $row[] = $aRow['peppol_document_id'] ?: '-';

    // Sent date
    $row[] = $aRow['sent_at'] ? _dt($aRow['sent_at']) : '-';

    // Actions
    $actions = '';
    
    if (staff_can('view', 'peppol')) {
        if (!empty($aRow['ubl_content'])) {
            $actions .= '<a href="' . admin_url('peppol/view_ubl/' . $aRow['id']) . '" class="btn btn-default btn-icon" target="_blank" data-toggle="tooltip" title="' . _l('peppol_view_ubl') . '">
                        <i class="fa fa-eye"></i></a> ';
            
            $actions .= '<a href="' . admin_url('peppol/download_ubl/' . $aRow['id']) . '" class="btn btn-default btn-icon" data-toggle="tooltip" title="' . _l('peppol_download_ubl') . '">
                        <i class="fa fa-download"></i></a> ';
        }
    }
    
    if (staff_can('create', 'peppol')) {
        if (in_array($aRow['status'], ['failed', 'pending'])) {
            $actions .= '<a href="' . admin_url('peppol/resend/' . $aRow['id']) . '" class="btn btn-warning btn-icon" data-toggle="tooltip" title="' . _l('peppol_resend') . '" onclick="return confirm(\'' . _l('peppol_confirm_resend') . '\')">
                        <i class="fa fa-refresh"></i></a> ';
        }
    }

    $row[] = $actions;

    $output['aaData'][] = $row;
}

echo json_encode($output);