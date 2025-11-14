# PEPPOL Provider Test Suite

A comprehensive unit testing framework for PEPPOL providers with built-in provider switching capabilities.

## 🚀 Features

- **Multi-Provider Support**: Test all providers (Ademico, Unit4, Recommand) with a single test suite
- **Provider Switching**: Seamlessly switch between providers during testing
- **Interface Compliance**: Verify all providers implement the required interface correctly
- **Legal Entity Management**: Complete CRUD testing for legal entities
- **Document Operations**: Test document sending, status tracking, and webhook handling
- **Mock Data**: Built-in mock data and test utilities
- **Comprehensive Reporting**: Detailed test results with pass/fail summaries

## 📁 Structure

```
tests/
├── README.md                     # This file
├── run_tests.php                 # Main test runner
└── providers/
    ├── BaseProviderTest.php      # Base test class with provider switching
    ├── LegalEntityTest.php       # Legal entity CRUD tests
    └── DocumentTest.php          # Document operation tests
```

## 🧪 Available Tests

### Legal Entity Tests
- ✅ Interface compliance verification
- ✅ Connection testing
- ✅ Get legal entities list
- ✅ Create legal entity
- ✅ Get specific legal entity
- ✅ Update legal entity
- ✅ Delete legal entity
- ✅ Unsupported provider handling

### Document Tests
- ✅ Interface compliance verification
- ✅ Connection testing
- ✅ Send document
- ✅ Get delivery status
- ✅ Webhook handling
- ✅ Provider-specific features

## 🏃‍♂️ Usage

### Command Line Interface

```bash
# Run all tests across all providers
php run_tests.php all

# Test specific provider only
php run_tests.php provider ademico
php run_tests.php provider unit4
php run_tests.php provider recommand

# Run specific test type across all providers
php run_tests.php type legal_entities
php run_tests.php type documents

# Show help
php run_tests.php help
```

### Programmatic Usage

```php
// Test specific provider
$test = new LegalEntityTest('ademico');
$test->run_all_tests();

// Switch providers during testing
$test->switch_provider('unit4');
$test->run_all_tests();

// Cross-provider testing
LegalEntityTest::run_cross_provider_tests();
DocumentTest::run_cross_provider_tests();
```

## 🔧 Configuration

### Provider Configurations

The test suite automatically configures itself based on each provider's capabilities:

```php
'ademico' => [
    'supports_legal_entities' => true,
    'auth_type' => 'oauth2',
    'required_fields' => ['client_id', 'client_secret']
],
'unit4' => [
    'supports_legal_entities' => false,
    'auth_type' => 'basic',
    'required_fields' => ['username', 'password']
],
'recommand' => [
    'supports_legal_entities' => false,
    'auth_type' => 'api_key',
    'required_fields' => ['api_key', 'company_id']
]
```

### Mock Data

Built-in mock data includes:

```php
$mock_data = [
    'legal_entity' => [
        'name' => 'Test Company Ltd',
        'identifier' => '12345678',
        'scheme_id' => '0088',
        'registration_number' => 'REG123456',
        'vat_number' => 'VAT123456789',
        'address' => [...],
        'contact' => [...],
        'peppol_identifier' => '0088:12345678'
    ],
    'invoice' => [...],
    'client' => [...],
    'ubl_content' => '<?xml version="1.0"?>...'
];
```

## 📊 Sample Output

```
🚀 PEPPOL Provider Test Suite
================================================================================
Start Time: 2024-01-15 10:30:00

🔄 Running Complete Test Suite Across All Providers
================================================================================

📊 LEGAL ENTITY TESTS
========================================

📋 Testing Provider: ademico
----------------------------------------
🧪 Starting Legal Entity Tests for Provider: ademico
============================================================
[ademico] ℹ️  Testing interface compliance...
[ademico] ✅ PASS: Provider implements required method: get_legal_entities
[ademico] ✅ PASS: Provider implements required method: create_legal_entity
...

🎉 All tests passed for provider: ademico

📋 Testing Provider: unit4
----------------------------------------
🧪 Starting Legal Entity Tests for Provider: unit4
============================================================
[unit4] ℹ️  Testing interface compliance...
[unit4] ✅ PASS: Provider implements required method: get_legal_entities
[unit4] ℹ️  Testing unsupported legal entity operations...
[unit4] ✅ PASS: get_legal_entities returns success=false for unsupported provider
...

📊 Test Summary
============================================================
✅ ademico: PASSED
✅ unit4: PASSED
✅ recommand: PASSED
```

## 🛡️ Provider-Specific Testing

### Ademico Provider
- OAuth2 token management testing
- Legal entity CRUD operations
- Multipart document uploads
- Webhook signature verification

### Unit4 Provider
- Basic authentication testing
- Standard document operations
- XML webhook handling
- Multipart form data

### Recommand Provider
- API key authentication testing
- JSON document format
- Simple webhook handling

## 🔄 Provider Switching

The test suite supports dynamic provider switching:

```php
$test = new LegalEntityTest('ademico');
$test->run_all_tests();

// Switch to different provider
$test->switch_provider('unit4');
$test->run_all_tests();

// Test with all providers
foreach (['ademico', 'unit4', 'recommand'] as $provider) {
    $test->switch_provider($provider);
    $test->test_connection();
}
```

## 🧩 Extending Tests

### Adding New Test Cases

```php
class CustomProviderTest extends BaseProviderTest
{
    public function test_custom_feature()
    {
        $this->log("Testing custom feature...");
        
        $result = $this->provider->custom_method();
        
        $this->assert(
            is_array($result),
            "custom_method returns array"
        );
    }
    
    public function test_interface_compliance()
    {
        // Required implementation
    }
    
    public function test_connection()
    {
        // Required implementation
    }
}
```

### Adding New Providers

1. Add provider configuration to `BaseProviderTest::$test_config`
2. Update `AVAILABLE_PROVIDERS` constant
3. Add provider-specific test methods if needed

## 🐛 Troubleshooting

### Common Issues

**Provider file not found:**
```
Exception: Provider file not found: /path/to/Provider.php
```
- Ensure provider files exist in the correct directory
- Check file naming conventions

**Interface not implemented:**
```
Exception: Provider class not found: Provider_name
```
- Verify provider class implements `Peppol_provider_interface`
- Check class naming matches file naming

**Connection failures:**
```
Connection test threw exception (expected in test environment)
```
- This is normal in test environments without real API access
- Tests focus on interface compliance and data structure validation

## 📈 Best Practices

1. **Run tests before deployment**: Always run the full test suite before deploying provider changes
2. **Test all providers**: Even if you only modified one provider, test all to ensure interface compatibility
3. **Mock real data**: Replace mock data with real test data when possible for more accurate testing
4. **Check return structures**: Verify all methods return the expected data structures
5. **Test error conditions**: Include tests for error scenarios and edge cases

## 🤝 Contributing

When adding new providers or features:

1. Extend the base test class
2. Add provider-specific configurations
3. Include comprehensive test coverage
4. Update this documentation
5. Run the full test suite to ensure compatibility