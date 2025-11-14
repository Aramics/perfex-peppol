<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Registration Hooks
 */

/**
 * Register default PEPPOL providers
 */
hooks()->add_filter('peppol_register_providers', function ($providers) {
    // Register Ademico provider
    $providers['ademico'] = [
        'name' => 'Ademico Software',
        'class' => 'Ademico_provider',
        'view' => 'provider-settings/ademico',
        'config_fields' => ['oauth2_client_identifier', 'oauth2_client_secret'],
        'required_fields' => ['peppol_ademico_oauth2_client_identifier', 'peppol_ademico_oauth2_client_secret'],
        'endpoints' => [
            'live' => 'https://peppol-api.ademico-software.com',
            'test' => 'https://test-peppol-api.ademico-software.com',
            'token_live' => 'https://peppol-oauth2.ademico-software.com/oauth2/token',
            'token_test' => 'https://test-peppol-oauth2.ademico-software.com/oauth2/token'
        ],
        'webhooks' => [
            'endpoint' => 'peppol/webhook/ademico',
            'general' => 'peppol/webhook?provider=ademico',
            'health' => 'peppol/webhook/health',
            'signature_header' => 'X-Ademico-Signature',
            'supported_events' => ['document.received', 'document.delivered', 'document.failed']
        ],
        'features' => ['send', 'receive', 'status_tracking', 'webhooks', 'oauth2'],
        'authentication' => 'oauth2'
    ];

    // Register Unit4 provider
    $providers['unit4'] = [
        'name' => 'Unit4 Access Point',
        'class' => 'Unit4_provider',
        'view' => 'provider-settings/unit4',
        'config_fields' => ['username', 'password', 'endpoint_url'],
        'required_fields' => ['peppol_unit4_username', 'peppol_unit4_password'],
        'endpoints' => [
            'live' => 'https://ap.unit4.com',
            'sandbox' => 'https://test-ap.unit4.com'
        ],
        'webhooks' => [
            'endpoint' => 'peppol/webhook/unit4',
            'general' => 'peppol/webhook?provider=unit4',
            'health' => 'peppol/webhook/health',
            'signature_header' => 'X-Unit4-Signature',
            'supported_events' => ['document.received', 'status.updated']
        ],
        'features' => ['send', 'receive', 'status_tracking', 'webhooks'],
        'authentication' => 'basic_auth'
    ];

    // Register Recommand provider
    $providers['recommand'] = [
        'name' => 'Recommand',
        'class' => 'Recommand_provider',
        'view' => 'provider-settings/recommand',
        'config_fields' => ['api_key', 'company_id'],
        'required_fields' => ['peppol_recommand_api_key', 'peppol_recommand_company_id'],
        'endpoints' => [
            'live' => 'https://peppol.recommand.eu/api',
            'sandbox' => 'https://sandbox-peppol.recommand.eu/api'
        ],
        'webhooks' => [
            'endpoint' => 'peppol/webhook/recommand',
            'general' => 'peppol/webhook?provider=recommand',
            'health' => 'peppol/webhook/health',
            'signature_header' => 'X-Recommand-Signature',
            'supported_events' => ['invoice.received', 'invoice.status', 'document.processed']
        ],
        'features' => ['send', 'receive', 'status_tracking', 'webhooks', 'json_format'],
        'authentication' => 'bearer_token'
    ];

    return $providers;
});