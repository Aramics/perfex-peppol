<?php

// Allow CLI execution for testing
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/');
}

/**
 * PEPPOL Provider Test Runner
 * 
 * Main test runner that executes all provider tests with switching capability
 */

// Ensure we can load the test classes
require_once(__DIR__ . '/providers/LegalEntityTest.php');
require_once(__DIR__ . '/providers/DocumentTest.php');

class PeppolTestRunner
{
    private $test_results = [];
    private $start_time;

    public function __construct()
    {
        $this->start_time = microtime(true);
        echo "🚀 PEPPOL Provider Test Suite\n";
        echo str_repeat("=", 80) . "\n";
        echo "Start Time: " . date('Y-m-d H:i:s') . "\n\n";
    }

    /**
     * Run all tests across all providers
     */
    public function run_all_tests()
    {
        echo "🔄 Running Complete Test Suite Across All Providers\n";
        echo str_repeat("=", 80) . "\n";

        // Run Legal Entity Tests
        try {
            echo "\n📊 LEGAL ENTITY TESTS\n";
            echo str_repeat("=", 40) . "\n";
            LegalEntityTest::run_cross_provider_tests();
            $this->test_results['legal_entities'] = 'PASSED';
        } catch (Exception $e) {
            $this->test_results['legal_entities'] = 'FAILED: ' . $e->getMessage();
            echo "❌ Legal Entity tests failed: " . $e->getMessage() . "\n";
        }

        // Run Document Tests
        try {
            echo "\n📄 DOCUMENT TESTS\n";
            echo str_repeat("=", 40) . "\n";
            DocumentTest::run_cross_provider_tests();
            $this->test_results['documents'] = 'PASSED';
        } catch (Exception $e) {
            $this->test_results['documents'] = 'FAILED: ' . $e->getMessage();
            echo "❌ Document tests failed: " . $e->getMessage() . "\n";
        }

        $this->print_final_summary();
    }

    /**
     * Run tests for a specific provider
     */
    public function run_provider_tests($provider_name)
    {
        echo "🎯 Running Tests for Provider: {$provider_name}\n";
        echo str_repeat("=", 80) . "\n";

        $provider_results = [];

        // Legal Entity Tests
        try {
            $legal_test = new LegalEntityTest($provider_name);
            $legal_test->run_all_tests();
            $provider_results['legal_entities'] = 'PASSED';
        } catch (Exception $e) {
            $provider_results['legal_entities'] = 'FAILED: ' . $e->getMessage();
            echo "❌ Legal Entity tests failed: " . $e->getMessage() . "\n";
        }

        // Document Tests  
        try {
            $doc_test = new DocumentTest($provider_name);
            $doc_test->run_all_tests();
            $provider_results['documents'] = 'PASSED';
        } catch (Exception $e) {
            $provider_results['documents'] = 'FAILED: ' . $e->getMessage();
            echo "❌ Document tests failed: " . $e->getMessage() . "\n";
        }

        $this->print_provider_summary($provider_name, $provider_results);
    }

    /**
     * Run a specific test type across all providers or for specific provider
     */
    public function run_test_type($test_type, $provider_name = null)
    {
        if ($provider_name) {
            echo "🧪 Running {$test_type} Tests for Provider: {$provider_name}\n";
            echo str_repeat("=", 80) . "\n";
            
            if (!in_array($provider_name, ['ademico', 'unit4', 'recommand'])) {
                echo "❌ Unknown provider: {$provider_name}\n";
                echo "Available providers: ademico, unit4, recommand\n";
                return;
            }
            
            $this->run_specific_test_for_provider($test_type, $provider_name);
        } else {
            echo "🧪 Running {$test_type} Tests Across All Providers\n";
            echo str_repeat("=", 80) . "\n";

            switch (strtolower($test_type)) {
                case 'legal':
                case 'legal_entities':
                    $success = LegalEntityTest::run_cross_provider_tests();
                    $this->test_results[$test_type] = $success ? 'PASSED' : 'FAILED';
                    break;
                case 'document':
                case 'documents':
                    $success = DocumentTest::run_cross_provider_tests();
                    $this->test_results[$test_type] = $success ? 'PASSED' : 'FAILED';
                    break;
                default:
                    echo "❌ Unknown test type: {$test_type}\n";
                    echo "Available types: legal_entities, documents\n";
                    break;
            }
        }
    }

