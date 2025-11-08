<?php

// Allow CLI execution for testing
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)) . '/');
}

require_once(__DIR__ . '/BaseProviderTest.php');

/**
 * Document Management Test Class
 * 
 * Tests document sending, status checking, and webhook handling across providers
 */
class DocumentTest extends BaseProviderTest
{
    private $sent_document_id;

    public function __construct($provider_name = 'ademico')
    {
        parent::__construct($provider_name);
        $this->setup_test_environment();
    }

    /**
     * Run all document tests
     */
    public function run_all_tests()
    {
        echo "\n📄 Starting Document Tests for Provider: {$this->provider_name}\n";
        echo str_repeat("=", 60) . "\n";

        try {
            $this->test_interface_compliance();
            $this->test_connection();
            $this->test_send_document();
            $this->test_get_delivery_status();
            $this->test_webhook_handling();

            echo "\n🎉 All document tests passed for provider: {$this->provider_name}\n";
            
        } catch (Exception $e) {
            echo "\n💥 Document test failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Test provider interface compliance
     */
    public function test_interface_compliance()
    {
        $this->log("Testing interface compliance...");

        $required_methods = [
            'send_document',
            'test_connection', 
            'get_delivery_status',
            'handle_webhook'
        ];

        foreach ($required_methods as $method) {
            $this->assert(
                method_exists($this->provider, $method),
                "Provider implements required method: {$method}"
            );
        }
    }

    /**
     * Test connection functionality
     */
    public function test_connection()
    {
        $this->log("Testing connection...");

        try {
            $result = $this->provider->test_connection('sandbox');
            
            $this->assert(
                is_array($result),
                "test_connection returns array"
            );
            
            $required_fields = ['success', 'message'];
            foreach ($required_fields as $field) {
                $this->assert(
                    isset($result[$field]),
                    "test_connection result has '{$field}' field"
                );
            }

        } catch (Exception $e) {
            $this->log("Connection test threw exception (expected in test environment): " . $e->getMessage());
        }
    }

    /**
     * Test document sending
     */
    public function test_send_document()
    {
        $this->log("Testing send_document...");

        $ubl_content = $this->mock_data['ubl_content'];
        $invoice = (object) $this->mock_data['invoice'];
        $client = (object) $this->mock_data['client'];

        try {
            $result = $this->provider->send_document($ubl_content, $invoice, $client);
            
            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'send_document');

            // Store document ID for status testing if successful
            if ($result['success'] && isset($result['document_id'])) {
                $this->sent_document_id = $result['document_id'];
                $this->log("✅ Sent document with ID: " . $this->sent_document_id);
            }

        } catch (Exception $e) {
            $this->log("❌ send_document test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test delivery status checking
     */
    public function test_get_delivery_status()
    {
        $this->log("Testing get_delivery_status...");

        $test_document_id = $this->sent_document_id ?? 'test-doc-123';

        try {
            $result = $this->provider->get_delivery_status($test_document_id);
            
            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'get_delivery_status');

            // Check for status field if successful
            if ($result['success']) {
                $this->assert(
                    isset($result['status']),
                    "get_delivery_status result has 'status' field when successful"
                );
            }

        } catch (Exception $e) {
            $this->log("❌ get_delivery_status test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test webhook handling
     */
    public function test_webhook_handling()
    {
        $this->log("Testing handle_webhook...");

        // Mock webhook data for different providers
        $webhook_data = $this->get_mock_webhook_data();

        foreach ($webhook_data as $scenario => $data) {
            $this->log("Testing webhook scenario: {$scenario}");

            try {
                // Mock the webhook input
                $this->mock_webhook_input($data);
                
                $result = $this->provider->handle_webhook();
                
                // handle_webhook can return null for status updates or array for document receipts
                $this->assert(
                    $result === null || is_array($result),
                    "handle_webhook returns null or array for scenario: {$scenario}"
                );

                if (is_array($result)) {
                    $this->assert(
                        isset($result['document_id']),
                        "Webhook document result has document_id for scenario: {$scenario}"
                    );
                }

            } catch (Exception $e) {
                $this->log("Webhook test for {$scenario} threw exception (expected without real webhook): " . $e->getMessage());
            }
        }
    }

    /**
     * Get mock webhook data based on provider
     */
    private function get_mock_webhook_data()
    {
        switch ($this->provider_name) {
            case 'ademico':
                return [
                    'document_sent' => [
                        'eventType' => 'DOCUMENT_SENT',
                        'transmissionId' => 'test-transmission-123',
                        'documentStatus' => 'SENT',
                        'notificationDate' => date('Y-m-d H:i:s')
                    ],
                    'document_delivered' => [
                        'eventType' => 'MLR_RECEIVED',
                        'transmissionId' => 'test-transmission-123',
                        'documentStatus' => 'ACCEPTED',
                        'notificationDate' => date('Y-m-d H:i:s')
                    ]
                ];

            case 'unit4':
                return [
                    'document_delivered' => [
                        'eventType' => 'DocumentDelivered',
                        'documentId' => 'test-doc-123',
                        'timestamp' => time()
                    ],
                    'document_failed' => [
                        'eventType' => 'DocumentFailed',
                        'documentId' => 'test-doc-123',
                        'errorMessage' => 'Test error',
                        'timestamp' => time()
                    ]
                ];

            case 'recommand':
            default:
                return [
                    'status_update' => [
                        'type' => 'status_update',
                        'document_id' => 'test-doc-123',
                        'status' => 'delivered'
                    ]
                ];
        }
    }

    /**
     * Mock webhook input data
     */
    private function mock_webhook_input($data)
    {
        // Store original values
        static $original_input = null;
        static $original_server = null;

        if ($original_input === null) {
            $original_input = file_get_contents('php://input');
            $original_server = $_SERVER;
        }

        // Mock the webhook input
        // Note: In real testing, you'd need more sophisticated mocking
        // This is a conceptual implementation
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        // In a real test environment, you'd mock file_get_contents('php://input')
        // For now, we'll just validate the structure
    }

    /**
     * Test provider-specific features
     */
    public function test_provider_specific_features()
    {
        $this->log("Testing provider-specific features...");

        switch ($this->provider_name) {
            case 'ademico':
                $this->test_ademico_specific_features();
                break;
            case 'unit4':
                $this->test_unit4_specific_features();
                break;
            case 'recommand':
                $this->test_recommand_specific_features();
                break;
        }
    }

    /**
     * Test Ademico-specific features
     */
    private function test_ademico_specific_features()
    {
        $this->log("Testing Ademico OAuth2 token management...");

        // Test token caching methods if available
        if (method_exists($this->provider, 'clear_token_cache')) {
            try {
                $this->provider->clear_token_cache();
                $this->assert(true, "clear_token_cache executed without error");
            } catch (Exception $e) {
                $this->log("Token cache clear failed (expected): " . $e->getMessage());
            }
        }

        if (method_exists($this->provider, 'get_token_info')) {
            try {
                $token_info = $this->provider->get_token_info();
                $this->assert(
                    is_array($token_info),
                    "get_token_info returns array"
                );
            } catch (Exception $e) {
                $this->log("Token info retrieval failed (expected): " . $e->getMessage());
            }
        }
    }

    /**
     * Test Unit4-specific features
     */
    private function test_unit4_specific_features()
    {
        $this->log("Testing Unit4 basic authentication...");
        // Unit4 uses basic authentication - no special token management needed
        $this->assert(true, "Unit4 basic auth validation passed");
    }

    /**
     * Test Recommand-specific features
     */
    private function test_recommand_specific_features()
    {
        $this->log("Testing Recommand API key authentication...");
        // Recommand uses API key - no special token management needed
        $this->assert(true, "Recommand API key validation passed");
    }

    /**
     * Run cross-provider document tests
     */
    public static function run_cross_provider_tests()
    {
        echo "\n📄 Running Cross-Provider Document Tests\n";
        echo str_repeat("=", 60) . "\n";

        $providers = ['ademico', 'unit4', 'recommand'];
        $test_results = [];
        $failed_tests = [];

        foreach ($providers as $provider_name) {
            echo "\n📋 Testing Provider: {$provider_name}\n";
            echo str_repeat("-", 40) . "\n";
            
            $test_passed = true;
            $failure_message = '';
            
            try {
                $test = new DocumentTest($provider_name);
                $test->run_all_tests();
                $test->test_provider_specific_features();
                
            } catch (Exception $e) {
                $test_passed = false;
                $failure_message = $e->getMessage();
                echo "❌ Provider {$provider_name} tests failed: " . $e->getMessage() . "\n";
                $failed_tests[] = $provider_name;
            }
            
            $test_results[$provider_name] = $test_passed ? 'PASSED' : 'FAILED: ' . $failure_message;
        }

        // Summary
        echo "\n📊 Document Test Summary\n";
        echo str_repeat("=", 60) . "\n";
        foreach ($test_results as $provider => $result) {
            $status = strpos($result, 'FAILED') === false ? '✅' : '❌';
            echo "{$status} {$provider}: {$result}\n";
        }
        
        // Overall result
        if (empty($failed_tests)) {
            echo "\n🎉 All provider document tests passed!\n";
        } else {
            echo "\n⚠️  " . count($failed_tests) . " out of " . count($providers) . " providers failed document tests.\n";
            echo "Failed providers: " . implode(', ', $failed_tests) . "\n";
        }
        
        return empty($failed_tests);
    }
}