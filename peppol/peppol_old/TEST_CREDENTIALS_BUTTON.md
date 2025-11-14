# Test Credentials Button Implementation

This document explains the complete implementation of the "Test Credentials" button functionality that allows administrators to validate their test credentials directly from the PEPPOL settings interface.

## Overview

The "Test Credentials" button provides a seamless way to:
- **Validate test credentials**: Run real tests against sandbox environments
- **Instant feedback**: Get immediate results without leaving the settings page
- **Automated testing**: Execute the full automated test suite with user-provided credentials
- **Safe testing**: Temporarily apply credentials without permanently changing settings

## User Experience

### Button Behavior

**Visibility:**
- ✅ Visible only when "Sandbox (Testing)" environment is selected
- ❌ Hidden when "Live (Production)" environment is selected
- 🔄 Automatically shown/hidden when environment changes

**Interaction Flow:**
1. User enters test credentials in the UI
2. User clicks "Test Credentials" button
3. Button shows loading state: "Testing..."
4. System runs automated test suite
5. Results displayed immediately below button

### Visual States

**Normal State:**
```html
<button class="btn btn-warning">
    <i class="fa fa-vial"></i> Test Credentials
</button>
```

**Loading State:**
```html
<button class="btn btn-warning" disabled>
    <i class="fa fa-spinner fa-spin"></i> Testing...
</button>
```

**Success Result:**
```html
<div class="alert alert-success">
    <i class="fa fa-check"></i> Test credentials validated successfully! All automated tests passed.
</div>
```

**Failure Result:**
```html
<div class="alert alert-danger">
    <i class="fa fa-times"></i> Test credentials validation failed. Check your credentials and try again.
</div>
```

## Technical Implementation

### Frontend (JavaScript)

#### Event Handler
```javascript
$('.peppol-test-credentials').on('click', function() {
    var provider = $(this).data('provider');
    var button = $(this);
    var resultDiv = $('.peppol-credentials-test');
    
    // Get credentials from form
    var testCredentials = getTestCredentialsFromForm(provider);
    
    if (!testCredentials) {
        // Show warning if no credentials
        return;
    }
    
    // Make AJAX request to backend
    $.ajax({
        url: admin_url + 'peppol/test_credentials',
        type: 'POST',
        data: {
            provider: provider,
            test_credentials: testCredentials
        }
        // Handle success/error responses
    });
});
```

#### Credential Extraction
The system automatically extracts credentials from the current form state:

**Ademico:**
- `peppol_ademico_oauth2_client_identifier_test`
- `peppol_ademico_oauth2_client_secret_test`

**Unit4:**
- `peppol_unit4_username_test`
- `peppol_unit4_password_test`

**Recommand:**
- `peppol_recommand_api_key_test`
- `peppol_recommand_company_id_test`

### Backend (PHP Controller)

#### Endpoint: `/admin/peppol/test_credentials`

**Method:** POST
**Permissions:** Requires `view` permission for `peppol`

#### Request Flow

1. **Validation**: Check provider and credentials are provided
2. **Backup**: Save current test settings
3. **Apply**: Temporarily set user-provided credentials
4. **Execute**: Run automated test suite
5. **Restore**: Restore original settings
6. **Response**: Return test results

#### Core Methods

```php
public function test_credentials()
{
    // Validate permissions and input
    // Execute test suite with provided credentials
    // Return JSON response
}

private function run_provider_test_suite($provider, $test_credentials)
{
    // Backup current settings
    // Apply test credentials
    // Run tests
    // Restore settings
    // Return results
}
```

#### Test Execution

The system executes the actual automated test suite:
```php
$command = "php " . escapeshellarg($test_script_path) . " type legal_entities " . escapeshellarg($provider) . " 2>&1";
$output = shell_exec($command);
```

#### Output Parsing

The system intelligently parses test output to determine success/failure:
- ✅ Success: "All tests passed", "tests passed"
- ❌ Failure: "FAILED", "Fatal error", "Exception"

## Security Considerations

### Credential Handling

**Temporary Application:**
- Credentials are applied only for the duration of the test
- Original settings are automatically restored
- No permanent changes to database

**Isolation:**
- Tests run in sandbox environment only
- No production systems affected
- Credentials validated against test endpoints only

**Error Handling:**
- Settings restored even if test fails
- Exception handling prevents credential leakage
- Graceful degradation on errors

### Access Control

**Permission Checks:**
- Requires `view` permission for `peppol` module
- Same security as existing test_connection endpoint
- Standard CSRF protection

**Input Validation:**
- Provider validation against whitelist
- Credential structure validation
- SQL injection prevention

## Test Integration

### Automated Test Suite

The button executes the same test framework used by developers:
- **Legal Entity Tests**: CRUD operations validation
- **Connection Tests**: Authentication verification
- **Interface Compliance**: Method signature validation

### Test Types Executed

1. **Interface Compliance**
   - Verifies all required methods exist
   - Validates provider implements interface

2. **Connection Testing**
   - Tests authentication with provided credentials
   - Validates API connectivity

3. **Legal Entity Operations**
   - Create legal entity
   - Retrieve legal entities
   - Update legal entity
   - Delete legal entity

### Result Interpretation

**Success Criteria:**
- All test methods pass
- No exceptions thrown
- Proper API response structures

**Failure Scenarios:**
- Authentication failures
- API connectivity issues
- Invalid response formats
- Exception during execution

## Provider-Specific Implementation

### Ademico Provider

**Credentials Required:**
- OAuth2 Client ID
- OAuth2 Client Secret

**Test Scope:**
- OAuth2 token generation
- Legal entity CRUD operations
- API connectivity validation

### Unit4 Provider

**Credentials Required:**
- Username
- Password

**Test Scope:**
- Basic authentication
- Connection testing
- Unsupported operation handling

### Recommand Provider

**Credentials Required:**
- API Key
- Company ID

**Test Scope:**
- API key authentication
- Connection testing
- Unsupported operation handling

## Error Messages and Troubleshooting

### Common Error Messages

**"Please configure test credentials first"**
- Solution: Fill in all required test credential fields

**"Test credentials validation failed"**
- Solution: Verify credentials are correct for sandbox environment

**"Test suite not found"**
- Solution: Ensure test framework is properly installed

**"Failed to execute test suite"**
- Solution: Check server permissions and PHP configuration

### Debugging

**Output Available:**
- Full test suite output included in response
- Can be logged for debugging purposes
- Detailed error messages from test framework

**Troubleshooting Steps:**
1. Verify credentials are for sandbox environment
2. Check network connectivity to provider APIs
3. Validate credential format and completeness
4. Review test suite output for specific errors

## Benefits

### For Administrators

**Immediate Validation:**
- No need to run command-line tests
- Instant feedback on credential validity
- Integration with existing settings workflow

**User-Friendly:**
- Simple one-click testing
- Clear success/failure messages
- No technical knowledge required

### For Developers

**Consistency:**
- Uses same test framework as development
- Identical validation logic
- Comprehensive test coverage

**Reliability:**
- Automated restoration of settings
- Error handling and graceful failures
- Safe testing environment

### For Operations

**Confidence:**
- Validate credentials before going live
- Ensure API connectivity
- Verify provider integration

**Documentation:**
- Test results provide validation evidence
- Clear audit trail of credential testing
- Integration with existing workflows

This implementation provides a complete, secure, and user-friendly way to validate PEPPOL test credentials directly from the admin interface while maintaining the robustness and reliability of the automated test framework.