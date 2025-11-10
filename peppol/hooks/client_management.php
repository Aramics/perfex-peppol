<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Client Management Hooks
 */

/**
 * Hook when client is created - auto register as legal entity
 */
hooks()->add_action('after_client_created', function ($client_id) {
    // Only auto-register if PEPPOL is configured and auto-registration is enabled
    if (!is_peppol_configured() || get_option('peppol_auto_register_legal_entities') != '1') {
        return;
    }

    $CI = &get_instance();
    $CI->load->library('peppol/peppol_service');
    
    // Run in background to avoid blocking client creation
    try {
        $result = $CI->peppol_service->create_or_update_client_legal_entity($client_id);
        
        if ($result['success']) {
            $CI->load->model('peppol/peppol_model');
            $CI->peppol_model->log_activity([
                'client_id' => $client_id,
                'action' => 'auto_legal_entity_registration',
                'status' => 'success',
                'message' => 'Client automatically registered as legal entity on creation'
            ]);
        }
    } catch (Exception $e) {
        // Log error but don't fail client creation
        log_message('error', 'PEPPOL auto legal entity registration failed for client ' . $client_id . ': ' . $e->getMessage());
    }
});

/**
 * Hook when client is updated - sync legal entity if significant changes
 */
hooks()->add_action('after_client_updated', function ($data) {
    // Only sync if PEPPOL is configured and auto-sync is enabled
    if (!is_peppol_configured() || get_option('peppol_auto_sync_legal_entities') != '1') {
        return;
    }

    $client_id = $data['client_id'];
    $CI = &get_instance();
    
    // Check if client has any existing legal entity registrations
    $CI->load->library('peppol/peppol_service');
    $status = $CI->peppol_service->get_client_legal_entity_status($client_id);
    
    // Only sync if client is already registered with at least one provider
    $has_registration = false;
    foreach ($status['providers'] as $provider_status) {
        if ($provider_status['registered']) {
            $has_registration = true;
            break;
        }
    }
    
    if (!$has_registration) {
        return;
    }

    // Check if significant data changed
    $significant_fields = ['company', 'vat', 'address', 'city', 'zip', 'country', 'billing_street', 'billing_city', 'billing_zip', 'billing_country'];
    $data_changed = false;
    
    foreach ($significant_fields as $field) {
        if (isset($data['data'][$field]) && $data['data'][$field] != $data['original_data'][$field]) {
            $data_changed = true;
            break;
        }
    }
    
    if (!$data_changed) {
        return;
    }

    // Sync with all registered providers
    try {
        foreach ($status['providers'] as $provider => $provider_status) {
            if ($provider_status['registered']) {
                $CI->peppol_service->sync_client_legal_entity($client_id, $provider);
            }
        }
        
        $CI->load->model('peppol/peppol_model');
        $CI->peppol_model->log_activity([
            'client_id' => $client_id,
            'action' => 'auto_legal_entity_sync',
            'status' => 'success', 
            'message' => 'Legal entity data automatically synchronized after client update'
        ]);
        
    } catch (Exception $e) {
        // Log error but don't fail client update
        log_message('error', 'PEPPOL auto legal entity sync failed for client ' . $client_id . ': ' . $e->getMessage());
    }
});

/**
 * Add PEPPOL legal entity management section to client profile
 */
hooks()->add_action('after_custom_profile_tab_content', function ($client) {
    if (!$client || !staff_can('view', 'peppol') || !is_peppol_configured()) {
        return;
    }

    echo render_peppol_legal_entity_section($client);
});

/**
 * Add PEPPOL fields to client form
 */
hooks()->add_action('after_custom_fields_select_options', function ($data) {
    if ($data['belongs_to'] == 'customers') {
        render_peppol_client_fields();
    }
});