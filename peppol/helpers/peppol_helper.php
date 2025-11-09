<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Helper Functions
 * 
 * Collection of helper functions for PEPPOL module functionality
 * These functions provide utility methods for rendering UI components
 * and handling PEPPOL-specific data processing
 */

if (!function_exists('render_peppol_client_fields')) {
    /**
     * Render PEPPOL identifier fields for client form
     * 
     * @return void
     */
    function render_peppol_client_fields()
    {
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo render_input('peppol_identifier', _l('peppol_client_identifier'), '', 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_client_identifier_help')
        ]);
        echo '</div>';

        echo '<div class="col-md-6">';
        echo render_select('peppol_scheme', [
            ['id' => '0088', 'name' => '0088 - GLN'],
            ['id' => '0060', 'name' => '0060 - DUNS'],
            ['id' => '0007', 'name' => '0007 - Swedish organization number'],
            ['id' => '0037', 'name' => '0037 - LY-tunnus'],
            ['id' => '0096', 'name' => '0096 - GTIN'],
            ['id' => '0135', 'name' => '0135 - SIA Object Identifier'],
            ['id' => '0183', 'name' => '0183 - Corporate number']
        ], ['id', 'name'], _l('peppol_client_scheme'), '0088');
        echo '</div>';
        echo '</div>';
    }
}

if (!function_exists('render_peppol_invoice_action')) {
    /**
     * Render PEPPOL action button/status for invoice view
     * 
     * @param object $invoice Invoice object
     * @return void
     */
    function render_peppol_invoice_action($invoice)
    {
        $CI = &get_instance();
        $CI->load->model('clients_model');
        $client = $CI->clients_model->get($invoice->clientid);

        if (!$client || empty($client->peppol_identifier)) {
            echo '<div class="alert alert-info mtop15">';
            echo '<i class="fa fa-info-circle"></i> ';
            echo _l('peppol_client_no_identifier');
            echo '</div>';
            return;
        }

        $CI->load->model('peppol/peppol_model');
        $peppol_invoice = $CI->peppol_model->get_peppol_invoice_by_invoice($invoice->id);

        if ($peppol_invoice) {
            echo '<div class="alert alert-info mtop15">';
            echo '<i class="fa fa-paper-plane"></i> ';
            echo _l('peppol_status') . ': <strong>' . _l('peppol_status_' . $peppol_invoice->status) . '</strong>';

            if ($peppol_invoice->peppol_document_id) {
                echo '<br>' . _l('peppol_document_id') . ': ' . $peppol_invoice->peppol_document_id;
            }

            if ($peppol_invoice->status == 'failed') {
                echo '<br><a href="' . admin_url('peppol/resend/' . $peppol_invoice->id) . '" class="btn btn-warning btn-xs mtop5" onclick="return confirm(\'' . _l('peppol_confirm_resend') . '\')">';
                echo '<i class="fa fa-refresh"></i> ' . _l('peppol_resend');
                echo '</a>';
            }

            echo '</div>';
        } else if ($invoice->status == 2) { // Sent status
            echo '<div class="mtop15">';
            echo '<a href="' . admin_url('peppol/send/' . $invoice->id) . '" class="btn btn-info" onclick="return confirm(\'' . _l('peppol_confirm_send') . '\')">';
            echo '<i class="fa fa-paper-plane"></i> ' . _l('peppol_send_invoice');
            echo '</a>';
            echo '</div>';
        }
    }
}

if (!function_exists('get_peppol_status_label')) {
    /**
     * Get formatted status label for PEPPOL transmission
     * 
     * @param string $status Status string
     * @return string HTML formatted status label
     */
    function get_peppol_status_label($status)
    {
        $status_classes = [
            'sent' => 'success',
            'delivered' => 'success',
            'failed' => 'danger',
            'pending' => 'warning',
            'queued' => 'warning',
            'sending' => 'info',
            'processed' => 'success',
            'received' => 'info'
        ];

        $class = isset($status_classes[$status]) ? $status_classes[$status] : 'default';

        return '<span class="label label-' . $class . '">' . _l('peppol_status_' . $status) . '</span>';
    }
}

if (!function_exists('format_peppol_identifier')) {
    /**
     * Format PEPPOL identifier with scheme for display
     * 
     * @param string $identifier PEPPOL identifier
     * @param string $scheme Scheme code
     * @return string Formatted identifier
     */
    function format_peppol_identifier($identifier, $scheme = null)
    {
        if (empty($identifier)) {
            return '-';
        }

        if ($scheme) {
            return $scheme . ':' . $identifier;
        }

        return $identifier;
    }
}

