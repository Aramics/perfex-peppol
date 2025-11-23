<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Add PEPPOL menu to admin sidebar
 */
hooks()->add_action('admin_init', 'peppol_add_admin_menu');

function peppol_add_admin_menu()
{
    $CI = &get_instance();

    if (has_permission('peppol', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('peppol-menu', [
            'collapse' => true,
            'name'     => _l('peppol'),
            'position' => 15,
            'icon'     => 'fa fa-exchange',
        ]);

        $CI->app_menu->add_sidebar_children_item('peppol-menu', [
            'slug'     => 'peppol-documents',
            'name'     => _l('peppol_documents'),
            'icon'     => 'fa fa-file-text-o',
            'href'     => admin_url('peppol/documents'),
            'position' => 1,
        ]);

        if (has_permission('settings', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('peppol-menu', [
                'slug'     => 'peppol-settings',
                'name'     => _l('peppol_settings'),
                'icon'     => 'fa fa-cog',
                'href'     => admin_url('settings?group=peppol'),
                'position' => 2,
            ]);
        }
    }
}