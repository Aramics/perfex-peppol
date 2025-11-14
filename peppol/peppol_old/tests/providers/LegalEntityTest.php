<?php

// Allow CLI execution for testing
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)) . '/');
}

require_once(__DIR__ . '/BaseProviderTest.php');

/**
 * Legal Entity Management Test Class
 * 
 * Tests legal entity CRUD operations across different providers
 * Automatically adapts test expectations based on provider capabilities
 */
class LegalEntityTest extends BaseProviderTest
{
    private $created_entity_id;

    public function __construct($provider_name = 'ademico')
    {
        parent::__construct($provider_name);
        $this->setup_test_environment();
    }

    /**
     * Run all legal entity tests
     */
    public function run_all_tests()
    {
        echo "\n🧪 Starting Legal Entity Tests for Provider: {$this->provider_name}\n";
        echo str_repeat("=", 60) . "\n";

        try {
            $this->test_interface_compliance();
            $this->test_connection();

            if ($this->provider_supports('legal_entities')) {
                $this->test_get_legal_entities();
                $this->test_create_legal_entity();
                $this->test_get_legal_entity();
                $this->test_update_legal_entity();
                $this->test_delete_legal_entity();
            } else {
                $this->test_unsupported_legal_entity_operations();
            }

            echo "\n🎉 All tests passed for provider: {$this->provider_name}\n";
        } catch (Exception $e) {
            echo "\n💥 Test failed: " . $e->getMessage() . "\n";
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
            'handle_webhook',
            'get_legal_entities',
            'create_legal_entity',
            'update_legal_entity',
            'delete_legal_entity',
            'get_legal_entity'
        ];

        foreach ($required_methods as $method) {
            $this->assert(
                method_exists($this->provider, $method),
                "Provider implements required method: {$method}"
            );
        }

        $this->assert(
            $this->provider instanceof Peppol_provider_interface,
            "Provider implements Peppol_provider_interface"
        );
    }

    /**
     * Test connection functionality
     */
    public function test_connection()
    {
        $this->log("Testing connection...");

        // For actual testing, you might want to mock the HTTP requests
        // This is a basic structure test
        try {
            $result = $this->provider->test_connection('sandbox');

            $this->assert(
                is_array($result),
                "test_connection returns array"
            );

            $this->assert(
                isset($result['success']),
                "test_connection result has 'success' field"
            );

            $this->assert(
                isset($result['message']),
                "test_connection result has 'message' field"
            );
        } catch (Exception $e) {
            // Connection might fail in test environment, that's expected
            $this->log("Connection test threw exception (expected in test environment): " . $e->getMessage());
        }
    }

    /**
     * Test getting list of legal entities
     */
    public function test_get_legal_entities()
    {
        $this->log("Testing get_legal_entities...");

        try {
            $result = $this->provider->get_legal_entities();

            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'get_legal_entities');

            // Additional validation for entities field
            $this->assert(
                isset($result['entities']),
                "get_legal_entities result has 'entities' field"
            );

            $this->assert(
                is_array($result['entities']),
                "entities field is an array"
            );
        } catch (Exception $e) {
            $this->log("❌ get_legal_entities test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test creating a legal entity
     */
    public function test_create_legal_entity()
    {
        $this->log("Testing create_legal_entity...");

        $entity_data = $this->mock_data['legal_entity'];

        try {
            $result = $this->provider->create_legal_entity($entity_data);

            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'create_legal_entity');

            // Store entity ID for subsequent tests if successful
            if ($result['success'] && isset($result['entity_id'])) {
                $this->created_entity_id = $result['entity_id'];
                $this->log("✅ Created entity with ID: " . $this->created_entity_id);
            }
        } catch (Exception $e) {
            $this->log("❌ create_legal_entity test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test getting a specific legal entity
     */
    public function test_get_legal_entity()
    {
        $this->log("Testing get_legal_entity...");

        $test_entity_id = $this->created_entity_id ?? 'test-entity-123';

        try {
            $result = $this->provider->get_legal_entity($test_entity_id);

            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'get_legal_entity');

            // Additional validation for entity field
            $this->assert(
                array_key_exists('entity', $result),
                "get_legal_entity result has 'entity' field (can be null)"
            );
        } catch (Exception $e) {
            $this->log("❌ get_legal_entity test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test updating a legal entity
     */
    public function test_update_legal_entity()
    {
        $this->log("Testing update_legal_entity...");

        $test_entity_id = $this->created_entity_id ?? 'test-entity-123';
        $update_data = array_merge($this->mock_data['legal_entity'], [
            'name' => 'Updated Test Company Ltd'
        ]);

        try {
            $result = $this->provider->update_legal_entity($test_entity_id, $update_data);

            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'update_legal_entity');
        } catch (Exception $e) {
            $this->log("❌ update_legal_entity test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test deleting a legal entity
     */
    public function test_delete_legal_entity()
    {
        $this->log("Testing delete_legal_entity...");

        $test_entity_id = $this->created_entity_id ?? 'test-entity-123';

        try {
            $result = $this->provider->delete_legal_entity($test_entity_id);

            // Validate response structure and handle expected auth errors
            $result = $this->validate_api_response($result, 'delete_legal_entity');
        } catch (Exception $e) {
            $this->log("❌ delete_legal_entity test failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test that unsupported providers return appropriate responses
     */
    public function test_unsupported_legal_entity_operations()
    {
        $this->log("Testing unsupported legal entity operations...");

        $result = $this->provider->get_legal_entities();

        $this->assert(
            is_array($result),
            "get_legal_entities returns array even when unsupported"
        );

        $this->assert(
            $result['success'] === false,
            "get_legal_entities returns success=false for unsupported provider"
        );

        $this->assert(
            strpos($result['message'], 'not supported') !== false,
            "get_legal_entities returns 'not supported' message"
        );

        // Test create operation
        $result = $this->provider->create_legal_entity($this->mock_data['legal_entity']);

        $this->assert(
            $result['success'] === false,
            "create_legal_entity returns success=false for unsupported provider"
        );
    }

    /**
     * Run cross-provider tests
     */
    public static function run_cross_provider_tests()
    {
        echo "\n🔄 Running Cross-Provider Tests\n";
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
                $test = new LegalEntityTest($provider_name);
                $test->run_all_tests();
            } catch (Exception $e) {
                $test_passed = false;
                $failure_message = $e->getMessage();
                echo "❌ Provider {$provider_name} tests failed: " . $e->getMessage() . "\n";
                $failed_tests[] = $provider_name;
            }

            $test_results[$provider_name] = $test_passed ? 'PASSED' : 'FAILED: ' . $failure_message;
        }

        // Summary
        echo "\n📊 Test Summary\n";
        echo str_repeat("=", 60) . "\n";
        foreach ($test_results as $provider => $result) {
            $status = strpos($result, 'FAILED') === false ? '✅' : '❌';
            echo "{$status} {$provider}: {$result}\n";
        }

        // Overall result
        if (empty($failed_tests)) {
            echo "\n🎉 All provider tests passed!\n";
        } else {
            echo "\n⚠️  " . count($failed_tests) . " out of " . count($providers) . " providers failed tests.\n";
            echo "Failed providers: " . implode(', ', $failed_tests) . "\n";
        }

        return empty($failed_tests);
    }
}