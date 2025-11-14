# PEPPOL Test Credentials Integration

This document explains how the PEPPOL module now integrates test credentials through the admin UI with the automated test suite.

## Overview

The PEPPOL module now provides a comprehensive test credential management system that:
1. **UI Integration**: Allows admins to configure test credentials through the settings interface
2. **Test Suite Integration**: Automatically uses configured test credentials in the test framework
3. **Secure Storage**: Separates test credentials from production credentials
4. **Fallback Support**: Multiple credential source prioritization

## UI Changes

### Provider Settings Views

Each provider now has separate sections for production and test credentials:

**Ademico Provider:**
- Production: `peppol_ademico_oauth2_client_identifier`, `peppol_ademico_oauth2_client_secret`
- Test: `peppol_ademico_oauth2_client_identifier_test`, `peppol_ademico_oauth2_client_secret_test`

**Unit4 Provider:**
- Production: `peppol_unit4_username`, `peppol_unit4_password`
- Test: `peppol_unit4_username_test`, `peppol_unit4_password_test`

**Recommand Provider:**
- Production: `peppol_recommand_api_key`, `peppol_recommand_company_id`
- Test: `peppol_recommand_api_key_test`, `peppol_recommand_company_id_test`

### New UI Elements

1. **Section Headers**: Clear separation between production and test credentials
2. **Help Text**: Extensive tooltips explaining the purpose of test credentials
3. **Warning Alerts**: Reminders about security and proper use of test credentials
4. **Test Button**: New "Test Credentials" button alongside connection test

## Test Framework Integration

### Credential Loading Priority

The test framework now loads credentials in the following priority order:

1. **PEPPOL Settings** (Preferred)
   - Uses test credentials configured in the admin UI
   - Example: `peppol_ademico_oauth2_client_identifier_test`

2. **External File** (Fallback)
   - Uses `test_credentials.php` if PEPPOL settings are empty
   - Maintains backward compatibility

3. **Built-in Defaults** (Final fallback)
   - Uses hardcoded test credentials in BaseProviderTest
   - Ensures tests can always run

### Implementation Details

```php
// BaseProviderTest.php - New credential loading logic
private function load_test_credentials_from_settings()
{
    $test_credentials = [];
    
    switch ($this->provider_name) {
        case 'ademico':
            $client_id = $this->get_test_setting('peppol_ademico_oauth2_client_identifier_test');
            $client_secret = $this->get_test_setting('peppol_ademico_oauth2_client_secret_test');
            
            if (!empty($client_id) && !empty($client_secret)) {
                $test_credentials = [
                    'peppol_ademico_oauth2_client_identifier' => $client_id,
                    'peppol_ademico_oauth2_client_secret' => $client_secret
                ];
            }
            break;
        // ... similar for other providers
    }
    
    return $test_credentials;
}
```

## Language Strings

### New Labels Added

- `peppol_production_credentials` - "Production Credentials"
- `peppol_test_credentials` - "Test/Sandbox Credentials"
- `peppol_endpoint_configuration` - "Endpoint Configuration"
- `peppol_test_credentials_note` - "Test Credentials for Development"
- `peppol_test_credentials_help` - Comprehensive help text
- Provider-specific test field labels and help text

### Help Text Examples

```php
$lang['peppol_ademico_client_id_test_help'] = 'OAuth2 client identifier for Ademico sandbox environment. Used for testing and development.';
$lang['peppol_test_credentials_help'] = 'Test credentials are used for development, testing, and the test suite. They should only contain sandbox/test environment credentials, never production credentials.';
```

## Usage Workflow

### For Administrators

1. **Navigate to PEPPOL Settings**
   - Go to Settings → PEPPOL → Provider Settings

2. **Configure Production Credentials**
   - Enter live/production credentials in the "Production Credentials" section

3. **Configure Test Credentials**
   - Enter sandbox/test credentials in the "Test/Sandbox Credentials" section
   - Use only sandbox environment credentials

4. **Test Configuration**
   - Click "Test Connection" to test production credentials
   - Click "Test Credentials" to run automated tests with test credentials

### For Developers/Testing

1. **Run Test Suite**
   ```bash
   # Tests will automatically use configured test credentials
   php run_tests.php type legal_entities ademico
   ```

2. **Verify Credential Loading**
   - Check console output for credential source:
   - "Loading test credentials from PEPPOL settings" (preferred)
   - "Loading credentials from test_credentials.php (fallback)"
   - "Using built-in test credentials"

## Security Considerations

### Best Practices

1. **Separation of Concerns**
   - Production and test credentials are completely separate
   - Test credentials cannot access production systems

2. **Secure Storage**
   - Test credentials stored in database with same security as other settings
   - Passwords fields properly masked in UI

3. **Environment Validation**
   - Test framework always forces sandbox environment
   - Cannot accidentally use test credentials in production

### Warnings and Alerts

The UI includes prominent warnings about:
- Only using sandbox/test credentials in test fields
- Never putting production credentials in test fields
- Keeping test credentials confidential despite being for testing

## Testing Different Scenarios

### With PEPPOL Settings Configured
```bash
# Will use credentials from admin UI
php run_tests.php type legal_entities ademico
# Output: "Loading test credentials from PEPPOL settings"
```

### With External File Only
```bash
# Remove test credentials from UI, use external file
php run_tests.php type legal_entities ademico
# Output: "Loading credentials from test_credentials.php (fallback)"
```

### With No External Configuration
```bash
# No UI settings, no external file
php run_tests.php type legal_entities ademico
# Output: "Using built-in test credentials"
```

## Future Enhancements

### Potential Improvements

1. **Test Results Dashboard**
   - Show test results directly in admin UI
   - Historical test run tracking

2. **Credential Validation**
   - Real-time validation of test credentials
   - API connectivity checks

3. **Automated Testing**
   - Scheduled test runs
   - Email notifications for test failures

4. **Multi-Environment Support**
   - Support for multiple test environments (dev, staging, etc.)
   - Environment-specific credential sets

## Files Modified

### Views
- `/views/provider-settings/ademico.php`
- `/views/provider-settings/unit4.php`
- `/views/provider-settings/recommand.php`
- `/views/settings.php`

### Language
- `/language/english/peppol_lang.php`

### Test Framework
- `/tests/providers/BaseProviderTest.php`

### Documentation
- `/tests/README_TEST.md`
- `/tests/test_credentials.example.php`

This implementation provides a complete, secure, and user-friendly way to manage test credentials while maintaining the flexibility and power of the automated test suite.