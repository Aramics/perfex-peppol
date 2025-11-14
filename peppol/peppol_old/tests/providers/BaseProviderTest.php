<?php

// Allow CLI execution for testing
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)) . '/');
}

require_once(__DIR__ . '/../../libraries/providers/Peppol_provider_interface.php');

/**
 * Base Provider Test Class
 * 
 * Provides common testing functionality that can be used across all PEPPOL providers
 * Supports provider switching for comprehensive testing
 */
abstract class BaseProviderTest
{
    protected $provider;
    protected $provider_name;
    protected $test_config;
    protected $mock_data;

    // Available providers for testing
    const AVAILABLE_PROVIDERS = [
        'ademico' => 'Ademico_provider',
        'unit4' => 'Unit4_provider',
        'recommand' => 'Recommand_provider'
    ];

    public function __construct($provider_name = 'ademico')
    {
        $this->provider_name = $provider_name;
        $this->load_test_config();
        $this->setup_mock_data();
        $this->setup_test_environment();
        $this->init_provider();
    }

    /**
     * Switch to a different provider for testing
     */
    public function switch_provider($provider_name)
    {
        if (!isset(self::AVAILABLE_PROVIDERS[$provider_name])) {
            throw new Exception("Unknown provider: {$provider_name}");
        }

        $this->provider_name = $provider_name;
        $this->load_test_config();
        $this->setup_test_environment();
        $this->init_provider();
        
        echo "✅ Switched to provider: {$provider_name}\n";
    }

    /**
     * Initialize the provider based on current provider_name
     */
    protected function init_provider()
    {
        $provider_class = self::AVAILABLE_PROVIDERS[$this->provider_name];
        $provider_file = __DIR__ . "/../../libraries/providers/{$provider_class}.php";
        
        if (!file_exists($provider_file)) {
            throw new Exception("Provider file not found: {$provider_file}");
        }
        
        require_once($provider_file);
        
        if (!class_exists($provider_class)) {
            throw new Exception("Provider class not found: {$provider_class}");
        }
        
        // Initialize provider - it will use get_option() which reads from $mock_options
        $this->provider = new $provider_class();
        
        // Force provider to reload config with test credentials
        if (method_exists($this->provider, 'reload_config')) {
            $this->provider->reload_config();
        }
    }

    /**
     * Load test configuration for current provider
     */
    protected function load_test_config()
    {
        $this->test_config = [
            'ademico' => [
                'supports_legal_entities' => true,
                'auth_type' => 'oauth2',
                'required_fields' => ['client_id', 'client_secret'],
                'test_endpoints' => ['connectivity', 'legal_entities']
            ],
            'unit4' => [
                'supports_legal_entities' => false,
                'auth_type' => 'basic',
                'required_fields' => ['username', 'password'],
                'test_endpoints' => ['connectivity']
            ],
            'recommand' => [
                'supports_legal_entities' => false,
                'auth_type' => 'api_key',
                'required_fields' => ['api_key', 'company_id'],
                'test_endpoints' => ['connectivity']
            ]
        ];
    }

    /**
     * Setup mock data for testing
     */
    protected function setup_mock_data()
    {
        $this->mock_data = [
            'legal_entity' => [
                'name' => 'Test Company Ltd',
                'identifier' => '12345678',
                'scheme_id' => '0088',
                'registration_number' => 'REG123456',
                'vat_number' => 'VAT123456789',
                'address' => [
                    'street' => '123 Test Street',
                    'city' => 'Test City',
                    'postal_code' => '12345',
                    'country' => 'Test Country',
                    'country_code' => 'TC'
                ],
                'contact' => [
                    'name' => 'Test Contact',
                    'email' => 'test@company.com',
                    'phone' => '+1234567890'
                ],
                'peppol_identifier' => '0088:12345678',
                'peppol_scheme' => '0088',
                'document_types' => ['invoice', 'credit_note']
            ],
            'invoice' => [
                'id' => 1,
                'number' => 'INV-2024-001',
                'amount' => 1000.00,
                'currency' => 'EUR'
            ],
            'client' => [
                'id' => 1,
                'company' => 'Test Client Ltd',
                'peppol_identifier' => '0088:87654321',
                'peppol_scheme' => '0088'
            ],
            'ubl_content' => '<?xml version="1.0" encoding="UTF-8"?><Invoice><!-- Sample UBL --></Invoice>'
        ];
    }

