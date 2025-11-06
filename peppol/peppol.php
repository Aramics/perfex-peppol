<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PEPPOL Integration
Description: Send and receive invoices through the PEPPOL network using various access point providers
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
 * Init PEPPOL module menu items and admin hooks
 */
hooks()->add_action('admin_init', 'peppol_module_init_menu_items');
hooks()->add_action('admin_init', 'peppol_permissions');
hooks()->add_action('after_invoice_updated', 'peppol_invoice_updated');
hooks()->add_action('after_invoice_added', 'peppol_invoice_added');
hooks()->add_filter('module_peppol_action_links', 'peppol_module_action_links');

/**
 * PEPPOL settings and form hooks
 */
hooks()->add_filter('before_settings_updated', 'peppol_before_settings_updated');
hooks()->add_action('after_settings_updated', 'peppol_after_settings_updated');
hooks()->add_action('after_custom_fields_select_options', 'peppol_add_client_fields');
hooks()->add_action('after_invoice_view_as_client_link', 'peppol_add_invoice_action');
hooks()->add_action('after_cron_run', 'run_peppol_cron');

/**
 * PEPPOL provider registration hooks
 */
hooks()->add_filter('peppol_register_providers', 'peppol_register_default_providers');

/**
 * Asset loading hooks
 */
hooks()->add_action('app_admin_head', 'peppol_load_admin_css');
hooks()->add_action('app_admin_footer', 'peppol_load_admin_js');

/**
 * Add additional settings for this module in the module list area
 */
function peppol_module_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=finance&tab=peppol') . '">' . _l('settings') . '</a>';
    $actions[] = '<a href="' . admin_url('peppol/logs') . '">' . _l('peppol_transaction_logs') . '</a>';

    return $actions;
}

/**
 * Register PEPPOL permissions
 */
function peppol_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
        'send'   => _l('peppol_permission_send'),
        'receive' => _l('peppol_permission_receive'),
    ];

    register_staff_capabilities('peppol', $capabilities, _l('peppol'));
}

/**
 * Initialize PEPPOL module menu items
 */
function peppol_module_init_menu_items()
{
    $CI = &get_instance();

    if (staff_can('view', 'peppol')) {
        $CI->app_menu->add_sidebar_children_item('sales', [
            'slug'     => 'peppol-invoices',
            'name'     => _l('peppol_invoices'),
            'href'     => admin_url('peppol'),
            'position' => 25,
            'icon'     => ''
        ]);

        $CI->app_menu->add_sidebar_children_item('utilities', [
            'slug'     => 'peppol-logs',
            'name'     => _l('peppol_logs'),
            'href'     => admin_url('peppol/logs'),
            'position' => 26,
            'icon'     => ''
        ]);
    }

    $settings_tab = [
        'name'     => _l('peppol_settings'),
        'view'     => PEPPOL_MODULE_NAME . '/settings',
        'position' => 50,
        'icon'     => 'fa fa-list-alt',
    ];

    if (method_exists($CI->app, 'add_settings_section_child'))
        $CI->app->add_settings_section_child('finance', PEPPOL_MODULE_NAME, $settings_tab);
    else
        $CI->app_tabs->add_settings_tab(PEPPOL_MODULE_NAME, $settings_tab);
}

/**
 * Hook when invoice is updated
 */
function peppol_invoice_updated($data)
{
    $CI = &get_instance();
    $CI->load->model('peppol/peppol_model');

    // Check if auto-send is enabled and invoice meets criteria
    if (get_option('peppol_auto_send_enabled') == '1') {
        $invoice_id = $data['invoice_id'];
        $invoice = $CI->invoices_model->get($invoice_id);

        if ($invoice && $invoice->status == 2) { // Status 2 = Sent
            $CI->peppol_model->queue_invoice_for_sending($invoice_id);
        }
    }
}

/**
 * Hook when invoice is added
 */
function peppol_invoice_added($invoice_id)
{
    $CI = &get_instance();
    $CI->load->model('peppol/peppol_model');

    // Log invoice creation for PEPPOL tracking
    $CI->peppol_model->log_invoice_event($invoice_id, 'created', 'Invoice created in CRM');
}


/**
 * Validate PEPPOL settings before saving
 */
function peppol_before_settings_updated($data)
{
    // Validate PEPPOL settings before saving
    if (isset($data['settings']['peppol_company_identifier'])) {
        $identifier = $data['settings']['peppol_company_identifier'];
        if (!empty($identifier) && !preg_match('/^[0-9A-Za-z]+$/', $identifier)) {
            set_alert('danger', _l('peppol_validation_identifier_format'));
            return false;
        }
    }

    return $data;
}

/**
 * Test connection after settings update
 */
