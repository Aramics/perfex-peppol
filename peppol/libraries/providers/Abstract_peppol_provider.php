<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Peppol_provider_interface.php';

/**
 * Abstract PEPPOL Provider Base Class
 * 
 * This abstract class provides common functionality for PEPPOL providers
 * and implements some default behavior that most providers will need.
 */
abstract class Abstract_peppol_provider implements Peppol_provider_interface
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Get base provider information (must override in subclasses)
     */
    abstract public function get_provider_info();

    /**
     * Get the provider ID
     * 
     * @return string Provider unique identifier
     */
    public function get_id()
    {
        $info = $this->get_provider_info();
        return $info['id'] ?: get_class($this);
    }

    /**
     * Default implementation returns common supported documents
     */
    public function supported_documents()
    {
        return ['invoice', 'credit_note'];
    }

    /**
     * Default webhook handler (override in subclasses if webhooks are supported)
     */
    public function webhook($payload)
    {
        return [
            'success' => false,
            'message' => 'Webhooks not supported by this provider'
        ];
    }

    /**
     * Default implementation for getting settings
     */
    public function get_settings()
    {
        $inputs = $this->get_setting_inputs();
        $settings = [];

        foreach ($inputs as $key => $input) {
            // For hidden and readonly fields, always use the default value
            if ($input['type'] === 'hidden' || $input['type'] === 'readonly') {
                $settings[$key] = $input['default'] ?? '';
            } else {
                $option_name = "peppol_{$this->get_id()}_{$key}";
                $settings[$key] = get_option($option_name, $input['default'] ?? '');
            }
        }

        return $settings;
    }

    /**
     * Default implementation for setting settings
     */
    public function set_settings($settings)
    {
        try {
            $inputs = $this->get_setting_inputs();

            foreach ($settings as $key => $value) {
                // Skip hidden and readonly fields - they should not be stored
                if (isset($inputs[$key]) && ($inputs[$key]['type'] === 'hidden' || $inputs[$key]['type'] === 'readonly')) {
                    continue;
                }

                $option_name = "peppol_{$this->get_id()}_{$key}";
                update_option($option_name, $value);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Default setting inputs (override in subclasses)
     */
    public function get_setting_inputs()
    {
        return [
            'environment' => [
                'type' => 'select',
                'label' => _l('peppol_environment'),
                'options' => ['sandbox' => _l('peppol_sandbox'), 'production' => _l('peppol_production')],
                'default' => 'sandbox',
                'required' => true
            ]
        ];
    }

    /**
     * Utility method to render form inputs from input definitions using Perfex helpers
     */
    public function render_setting_inputs($inputs = [], $current_values = [])
    {
        // If inputs not provided, get them from the provider
        if (empty($inputs)) {
            $inputs = $this->get_setting_inputs();
        }

        // If current values not provided, get them from the provider
        if (empty($current_values)) {
            $current_values = $this->get_settings();
        }

        $output = '';

        foreach ($inputs as $field_name => $config) {
            $value = $current_values[$field_name] ?? $config['default'] ?? '';
            $field_name_with_prefix = "settings[peppol_{$this->get_id()}_{$field_name}]";
            $label = $config['label'] ?? ucfirst($field_name);
            $attributes = $config['attributes'] ?? [];

            // Add placeholder if present
            if (!empty($config['placeholder'])) {
                $attributes['placeholder'] = $config['placeholder'];
            }

            // Add help tooltip icon before input if present
            if (!empty($config['help'])) {
                $output .= '<i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="' . e($config['help']) . '"></i>';
            }

            // Handle hidden fields - don't render them
            if ($config['type'] === 'hidden') {
                continue;
            }

            // Handle readonly fields as readonly text inputs
            if ($config['type'] === 'readonly') {
                $attributes['readonly'] = true;
                $output .= render_input($field_name_with_prefix, $label, $value, 'text', $attributes);
            } else {
                switch ($config['type']) {
                    case 'select':
                        $options = $config['options'] ?? [];
                        // Convert to format expected by render_select: [['id' => key, 'name' => value], ...]
                        $select_options = [];
                        foreach ($options as $key => $option_label) {
                            $select_options[] = ['id' => $key, 'name' => $option_label];
                        }
                        $output .= render_select($field_name_with_prefix, $select_options, ['id', 'name'], $label, $value, $attributes);
                        break;

                    default:
                        // Use render_input as default for all other types including checkbox
                        $output .= render_input($field_name_with_prefix, $label, $value, $config['type'], $attributes);
                        break;
                }
            }
        }

        return $output;
    }
}
