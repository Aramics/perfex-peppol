<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Credit Notes Table Hooks - Simple bulk actions
 */

/**
 * Add PEPPOL bulk actions to credit notes list page
 */
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();

    // Show on credit notes list page or client credit notes page
    $controller = $CI->router->fetch_class();
    $method = $CI->router->fetch_method();
    $group = $CI->input->get('group');

    $is_credit_notes_page = $controller === 'credit_notes' && in_array($method, ['index', 'table']);
    $is_client_credit_notes_page = $controller === 'clients' && $method === 'client' && $group === 'credit_notes';

    if ($is_credit_notes_page) {
        if (staff_can('view', 'peppol')) {
            $data = ['document_type' => 'credit_note'];
            $CI->load->view(PEPPOL_MODULE_NAME . '/document_bulk_actions', $data);
        }
    } elseif ($is_client_credit_notes_page) {
        if (staff_can('view', 'peppol')) {
            $data = ['document_type' => 'client_credit_note'];
            $CI->load->view(PEPPOL_MODULE_NAME . '/document_bulk_actions', $data);
        }
    }
});

/**
 * Add PEPPOL actions to single credit note view
 */
hooks()->add_action('before_credit_note_preview_more_menu_button', function ($credit_note) {
    if (!staff_can('view', 'peppol')) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('clients_model');
    $CI->load->model('peppol/peppol_model');

    $client = $CI->clients_model->get($credit_note->clientid);
    $peppol_credit_note = $CI->peppol_model->get_peppol_document('credit_note', $credit_note->id);

    // Only show if client has PEPPOL identifier
    if (!$client || empty($client->peppol_identifier)) {
        // return;
    }

    $data = [
        'document_type' => 'credit_note',
        'document' => $credit_note,
        'client' => $client,
        'peppol_document' => $peppol_credit_note
    ];

    $CI->load->view(PEPPOL_MODULE_NAME . '/document_dropdown_actions', $data);
});