function peppol_after_settings_updated($data)
{
    // Test connection after settings update if API credentials changed
    $api_fields = ['peppol_ademico_api_key', 'peppol_unit4_username', 'peppol_recommand_api_key'];

    $should_test = false;
    foreach ($api_fields as $field) {
        if (isset($data['settings'][$field])) {
            $should_test = true;
            break;
        }
    }

    if ($should_test && is_peppol_configured()) {
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_service');

        $result = $CI->peppol_service->test_connection();

        if (!$result['success']) {
            set_alert('warning', _l('peppol_settings_saved') . ' ' . _l('peppol_connection_test_failed') . ': ' . $result['message']);
        } else {
            set_alert('success', _l('peppol_settings_saved') . ' ' . _l('peppol_connection_test_success'));
        }
    }
}

/**
 * Add PEPPOL fields to client form
 */
function peppol_add_client_fields($data)
{
    if ($data['belongs_to'] == 'customers') {
        render_peppol_client_fields();
    }
}

/**
 * Add PEPPOL action to invoice view
 */
function peppol_add_invoice_action($invoice)
{
    if (!staff_can('create', 'peppol') || !is_peppol_configured()) {
        return;
    }

    render_peppol_invoice_action($invoice);
}

/**
 * Register default PEPPOL providers
 */
function peppol_register_default_providers($providers)
{
    // Register Ademico provider
    $providers['ademico'] = [
        'name' => 'Ademico Software',
        'class' => 'Ademico_provider',
        'view' => 'provider-settings/ademico',
        'config_fields' => ['oauth2_client_identifier', 'oauth2_client_secret'],
        'required_fields' => ['peppol_ademico_oauth2_client_identifier', 'peppol_ademico_oauth2_client_secret'],
        'endpoints' => [
            'live' => 'https://peppol-api.ademico-software.com',
            'test' => 'https://test-peppol-api.ademico-software.com',
            'token_live' => 'https://peppol-oauth2.ademico-software.com/oauth2/token',
            'token_test' => 'https://test-peppol-oauth2.ademico-software.com/oauth2/token'
        ],
        'webhooks' => [
            'endpoint' => 'peppol/webhook/ademico',
            'general' => 'peppol/webhook?provider=ademico',
            'health' => 'peppol/webhook/health',
            'signature_header' => 'X-Ademico-Signature',
            'supported_events' => ['document.received', 'document.delivered', 'document.failed']
        ],
        'features' => ['send', 'receive', 'status_tracking', 'webhooks', 'oauth2'],
        'authentication' => 'oauth2'
    ];

    // Register Unit4 provider
    $providers['unit4'] = [
        'name' => 'Unit4 Access Point',
        'class' => 'Unit4_provider',
        'view' => 'provider-settings/unit4',
        'config_fields' => ['username', 'password', 'endpoint_url'],
        'required_fields' => ['peppol_unit4_username', 'peppol_unit4_password'],
        'endpoints' => [
            'live' => 'https://ap.unit4.com',
            'sandbox' => 'https://test-ap.unit4.com'
        ],
        'webhooks' => [
            'endpoint' => 'peppol/webhook/unit4',
            'general' => 'peppol/webhook?provider=unit4',
            'health' => 'peppol/webhook/health',
            'signature_header' => 'X-Unit4-Signature',
            'supported_events' => ['document.received', 'status.updated']
        ],
        'features' => ['send', 'receive', 'status_tracking', 'webhooks'],
        'authentication' => 'basic_auth'
    ];

    // Register Recommand provider
    $providers['recommand'] = [
        'name' => 'Recommand',
        'class' => 'Recommand_provider',
        'view' => 'provider-settings/recommand',
        'config_fields' => ['api_key', 'company_id'],
        'required_fields' => ['peppol_recommand_api_key', 'peppol_recommand_company_id'],
        'endpoints' => [
            'live' => 'https://peppol.recommand.eu/api',
            'sandbox' => 'https://sandbox-peppol.recommand.eu/api'
        ],
        'webhooks' => [
            'endpoint' => 'peppol/webhook/recommand',
            'general' => 'peppol/webhook?provider=recommand',
            'health' => 'peppol/webhook/health',
            'signature_header' => 'X-Recommand-Signature',
            'supported_events' => ['invoice.received', 'invoice.status', 'document.processed']
        ],
        'features' => ['send', 'receive', 'status_tracking', 'webhooks', 'json_format'],
        'authentication' => 'bearer_token'
    ];

    return $providers;
}

/**
 * Load PEPPOL CSS in admin head
 */
function peppol_load_admin_css()
{
    // Only load on PEPPOL-related pages
    if (peppol_should_load_assets()) {
        echo '<link rel="stylesheet" type="text/css" href="' . module_dir_url(PEPPOL_MODULE_NAME, 'assets/css/peppol.css') . '">' . PHP_EOL;
    }
}

/**
 * Load PEPPOL JavaScript in admin footer
 */
function peppol_load_admin_js()
{
    // Only load on PEPPOL-related pages
    if (peppol_should_load_assets()) {
        echo '<script type="text/javascript" src="' . module_dir_url(PEPPOL_MODULE_NAME, 'assets/js/peppol.js') . '"></script>' . PHP_EOL;
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