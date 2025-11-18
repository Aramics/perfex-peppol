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
    
    // Allow other modules to register providers
    $providers = hooks()->apply_filters('peppol_register_providers', $providers);
    
    return $providers;
}