    /**
     * Run specific test type for specific provider
     */
    private function run_specific_test_for_provider($test_type, $provider_name)
    {
        try {
            switch (strtolower($test_type)) {
                case 'legal':
                case 'legal_entities':
                    $test = new LegalEntityTest($provider_name);
                    $test->run_all_tests();
                    echo "✅ {$provider_name} legal entity tests passed\n";
                    break;
                case 'document':
                case 'documents':
                    $test = new DocumentTest($provider_name);
                    $test->run_all_tests();
                    echo "✅ {$provider_name} document tests passed\n";
                    break;
                default:
                    echo "❌ Unknown test type: {$test_type}\n";
                    echo "Available types: legal_entities, documents\n";
                    break;
            }
        } catch (Exception $e) {
            echo "❌ {$provider_name} {$test_type} tests failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Print final test summary
     */
    private function print_final_summary()
    {
        $end_time = microtime(true);
        $duration = round($end_time - $this->start_time, 2);

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🏁 FINAL TEST SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        echo "Duration: {$duration} seconds\n";
        echo "End Time: " . date('Y-m-d H:i:s') . "\n\n";

        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($result) {
            return strpos($result, 'FAILED') === false;
        }));

        echo "📊 Overall Results:\n";
        foreach ($this->test_results as $test_type => $result) {
            $status = strpos($result, 'FAILED') === false ? '✅' : '❌';
            echo "{$status} {$test_type}: {$result}\n";
        }

        echo "\n📈 Summary: {$passed_tests}/{$total_tests} test suites passed\n";
        
        if ($passed_tests === $total_tests) {
            echo "🎉 All tests passed successfully!\n";
        } else {
            echo "⚠️  Some tests failed. Please review the results above.\n";
        }
    }

    /**
     * Print provider-specific summary
     */
    private function print_provider_summary($provider_name, $results)
    {
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "📊 {$provider_name} Test Summary\n";
        echo str_repeat("-", 60) . "\n";

        foreach ($results as $test_type => $result) {
            $status = strpos($result, 'FAILED') === false ? '✅' : '❌';
            echo "{$status} {$test_type}: {$result}\n";
        }
    }

    /**
     * Show usage help
     */
    public static function show_help()
    {
        echo "PEPPOL Provider Test Runner\n";
        echo str_repeat("=", 40) . "\n";
        echo "Usage:\n";
        echo "  php run_tests.php [command] [options]\n\n";
        echo "Commands:\n";
        echo "  all                         - Run all tests across all providers\n";
        echo "  provider <name>             - Run tests for specific provider\n";
        echo "  type <test_type>            - Run specific test type across all providers\n";
        echo "  type <test_type> <provider> - Run specific test type for specific provider\n";
        echo "  help                        - Show this help message\n\n";
        echo "Providers:\n";
        echo "  ademico, unit4, recommand\n\n";
        echo "Test Types:\n";
        echo "  legal_entities, documents\n\n";
        echo "Examples:\n";
        echo "  php run_tests.php all\n";
        echo "  php run_tests.php provider ademico\n";
        echo "  php run_tests.php type legal_entities\n";
        echo "  php run_tests.php type legal_entities ademico\n";
        echo "  php run_tests.php type documents unit4\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'help';
    $option = $argv[2] ?? null;

    $runner = new PeppolTestRunner();

    switch ($command) {
        case 'all':
            $runner->run_all_tests();
            break;
        case 'provider':
            if ($option) {
                $runner->run_provider_tests($option);
            } else {
                echo "❌ Please specify a provider name\n";
                PeppolTestRunner::show_help();
            }
            break;
        case 'type':
            if ($option) {
                $provider_option = $argv[3] ?? null;
                $runner->run_test_type($option, $provider_option);
            } else {
                echo "❌ Please specify a test type\n";
                PeppolTestRunner::show_help();
            }
            break;
        case 'help':
        default:
            PeppolTestRunner::show_help();
            break;
    }
} else {
    echo "⚠️  This script should be run from the command line\n";
}