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

/**
 * Handle PEPPOL settings form submission
 */
hooks()->add_action('settings_group_saved', function ($group) {
    if ($group !== 'finance') {
        return;
    }

    // Check if PEPPOL settings were submitted
    $CI = &get_instance();
    $peppol_settings = $CI->input->post('settings');

    if (!$peppol_settings || !isset($peppol_settings['peppol_company_identifier'])) {
        return;
    }

    $CI->load->model('peppol/peppol_model');

    // Log settings update
    $CI->peppol_model->log_activity([
        'type' => 'settings_updated',
        'message' => 'PEPPOL settings updated',
        'staff_id' => get_staff_user_id()
    ]);
});