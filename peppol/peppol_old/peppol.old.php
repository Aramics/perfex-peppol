<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PEPPOL Integration
Description: Send and receive invoices through the PEPPOL network using various access point providers
Version: 1.0.1
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
$CI->load->helper(PEPPOL_MODULE_NAME . '/' . PEPPOL_MODULE_NAME . '_cron');

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
 * Load organized hook files
 */
$hook_files = [
    'admin_menu_permissions.php',
    'invoice_lifecycle.php',
    'client_management.php',
    'settings_validation.php',
    'provider_registration.php',
    'asset_management.php',
    'cron_tasks.php',
    'add_bulk_action_to_invoice_table.php'
];

foreach ($hook_files as $hook_file) {
    $hook_path = __DIR__ . '/hooks/' . $hook_file;
    if (file_exists($hook_path)) {
        require_once $hook_path;
    }
}

/**
 * Check if PEPPOL assets should be loaded on current page
 */
function peppol_should_load_assets()
{
    $CI = &get_instance();

    // Get current controller and method
    $controller = $CI->router->fetch_class();
    $method = $CI->router->fetch_method();

    // Load on PEPPOL module pages
    if ($controller === 'peppol') {
        return true;
    }

    // Load on settings page when viewing PEPPOL settings
    if ($controller === 'settings' && isset($_GET['group']) && $_GET['group'] === PEPPOL_MODULE_NAME) {
        return true;
    }

    // Load on invoices pages for PEPPOL integration
    if ($controller === 'invoices') {
        return true;
    }

    // Load on clients pages for PEPPOL fields
    if ($controller === 'clients') {
        return true;
    }

    return false;
}