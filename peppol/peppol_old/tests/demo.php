<?php

// Allow CLI execution for testing
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/');
}

/**
 * PEPPOL Provider Test Demo
 * 
 * Demonstrates provider switching capabilities and basic testing functionality
 */

require_once(__DIR__ . '/providers/LegalEntityTest.php');
require_once(__DIR__ . '/providers/DocumentTest.php');

class PeppolTestDemo
{
    public function demonstrate_provider_switching()
    {
        echo "🎯 PEPPOL Provider Switching Demo\n";
        echo str_repeat("=", 50) . "\n\n";

        // Start with Ademico provider
        $test = new LegalEntityTest('ademico');
        
        echo "📋 1. Testing Ademico Provider (OAuth2 + Legal Entities)\n";
        echo str_repeat("-", 50) . "\n";
        $this->safe_run(function() use ($test) {
            $test->test_interface_compliance();
            echo "   ✅ Interface compliance verified\n";
        });

        // Switch to Unit4 provider
        echo "\n🔄 Switching to Unit4 provider...\n\n";
        $test->switch_provider('unit4');
        
        echo "📋 2. Testing Unit4 Provider (Basic Auth + No Legal Entities)\n";
        echo str_repeat("-", 50) . "\n";
        $this->safe_run(function() use ($test) {
            $test->test_interface_compliance();
            echo "   ✅ Interface compliance verified\n";
            echo "   ℹ️  Legal entities not supported (as expected)\n";
        });

        // Switch to Recommand provider
        echo "\n🔄 Switching to Recommand provider...\n\n";
        $test->switch_provider('recommand');
        
        echo "📋 3. Testing Recommand Provider (API Key + No Legal Entities)\n";
        echo str_repeat("-", 50) . "\n";
        $this->safe_run(function() use ($test) {
            $test->test_interface_compliance();
            echo "   ✅ Interface compliance verified\n";
            echo "   ℹ️  Legal entities not supported (as expected)\n";
        });

        echo "\n🎉 Provider switching demonstration completed!\n";
    }

    public function demonstrate_feature_detection()
    {
        echo "\n🔍 PEPPOL Feature Detection Demo\n";
        echo str_repeat("=", 50) . "\n\n";

        $providers = ['ademico', 'unit4', 'recommand'];
        
        foreach ($providers as $provider_name) {
            $test = new LegalEntityTest($provider_name);
            $config = $test->get_provider_config();
            
            echo "📋 {$provider_name} Provider Capabilities:\n";
            echo "   🔐 Auth Type: {$config['auth_type']}\n";
            echo "   🏢 Legal Entities: " . ($config['supports_legal_entities'] ? '✅ Supported' : '❌ Not Supported') . "\n";
            echo "   🔧 Required Fields: " . implode(', ', $config['required_fields']) . "\n";
            echo "   🎯 Test Endpoints: " . implode(', ', $config['test_endpoints']) . "\n";
            echo "\n";
        }
    }

    public function demonstrate_mock_data()
    {
        echo "📝 PEPPOL Mock Data Demo\n";
        echo str_repeat("=", 50) . "\n\n";

        $test = new LegalEntityTest('ademico');
        
        echo "🏢 Sample Legal Entity Data:\n";
        echo json_encode($test->mock_data['legal_entity'], JSON_PRETTY_PRINT) . "\n\n";
        
        echo "📄 Sample Invoice Data:\n";
        echo json_encode($test->mock_data['invoice'], JSON_PRETTY_PRINT) . "\n\n";
        
        echo "👤 Sample Client Data:\n";
        echo json_encode($test->mock_data['client'], JSON_PRETTY_PRINT) . "\n\n";
    }

    public function demonstrate_cross_provider_compatibility()
    {
        echo "🔄 Cross-Provider Compatibility Demo\n";
        echo str_repeat("=", 50) . "\n\n";

        $providers = ['ademico', 'unit4', 'recommand'];
        $methods = ['get_legal_entities', 'create_legal_entity', 'send_document', 'test_connection'];
        
        echo "Method Availability Matrix:\n";
        echo str_pad("Provider", 12) . " | " . implode(" | ", array_map(function($m) { 
            return str_pad(substr($m, 0, 12), 12); 
        }, $methods)) . "\n";
        echo str_repeat("-", 12 + 4 * 15) . "\n";

        foreach ($providers as $provider_name) {
            $test = new LegalEntityTest($provider_name);
            echo str_pad($provider_name, 12) . " | ";
            
            foreach ($methods as $method) {
                $has_method = method_exists($test->provider, $method) ? '✅' : '❌';
                echo str_pad($has_method, 12) . " | ";
            }
            echo "\n";
        }
    }

    public function run_full_demo()
    {
        echo "🚀 PEPPOL Provider Test Suite - Full Demo\n";
        echo str_repeat("=", 80) . "\n";
        echo "This demo showcases the provider switching capabilities\n";
        echo "and cross-provider compatibility testing features.\n\n";

        try {
            $this->demonstrate_provider_switching();
            $this->demonstrate_feature_detection();
            $this->demonstrate_mock_data();
            $this->demonstrate_cross_provider_compatibility();
            
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "🎉 Demo completed successfully!\n";
            echo "💡 To run actual tests, use: php run_tests.php all\n";
            
        } catch (Exception $e) {
            echo "❌ Demo failed: " . $e->getMessage() . "\n";
        }
    }

    private function safe_run($callback)
    {
        try {
            $callback();
        } catch (Exception $e) {
            echo "   ⚠️  " . $e->getMessage() . "\n";
        }
    }
}

// Run demo if called directly
if (php_sapi_name() === 'cli') {
    $demo = new PeppolTestDemo();
    $demo->run_full_demo();
}