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
        
        $this->provider = new $provider_class();
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
                'test_endpoints' => ['connectivity', 'legal_entities'],
                'mock_credentials' => [
                    'peppol_ademico_oauth2_client_identifier' => 'test_client_id',
                    'peppol_ademico_oauth2_client_secret' => 'test_client_secret'
                ]
            ],
            'unit4' => [
                'supports_legal_entities' => false,
                'auth_type' => 'basic',
                'required_fields' => ['username', 'password'],
                'test_endpoints' => ['connectivity'],
                'mock_credentials' => [
                    'peppol_unit4_username' => 'test_user',
                    'peppol_unit4_password' => 'test_pass'
                ]
            ],
            'recommand' => [
                'supports_legal_entities' => false,
                'auth_type' => 'api_key',
                'required_fields' => ['api_key', 'company_id'],
                'test_endpoints' => ['connectivity'],
                'mock_credentials' => [
                    'peppol_recommand_api_key' => 'test_api_key',
                    'peppol_recommand_company_id' => 'test_company'
                ]
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
     * Setup mock environment for testing
     */
    protected function setup_test_environment()
    {
        // Set mock options for current provider
        global $mock_options;
        $mock_options = $this->test_config[$this->provider_name]['mock_credentials'];
        $mock_options['peppol_environment'] = 'sandbox';
        
        $this->setup_mock_functions();
    }

    /**
     * Setup mock CodeIgniter functions
     */
    private function setup_mock_functions()
    {
        // Mock CI instance if needed
        if (!function_exists('get_instance')) {
            function get_instance() {
                return new MockCI();
            }
        }
    }

    /**
     * Get current provider configuration
     */
    public function get_provider_config()
    {
        return $this->test_config[$this->provider_name];
    }
}

/**
 * Mock CodeIgniter class for testing
 */
class MockCI
{
    public $session;

    public function __construct()
    {
        $this->session = new MockSession();
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
        // Mock logging - could write to file in real implementation
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

/**
 * Continuation of BaseProviderTest class methods
 */
abstract class BaseProviderTest
{
    // ... (class properties and other methods are above)

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

    // Abstract methods that concrete test classes must implement
    abstract public function test_connection();
    abstract public function test_interface_compliance();
}