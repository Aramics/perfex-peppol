<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Management Trait
 * 
 * Handles PEPPOL access point provider operations including:
 * - Provider connection testing and validation
 * - Provider configuration management
 * 
 * @package PEPPOL
 * @subpackage Controllers\Traits
 */
trait Peppol_provider_management_trait
{
    /**
     * Test PEPPOL provider connection (AJAX)
     * 
     * Validates and tests connection to a PEPPOL access point provider using
     * the provided configuration settings. Filters settings by provider prefix
     * and tests connectivity before saving configuration.
     * 
     * @return void Outputs JSON response with test results
     */
    public function test_provider_connection()
    {
        if (!staff_can('edit', 'settings') || !$this->input->post()) {
            return $this->json_output([
                'success' => false,
                'message' => _l('peppol_access_denied')
            ]);
        }

        $provider_id = $this->input->post('provider');
        $form_settings = $this->input->post('settings');

        if (!$provider_id) {
            return $this->json_output([
                'success' => false,
                'message' => _l('peppol_invalid_provider')
            ]);
        }

        try {
            // Get registered providers
            $providers = peppol_get_registered_providers();

            if (!isset($providers[$provider_id])) {
                return $this->json_output([
                    'success' => false,
                    'message' => _l('peppol_provider_not_found')
                ]);
            }

            $provider_instance = $providers[$provider_id];

            // Filter and clean settings for this provider
            $provider_settings = [];
            $provider_prefix = "peppol_{$provider_id}_";

            if (is_array($form_settings)) {
                foreach ($form_settings as $key => $value) {
                    // Extract settings that belong to this provider and remove prefix
                    if (strpos($key, $provider_prefix) === 0) {
                        $clean_key = str_replace($provider_prefix, '', $key);
                        $provider_settings[$clean_key] = $value;
                    }
                }
            }

            // Test the connection with cleaned settings
            $result = $provider_instance->test_connection($provider_settings);

            return $this->json_output($result);
        } catch (Exception $e) {
            return $this->json_output([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

}