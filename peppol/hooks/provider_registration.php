<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Registration Hook System
 * 
 * Simplified class-based registration system where providers are registered as classes
 * that implement or extend Abstract_peppol_provider.
 */

// Hook for getting registered provider classes
hooks()->add_filter('peppol_get_registered_provider_classes', 'peppol_get_default_provider_classes');

/**
 * Get default registered provider classes
 */
function peppol_get_default_provider_classes($provider_classes)
{
    // No default provider classes - system starts empty
    // Provider classes are registered by external modules or extensions
    return $provider_classes;
}

/**
 * Simple helper function to register a PEPPOL provider class
 * 
 * Usage example:
 * peppol_register_provider_class('My_peppol_provider');
 * 
 * The class should implement or extend Abstract_peppol_provider
 */
function peppol_register_provider_class($class_name)
{
    hooks()->add_filter('peppol_get_registered_provider_classes', function($provider_classes) use ($class_name) {
        if (!in_array($class_name, $provider_classes)) {
            $provider_classes[] = $class_name;
        }
        return $provider_classes;
    });
}