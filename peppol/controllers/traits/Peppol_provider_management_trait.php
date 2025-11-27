<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Management Trait
 * 
 * Handles provider-related operations including:
 * - Provider connection testing
 */
trait Peppol_provider_management_trait
{
    /**
     * Test provider connection (AJAX)
     */
    public function test_provider_connection()
    {
        if (!staff_can('edit', 'settings') || !$this->input->post()) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_access_denied')
            ]);
            return;
        }

        $provider_id = $this->input->post('provider');
        $form_settings = $this->input->post('settings');

        if (!$provider_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_invalid_provider')
            ]);
            return;
        }

        try {
            // Get registered providers
            $providers = peppol_get_registered_providers();

            if (!isset($providers[$provider_id])) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('peppol_provider_not_found')
                ]);
                return;
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

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}