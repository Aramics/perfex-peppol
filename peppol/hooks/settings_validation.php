<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Settings and Validation Hooks
 */

/**
 * Validate PEPPOL settings before saving
 */
hooks()->add_filter('before_settings_updated', function ($data) {
    // Validate PEPPOL settings before saving
    if (isset($data['settings']['peppol_company_identifier'])) {
        $identifier = $data['settings']['peppol_company_identifier'];
        if (!empty($identifier) && !preg_match('/^[0-9A-Za-z]+$/', $identifier)) {
            set_alert('danger', _l('peppol_validation_identifier_format'));
            return false;
        }
    }

    return $data;
});

/**
 * Test connection after settings update
 */
hooks()->add_action('after_settings_updated', function ($data) {
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
});