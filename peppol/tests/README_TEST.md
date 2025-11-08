# PEPPOL Provider Test Suite

This directory contains comprehensive tests for all PEPPOL providers with support for provider switching and real credential testing.

## Setup

### 1. Configure Test Credentials

Copy the example credentials file and add your test credentials:

```bash
cp test_credentials.example.php test_credentials.php
```

Edit `test_credentials.php` with your actual test credentials:

```php
<?php
return [
    'ademico' => [
        'peppol_ademico_oauth2_client_identifier' => 'your_actual_test_client_id',
        'peppol_ademico_oauth2_client_secret' => 'your_actual_test_secret'
    ],
    'unit4' => [
        'peppol_unit4_username' => 'your_actual_test_username',
        'peppol_unit4_password' => 'your_actual_test_password'
    ],
    'recommand' => [
        'peppol_recommand_api_key' => 'your_actual_test_api_key',
        'peppol_recommand_company_id' => 'your_actual_test_company_id'
    ]
];
```

**⚠️ Important:** The `test_credentials.php` file is automatically ignored by git to prevent accidental credential exposure.

### 2. Provider Test Credentials

#### Ademico
- Get sandbox OAuth2 credentials from Ademico developer portal
- Environment: `sandbox`
- Base URL: `https://test-peppol-api.ademico-software.com`

#### Unit4
- Get sandbox basic auth credentials from Unit4
- Environment: `sandbox`
- Contact Unit4 support for test credentials

#### Recommand
- Get sandbox API key from Recommand portal
- Environment: `sandbox`
- Contact Recommand support for test credentials

## Usage

### Run All Tests
```bash
php run_tests.php all
```

### Test Specific Provider
```bash
php run_tests.php provider ademico
php run_tests.php provider unit4
php run_tests.php provider recommand
```

### Test Specific Type
```bash
# All providers
php run_tests.php type legal_entities
php run_tests.php type documents

# Specific provider
php run_tests.php type legal_entities ademico
php run_tests.php type documents unit4
```

### Help
```bash
php run_tests.php help
```

## Test Features

### Credential Management
- **Real Credentials**: Tests use actual sandbox credentials instead of mocking authentication failures
- **Secure Storage**: Credentials stored in separate file that's not committed to version control
- **Fallback Support**: Falls back to default credentials if external file not found

### Provider Support
- **Ademico**: Full legal entity and document management support
- **Unit4**: Document management only (legal entities return "not supported")
- **Recommand**: Document management only (legal entities return "not supported")

### Test Types

#### Legal Entity Tests
- Interface compliance
- Connection testing
- CRUD operations (Create, Read, Update, Delete)
- Provider-specific feature detection

#### Document Tests
- Document sending
- Delivery status checking
- Webhook handling
- Provider-specific features

### Validation
- **API Response Validation**: All API responses validated for proper structure
- **Success/Failure Detection**: Tests fail when API operations are unsuccessful
- **Error Logging**: Detailed error messages for debugging
- **Provider Compatibility**: Automatic adaptation based on provider capabilities

## Test Environment

All tests run in **sandbox mode** to prevent affecting production data:
- Ademico: Uses test sandbox environment
- Unit4: Uses test sandbox environment  
- Recommand: Uses test sandbox environment

## Troubleshooting

### Authentication Errors
If you get authentication errors:
1. Verify your credentials in `test_credentials.php`
2. Ensure you're using sandbox/test credentials, not production
3. Check that credentials are valid and not expired
4. Contact the provider support for credential verification

### Provider-Specific Issues

#### Ademico
- OAuth2 token issues: Check client_id and client_secret
- Endpoint errors: Verify sandbox base URL

#### Unit4
- Basic auth issues: Check username and password
- Connection timeouts: Verify Unit4 sandbox availability

#### Recommand  
- API key issues: Check API key and company ID
- Permission errors: Verify API key has required permissions

### Missing Dependencies
If you get "file not found" errors:
```bash
# Ensure you're in the correct directory
cd modules/peppol/tests
php run_tests.php help
```

## Security Notes

- **Never commit** `test_credentials.php` to version control
- Use only **sandbox/test credentials**, never production credentials
- Rotate test credentials periodically
- Restrict test credential permissions to minimum required