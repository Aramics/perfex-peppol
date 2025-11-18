<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Registration System
 * 
 * Simple registration system for PEPPOL provider instances.
 * Providers register themselves through hooks and are returned as a map of ID to instance.
 */

/**
 * Get all registered PEPPOL providers
 * 
 * @return array Map of provider ID to provider instance ['provider_id' => $instance]
 */
function peppol_get_registered_providers()
{
    $providers = [];

    // Register default providers
    $providers = peppol_register_default_providers($providers);

    // Allow other modules to register providers
    $providers = hooks()->apply_filters('peppol_register_providers', $providers);

    return $providers;
}

/**
 * Register default PEPPOL providers
 */
function peppol_register_default_providers($providers)
{
    // Load and register Ademico provider
    require_once FCPATH . 'modules/peppol/libraries/providers/Ademico_peppol_provider.php';
    $ademico_provider = new Ademico_peppol_provider();
    $providers[$ademico_provider->get_id()] = $ademico_provider;

    return $providers;
}

/**
 * Get the currently active PEPPOL provider instance
 * 
 * @return Peppol_provider_interface|null Active provider instance or null if none configured
 */
function peppol_get_active_provider()
{
    $active_provider_id = get_option('peppol_active_provider', '');

    if (empty($active_provider_id)) {
        return null;
    }

    $providers = peppol_get_registered_providers();

    return isset($providers[$active_provider_id]) ? $providers[$active_provider_id] : null;
}