if (!function_exists('get_peppol_scheme_options')) {
    /**
     * Get available PEPPOL identifier scheme options
     * 
     * @return array Array of scheme options
     */
    function get_peppol_scheme_options()
    {
        return [
            '0088' => '0088 - GLN (Global Location Number)',
            '0060' => '0060 - DUNS (Data Universal Numbering System)',
            '0007' => '0007 - Swedish organization number',
            '0037' => '0037 - LY-tunnus (Finnish organization number)',
            '0096' => '0096 - GTIN (Global Trade Item Number)',
            '0135' => '0135 - SIA Object Identifier',
            '0183' => '0183 - Corporate number',
            '0184' => '0184 - Danish CVR number',
            '0192' => '0192 - Norwegian organization number',
            '9915' => '9915 - D-U-N-S Number',
            '9925' => '9925 - Business registration number'
        ];
    }
}

if (!function_exists('validate_peppol_identifier')) {
    /**
     * Validate PEPPOL identifier format
     * 
     * @param string $identifier PEPPOL identifier
     * @param string $scheme Scheme code
     * @return array Validation result with success boolean and message
     */
    function validate_peppol_identifier($identifier, $scheme = '0088')
    {
        if (empty($identifier)) {
            return [
                'success' => false,
                'message' => _l('peppol_validation_identifier_required')
            ];
        }

        // Basic alphanumeric validation
        if (!preg_match('/^[0-9A-Za-z]+$/', $identifier)) {
            return [
                'success' => false,
                'message' => _l('peppol_validation_identifier_format')
            ];
        }

        // Scheme-specific validation
        switch ($scheme) {
            case '0088': // GLN - 13 digits
                if (!preg_match('/^\d{13}$/', $identifier)) {
                    return [
                        'success' => false,
                        'message' => 'GLN must be exactly 13 digits'
                    ];
                }
                break;

            case '0060': // DUNS - 9 digits
                if (!preg_match('/^\d{9}$/', $identifier)) {
                    return [
                        'success' => false,
                        'message' => 'DUNS must be exactly 9 digits'
                    ];
                }
                break;

            case '0184': // Danish CVR - 8 digits
                if (!preg_match('/^\d{8}$/', $identifier)) {
                    return [
                        'success' => false,
                        'message' => 'Danish CVR must be exactly 8 digits'
                    ];
                }
                break;
        }

        return [
            'success' => true,
            'message' => 'Valid PEPPOL identifier'
        ];
    }
}

if (!function_exists('get_peppol_provider_icon')) {
    /**
     * Get icon class for PEPPOL provider
     * 
     * @param string $provider Provider name
     * @return string CSS icon class
     */
    function get_peppol_provider_icon($provider)
    {
        $icons = [
            'ademico' => 'fa fa-cloud',
            'unit4' => 'fa fa-server',
            'recommand' => 'fa fa-cogs'
        ];

        return isset($icons[$provider]) ? $icons[$provider] : 'fa fa-plug';
    }
}

if (!function_exists('format_peppol_file_size')) {
    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    function format_peppol_file_size($bytes)
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}

if (!function_exists('get_peppol_environment_badge')) {
    /**
     * Get environment badge for display
     * 
     * @param string $environment Environment (live/sandbox)
     * @return string HTML badge
     */
    function get_peppol_environment_badge($environment = null)
    {
        if (!$environment) {
            $environment = get_option('peppol_environment', 'sandbox');
        }

        if ($environment === 'live') {
            return '<span class="label label-success">Live</span>';
        } else {
            return '<span class="label label-warning">Sandbox</span>';
        }
    }
}

if (!function_exists('render_peppol_help_tooltip')) {
    /**
     * Render help tooltip icon with content
     * 
     * @param string $content Tooltip content
     * @param string $placement Tooltip placement (top, bottom, left, right)
     * @return string HTML tooltip icon
     */
    function render_peppol_help_tooltip($content, $placement = 'top')
    {
        return '<i class="fa fa-question-circle text-muted" data-toggle="tooltip" data-placement="' . $placement . '" title="' . htmlspecialchars($content) . '"></i>';
    }
}

