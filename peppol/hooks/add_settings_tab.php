<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Menu and Settings Hook
 */

/**
 * Add PEPPOL menu item and settings
 */
hooks()->add_action('admin_init', function () {
    if (staff_can('view', 'peppol')) {
        $CI = &get_instance();

        $settings_tab = [
            'name'     => _l('peppol_settings'),
            'view'     => PEPPOL_MODULE_NAME . '/admin/settings',
            'position' => 50,
            'icon'     => 'fa fa-list-alt',
        ];

        $CI->app->add_settings_section_child('finance', PEPPOL_MODULE_NAME, $settings_tab);
    }
});
