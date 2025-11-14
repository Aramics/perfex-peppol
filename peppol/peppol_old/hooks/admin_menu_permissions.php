<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Admin Menu and Permissions Hooks
 */

/**
 * Register PEPPOL permissions
 */
hooks()->add_action('admin_init', function () {
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
});

/**
 * Initialize PEPPOL module menu items
 */
hooks()->add_action('admin_init', function () {
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
});

/**
 * Add additional settings for this module in the module list area
 */
hooks()->add_filter('module_peppol_action_links', function ($actions) {
    $actions[] = '<a href="' . admin_url('settings?group=finance&tab=peppol') . '">' . _l('settings') . '</a>';
    $actions[] = '<a href="' . admin_url('peppol/logs') . '">' . _l('peppol_transaction_logs') . '</a>';

    return $actions;
});