if (!function_exists('get_peppol_providers')) {
    /**
     * Get available PEPPOL access point providers
     * 
     * @return array Array of available providers
     */
    function get_peppol_providers()
    {
        // Load the factory and get providers
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_provider_factory');

        $providers = Peppol_provider_factory::get_all_providers();

        return hooks()->apply_filters('peppol_providers', $providers);
    }
}

if (!function_exists('get_active_peppol_provider')) {
    /**
     * Get currently active PEPPOL provider
     * 
     * @return string Active provider key
     */
    function get_active_peppol_provider()
    {
        return get_option('peppol_active_provider', 'ademico');
    }
}

if (!function_exists('is_peppol_configured')) {
    /**
     * Check if PEPPOL is properly configured
     * 
     * @return bool True if configured, false otherwise
     */
    function is_peppol_configured()
    {
        $provider = get_active_peppol_provider();

        // Load the factory and check configuration
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_provider_factory');

        return Peppol_provider_factory::is_provider_configured($provider);
    }
}

if (!function_exists('get_peppol_provider_config')) {
    /**
     * Get configuration for a specific PEPPOL provider
     * 
     * @param string $provider_key Provider key (optional, defaults to active)
     * @return array|null Provider configuration or null if not found
     */
    function get_peppol_provider_config($provider_key = null)
    {
        if (!$provider_key) {
            $provider_key = get_active_peppol_provider();
        }

        $providers = get_peppol_providers();
        return isset($providers[$provider_key]) ? $providers[$provider_key] : null;
    }
}

if (!function_exists('peppol_provider_supports_feature')) {
    /**
     * Check if a provider supports a specific feature
     * 
     * @param string $feature Feature name
     * @param string $provider_key Provider key (optional, defaults to active)
     * @return bool True if feature is supported
     */
    function peppol_provider_supports_feature($feature, $provider_key = null)
    {
        $config = get_peppol_provider_config($provider_key);

        if (!$config || !isset($config['features'])) {
            return false;
        }

        return in_array($feature, $config['features']);
    }
}

if (!function_exists('get_peppol_provider_webhook_config')) {
    /**
     * Get webhook configuration for a provider
     * 
     * @param string $provider_key Provider key (optional, defaults to active)
     * @return array|null Webhook configuration or null if not found
     */
    function get_peppol_provider_webhook_config($provider_key = null)
    {
        $config = get_peppol_provider_config($provider_key);

        return isset($config['webhooks']) ? $config['webhooks'] : null;
    }
}

if (!function_exists('format_peppol_provider_name')) {
    /**
     * Get formatted provider name
     * 
     * @param string $provider_key Provider key (optional, defaults to active)
     * @return string Provider name or 'Unknown Provider'
     */
    function format_peppol_provider_name($provider_key = null)
    {
        $config = get_peppol_provider_config($provider_key);

        return $config ? $config['name'] : 'Unknown Provider';
    }
}

if (!function_exists('render_peppol_legal_entity_section')) {
    /**
     * Render simple PEPPOL legal entity button for client profile
     * 
     * @param object $client Client object
     * @return string HTML output
     */
    function render_peppol_legal_entity_section($client)
    {
        if (!$client) {
            return '';
        }

        $CI = &get_instance();
        $CI->load->library('peppol/peppol_service');

        // Get legal entity status for active provider only
        $active_provider = get_option('peppol_active_provider', 'ademico');
        $status = $CI->peppol_service->get_client_legal_entity_status($client->userid, $active_provider);
        $providers = get_peppol_providers();
        $provider_name = isset($providers[$active_provider]) ? $providers[$active_provider]['name'] : $active_provider;
        
        // Check if required PEPPOL fields are set
        $peppol_identifier = get_custom_field_value($client->userid, 'peppol_identifier', 'customers');
        $peppol_scheme = get_custom_field_value($client->userid, 'peppol_scheme', 'customers');
        $has_required_fields = !empty($peppol_identifier) && !empty($peppol_scheme);

        // Load the view with the variables
        $data = [
            'client' => $client,
            'status' => $status,
            'active_provider' => $active_provider,
            'provider_name' => $provider_name,
            'has_required_fields' => $has_required_fields,
            'peppol_identifier' => $peppol_identifier,
            'peppol_scheme' => $peppol_scheme
        ];

        return $CI->load->view('peppol/client/legal_entity_section', $data, true);
    }
}