    /**
     * Setup test environment with test credentials from UI settings
     */
    protected function setup_test_environment()
    {
        // Set test options for current provider
        global $mock_options;
        $mock_options = [];
        
        // Load test credentials from UI settings (what user configured)
        $test_credentials = $this->load_test_credentials_from_ui_settings();
        
        if (empty($test_credentials)) {
            throw new Exception("❌ No test credentials configured for provider '{$this->provider_name}'. Please configure test credentials in PEPPOL Settings > Provider Settings before running tests.");
        }
        
        $mock_options = $test_credentials;
        $this->log("✅ Loading test credentials from UI settings");
        
        // Always use sandbox environment for testing
        $mock_options['peppol_environment'] = 'sandbox';
        
        $this->log("Using test credentials for {$this->provider_name} in sandbox environment");
    }
    
    /**
     * Load test credentials from UI settings (what user configured in admin panel)
     */
    private function load_test_credentials_from_ui_settings()
    {
        $test_credentials = [];
        
        switch ($this->provider_name) {
            case 'ademico':
                $client_id = $this->get_ui_setting('peppol_ademico_oauth2_client_identifier_test');
                $client_secret = $this->get_ui_setting('peppol_ademico_oauth2_client_secret_test');
                
                if (!empty($client_id) && !empty($client_secret)) {
                    // Map test credentials to production setting names so provider can find them
                    $test_credentials = [
                        'peppol_ademico_oauth2_client_identifier' => $client_id,
                        'peppol_ademico_oauth2_client_secret' => $client_secret
                    ];
                }
                break;
                
            case 'unit4':
                $username = $this->get_ui_setting('peppol_unit4_username_test');
                $password = $this->get_ui_setting('peppol_unit4_password_test');
                
                if (!empty($username) && !empty($password)) {
                    // Map test credentials to production setting names so provider can find them
                    $test_credentials = [
                        'peppol_unit4_username' => $username,
                        'peppol_unit4_password' => $password
                    ];
                }
                break;
                
            case 'recommand':
                $api_key = $this->get_ui_setting('peppol_recommand_api_key_test');
                $company_id = $this->get_ui_setting('peppol_recommand_company_id_test');
                
                if (!empty($api_key) && !empty($company_id)) {
                    // Map test credentials to production setting names so provider can find them
                    $test_credentials = [
                        'peppol_recommand_api_key' => $api_key,
                        'peppol_recommand_company_id' => $company_id
                    ];
                }
                break;
        }
        
        return $test_credentials;
    }
    
    /**
     * Get UI setting value (what user configured in admin panel)
     */
    private function get_ui_setting($key)
    {
        // Try to get from get_option function (production environment with real database)
        if (function_exists('get_option')) {
            return get_option($key, ''); // Return empty string if not found
        }
        
        // Fallback: Check dedicated test settings file (for standalone testing)
        $test_settings_file = __DIR__ . '/../test_settings.php';
        if (file_exists($test_settings_file)) {
            $test_settings = require $test_settings_file;
            if (isset($test_settings[$key])) {
                return $test_settings[$key];
            }
        }
        
        // No setting found - return empty string
        return '';
    }

    /**
     * Get current provider configuration
     */
    public function get_provider_config()
    {
        return $this->test_config[$this->provider_name];
    }

    /**
     * Check if current provider supports a feature
     */
    public function provider_supports($feature)
    {
        $config = $this->get_provider_config();
        return isset($config["supports_{$feature}"]) ? $config["supports_{$feature}"] : false;
    }

