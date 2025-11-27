<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Add PEPPOL menu to admin sidebar
 */
hooks()->add_action('admin_init', 'peppol_add_admin_menu');

function peppol_add_admin_menu()
{
    $CI = &get_instance();

    if (staff_can('view', 'peppol')) {
        $CI->app_menu->add_sidebar_menu_item('peppol-menu', [
            'collapse' => true,
            'name'     => _l('peppol'),
            'position' => 15,
            'icon'     => 'fa fa-exchange',
        ]);

        $CI->app_menu->add_sidebar_children_item('peppol-menu', [
            'slug'     => 'peppol-documents',
            'name'     => _l('peppol_documents_menu'),
            'href'     => admin_url('peppol/documents'),
            'position' => 1,
        ]);

        if (staff_can('view', 'settings')) {
            $CI->app_menu->add_sidebar_children_item('peppol-menu', [
                'slug'     => 'peppol-settings',
                'name'     => _l('peppol_settings_menu'),
                'href'     => admin_url('settings?group=peppol'),
                'position' => 2,
            ]);
        }
    }
}