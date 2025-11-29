<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (function_exists('perfex_saas_is_tenant') && perfex_saas_is_tenant()) {
    /**
     * Get super admin seting at once and cache
     *
     * @param string|null $key
     * @return mixed
     */
    function peppol_saas_super_admin_settings($key = null)
    {
        static $options = null;
        if ($options === null) {
            $options = perfex_saas_get_options([
                'ps_global_peppol_enforce_on_all_tenants'
            ], true, " OR ( `name` LIKE '%peppol%' ) ");
        }
        return $key === null ? $options : ($options[$key] ?? null);
    }

    // Let hide some tabs for tenants as necessary
    hooks()->add_filter('peppol_settings_tabs', function ($peppol_settings_tabs) {
        $override = (int)perfex_saas_tenant_get_super_option('ps_global_peppol_enforce_on_all_tenants');
        if ($override === 1) {
            $peppol_settings_tabs['providers']['hidden'] = true;
        }

        $peppol_settings_tabs['notifications']['hidden'] = true;

        return $peppol_settings_tabs;
    });

    // Let override provider settings if enabled.
    hooks()->add_filter('peppol_provider_settings', function ($settings, $provider_instance) {
        // Rebuild the setting using super admin options.
        $override = (int)perfex_saas_tenant_get_super_option('ps_global_peppol_enforce_on_all_tenants');
        if ($override === 1) {

            $super_settings = peppol_saas_super_admin_settings();

            $option_names = [];

            $setting_prefix = $provider_instance->get_setting_prefix();
            $inputs = $provider_instance->get_setting_inputs();
            foreach ($inputs as $key => $input) {
                // For hidden and readonly fields, always use the default value
                if ($input['type'] === 'hidden' || $input['type'] === 'readonly') {
                    continue;
                }
                $_key = "{$setting_prefix}{$key}";
                $option_names[] = $_key;
                $settings[$key] = $super_settings[$_key] ?? $input['default'] ?? '';
            }
        }

        return $settings;
    }, 10, 2);

    // Let override option get calls for PEPPOL settings
    hooks()->add_filter('peppol_get_option', function ($value, $key) {
        if ($key == 'peppol_active_provider') {
            $super_settings = peppol_saas_super_admin_settings();
            $override = (int)$super_settings['ps_global_peppol_enforce_on_all_tenants'];
            if ($override === 1) {
                return $super_settings[$key] ?? $value;
            }
        }

        // Always get cron and notification settings from super admin
        if ($key == 'peppol_cron_interval' || $key == 'peppol_notification_lookup_hours') {

            $value = peppol_saas_super_admin_settings($key);
        }

        return $value;
    }, 10, 2);
}