    /**
     * Assert test result with provider context
     */
    protected function assert($condition, $message, $expected = null, $actual = null)
    {
        $status = $condition ? '✅ PASS' : '❌ FAIL';
        $provider_context = "[{$this->provider_name}] ";
        
        echo $provider_context . $status . ": " . $message;
        
        if ($expected !== null && $actual !== null) {
            echo " (Expected: " . json_encode($expected) . ", Actual: " . json_encode($actual) . ")";
        }
        
        echo "\n";
        
        if (!$condition) {
            throw new Exception("Test failed: {$message}");
        }
    }

    /**
     * Log test information
     */
    protected function log($message)
    {
        echo "[{$this->provider_name}] ℹ️  {$message}\n";
    }

    /**
     * Validate API response structure and success status
     */
    protected function validate_api_response($result, $operation_name, $expect_success = null)
    {
        // Basic structure validation
        $this->assert(
            is_array($result),
            "{$operation_name} returns array"
        );

        $this->assert(
            isset($result['success']),
            "{$operation_name} result has 'success' field"
        );

        $this->assert(
            isset($result['message']),
            "{$operation_name} result has 'message' field"
        );

        // If we don't care about success/failure, stop here
        if ($expect_success === null) {
            return $result;
        }

        // Check actual operation result
        if (!$result['success']) {
            $this->log("⚠️  {$operation_name} failed: " . $result['message']);
            
            // If we expected success but got failure, that's an error
            if ($expect_success === true) {
                throw new Exception("{$operation_name} failed: " . $result['message']);
            }
            
            // If we expected failure and got failure, that's fine
            $this->assert(true, "{$operation_name} failed as expected");
            
        } else {
            // Success case
            if ($expect_success === false) {
                throw new Exception("{$operation_name} succeeded when failure was expected");
            }
            
            $this->log("✅ {$operation_name} succeeded: " . $result['message']);
            $this->assert(true, "{$operation_name} succeeded");
        }

        return $result;
    }


    // Abstract methods that concrete test classes must implement
    abstract public function test_connection();
    abstract public function test_interface_compliance();
}

/**
 * Mock CodeIgniter class for testing
 */
class MockCI
{
    public $session;
    public $load;
    public $invoices_model;

    public function __construct()
    {
        $this->session = new MockSession();
        $this->load = new MockLoader();
        $this->invoices_model = new MockInvoicesModel();
    }
}

/**
 * Mock Invoices Model for testing
 */
class MockInvoicesModel
{
    public function get_invoice_items($invoice_id)
    {
        // Return mock invoice items (ignoring $invoice_id for testing)
        return [
            (object) [
                'id' => 1,
                'description' => 'Test Item 1',
                'qty' => 2,
                'rate' => 100.00,
                'unit' => 'each'
            ]
        ];
    }
}

/**
 * Mock Loader class for testing
 */
class MockLoader
{
    public function model($model)
    {
        // Mock model loading - return true for successful load (ignoring $model for testing)
        return true;
    }
}

/**
 * Mock Session class for testing
 */
class MockSession
{
    private $data = [];

    public function userdata($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set_userdata($key, $value)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->data[$k] = $v;
            }
        } else {
            $this->data[$key] = $value;
        }
    }

    public function unset_userdata($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                unset($this->data[$k]);
            }
        } else {
            unset($this->data[$key]);
        }
    }
}

// Mock functions need to be defined at global scope
if (!function_exists('get_instance')) {
    function get_instance() {
        return new MockCI();
    }
}

if (!function_exists('get_option')) {
    function get_option($key, $default = null) {
        global $mock_options;
        return isset($mock_options[$key]) ? $mock_options[$key] : $default;
    }
}

if (!function_exists('get_staff_user_id')) {
    function get_staff_user_id() {
        return 1;
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message) {
        // Mock logging
        error_log("[$level] $message");
    }
}

if (!function_exists('format_invoice_number')) {
    function format_invoice_number($invoice_id) {
        return "INV-" . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('db_prefix')) {
    function db_prefix() {
        return 'tbl_';
    }
}