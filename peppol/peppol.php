<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PEPPOL Integration
Description: Simple PEPPOL integration for sending invoices and credit notes via PEPPOL network
Version: 1.0.0
Requires at least: 3.4.*
Author: ulutfa
*/

define('PEPPOL_MODULE_NAME', 'peppol');

/**
 * Load the module helper
 */
$CI = &get_instance();
$CI->load->helper(PEPPOL_MODULE_NAME . '/' . PEPPOL_MODULE_NAME);

/**
 * Register activation module hook
 */
register_activation_hook(PEPPOL_MODULE_NAME, 'peppol_module_activation_hook');

function peppol_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files
 */
register_language_files(PEPPOL_MODULE_NAME, [PEPPOL_MODULE_NAME]);

/**
 * Load hook files
 */
$hook_files = [
    'add_bulk_action_to_invoice_table.php',
    'add_bulk_action_to_credit_notes_table.php',
    'add_settings_tab.php',
    'client_form_enhancement.php'
];

foreach ($hook_files as $hook_file) {
    $hook_path = __DIR__ . '/hooks/' . $hook_file;
    if (file_exists($hook_path)) {
        require_once $hook_path;
    }
}