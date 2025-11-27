<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PEPPOL Integration
Description: Streamlined PEPPOL integration for sending and receiving invoices and credit notes through the PEPPOL network.
Version: 1.0.0
Requires at least: 3.1.*
Author: ulutfa
Author URI: https://codecanyon.net/user/ulutfa
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
 * Process notifications
 */
register_cron_task('peppol_process_notifications');

/**
 * Load hook files
 */
$hook_files = [
    'add_bulk_action_to_invoice_table.php',
    'add_bulk_action_to_credit_notes_table.php',
    'add_settings_tab.php',
    'client_form_enhancement.php',
    'provider_registration.php',
    'add_admin_menu_permissions.php',
    'expense_deletion_cleanup.php',
    'add_credit_note_attachment_visibility_toggle.php'
];

foreach ($hook_files as $hook_file) {
    $hook_path = __DIR__ . '/hooks/' . $hook_file;
    if (file_exists($hook_path)) {
        require_once $hook_path;
    }
}

//dd(peppol_get_active_provider()->webhook([]));