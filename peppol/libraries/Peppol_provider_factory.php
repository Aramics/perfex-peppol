<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Provider Factory
 * 
 * Manages the creation and configuration of PEPPOL access point providers.
 * Uses hook-based provider registration for extensibility.
 */
class Peppol_provider_factory
{
    private static $providers = [];
    private static $provider_configs = [];
    private static $initialized = false;

    /**
     * Initialize the factory by collecting providers via hooks
     */
    public static function init()
    {
        if (self::$initialized) {
            return;
        }
        
        // Collect provider configurations via hooks
        self::$provider_configs = hooks()->apply_filters('peppol_register_providers', []);
        
        self::$initialized = true;
    }

    /**
     * Register a new provider at runtime
     * 
     * @param string $key Provider key
     * @param array $config Provider configuration
     */
    public static function register_provider($key, $config)
    {
        self::init();
        
        // Validate required configuration keys
        $required_keys = ['name', 'class', 'config_fields', 'required_fields', 'endpoints', 'features', 'authentication'];
        foreach ($required_keys as $required_key) {
            if (!isset($config[$required_key])) {
                throw new Exception("Missing required configuration key: {$required_key}");
            }
        }
        
        self::$provider_configs[$key] = $config;
        
        // Clear cached provider instance if it exists
        if (isset(self::$providers[$key])) {
            unset(self::$providers[$key]);
        }
    }

    /**
     * Unregister a provider
     * 
     * @param string $key Provider key
     */
    public static function unregister_provider($key)
    {
        self::init();
        
        if (isset(self::$provider_configs[$key])) {
            unset(self::$provider_configs[$key]);
        }
        
        if (isset(self::$providers[$key])) {
            unset(self::$providers[$key]);
        }
    }

    /**
     * Get all registered providers
     * 
     * @return array Associative array of provider configurations
     */
    public static function get_all_providers()
    {
        self::init();
        return self::$provider_configs;
    }

    /**
     * Check if a provider is registered
     * 
     * @param string $provider_name Provider name
     * @return bool True if provider is registered
     */
    public static function has_provider($provider_name)
    {
        self::init();
        return isset(self::$provider_configs[$provider_name]);
    }

    /**
     * Get a provider instance
     * 
     * @param string|null $provider_name Provider name (defaults to active provider)
     * @return Peppol_provider_interface Provider instance
     * @throws Exception If provider not found or cannot be instantiated
     */
    public static function get_provider($provider_name = null)
    {
        self::init();
        
        if (!$provider_name) {
            $provider_name = get_active_peppol_provider();
        }
        
        if (!isset(self::$providers[$provider_name])) {
            self::$providers[$provider_name] = self::create_provider($provider_name);
        }
        
        return self::$providers[$provider_name];
    }

    /**
     * Create a new provider instance
     * 
     * @param string $provider_name Provider name
     * @return Peppol_provider_interface Provider instance
     * @throws Exception If provider not found or cannot be created
     */
    private static function create_provider($provider_name)
    {
        if (!isset(self::$provider_configs[$provider_name])) {
            throw new Exception("Provider '{$provider_name}' not found");
        }
        
        $config = self::$provider_configs[$provider_name];
        $class_name = $config['class'];
        $class_file = APPPATH . 'modules/peppol/libraries/providers/' . $class_name . '.php';
        
        if (!file_exists($class_file)) {
            throw new Exception("Provider class file not found: {$class_file}");
        }
        
        require_once $class_file;
        
        if (!class_exists($class_name)) {
            throw new Exception("Provider class not found: {$class_name}");
        }
        
        $provider = new $class_name();
        
        if (!$provider instanceof Peppol_provider_interface) {
            throw new Exception("Provider '{$provider_name}' does not implement Peppol_provider_interface");
        }
        
        return $provider;
    }

    /**
     * Get provider configuration
     * 
     * @param string $provider_name Provider name
     * @return array|null Provider configuration or null if not found
     */
    public static function get_provider_config($provider_name)
    {
        self::init();
        return self::$provider_configs[$provider_name] ?? null;
    }

    /**
     * Check if a provider is properly configured
     * 
     * @param string $provider_name Provider name
     * @return bool True if provider is configured
     */
    public static function is_provider_configured($provider_name)
    {
        $config = self::get_provider_config($provider_name);
        
        if (!$config) {
            return false;
        }
        
        foreach ($config['required_fields'] as $field) {
            if (empty(get_option($field))) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get provider capabilities
     * 
     * @param string $provider_name Provider name
     * @return array Array of capabilities
     */
    public static function get_provider_capabilities($provider_name)
    {
        $config = self::get_provider_config($provider_name);
        return $config ? $config['features'] : [];
    }

    /**
     * Test connection for a provider
     * 
     * @param string $provider_name Provider name
     * @param string|null $environment Environment to test
     * @return array Test result
     */
    public static function test_provider_connection($provider_name, $environment = null)
    {
        try {
            $provider = self::get_provider($provider_name);
            return $provider->test_connection($environment);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get provider endpoints
     * 
     * @param string $provider_name Provider name
     * @return array Endpoints configuration
     */
    public static function get_provider_endpoints($provider_name)
    {
        $config = self::get_provider_config($provider_name);
        return $config ? $config['endpoints'] : [];
    }

    /**
     * Get provider authentication type
     * 
     * @param string $provider_name Provider name
     * @return string|null Authentication type
     */
    public static function get_provider_auth_type($provider_name)
    {
        $config = self::get_provider_config($provider_name);
        return $config ? $config['authentication'] : null;
    }

    /**
     * Validate provider configuration
     * 
     * @param string $provider_name Provider name
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public static function validate_provider_config($provider_name)
    {
        $config = self::get_provider_config($provider_name);
        
        if (!$config) {
            return [
                'valid' => false,
                'errors' => ["Provider '{$provider_name}' not found"]
            ];
        }
        
        $errors = [];
        
        // Check required fields
        foreach ($config['required_fields'] as $field) {
            if (empty(get_option($field))) {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Check endpoint configuration
        $environment = get_option('peppol_environment', 'sandbox');
        if (empty($config['endpoints'][$environment])) {
            $errors[] = "No endpoint configured for {$environment} environment";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Clear provider cache
     */
    public static function clear_cache()
    {
        self::$providers = [];
    }
}