# Separated Button Layout Implementation

This document explains the improved UI layout where test buttons are properly separated into their respective credential sections for better user experience.

## Overview

The PEPPOL settings UI now features a logical separation of testing functionality:
- **Production Section**: Contains production credentials and "Test Connection" button
- **Test Section**: Contains test credentials and "Test Credentials" button (sandbox only)

This provides a much clearer and more intuitive user experience.

## UI Layout Structure

### Production Credentials Section

**For all providers (Ademico, Unit4, Recommand):**

```
┌─ Production Credentials ─────────────────────────┐
│                                                  │
│  [Client ID Field]    [Client Secret Field]     │
│                                                  │
│  [Test Connection Button]                       │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Purpose:**
- Tests production credentials against production/live endpoints
- Always visible regardless of environment setting
- Uses current production credential values from form

### Test/Sandbox Credentials Section

**For all providers (shown only in sandbox environment):**

```
┌─ Test/Sandbox Credentials ───────────────────────┐
│                                                   │
│  [Test Client ID]     [Test Client Secret]       │
│                                                   │
│  [Test Credentials Button]                       │
│                                                   │
│  [⚠️ Warning about test credentials usage]       │
│                                                   │
└───────────────────────────────────────────────────┘
```

**Purpose:**
- Runs automated test suite with test credentials
- Only visible when "Sandbox (Testing)" environment is selected
- Uses test credential values from form
- Provides comprehensive validation of test setup

## Environment-Based Behavior

### Sandbox Environment Selected

**Production Section:**
- ✅ Always visible
- ✅ "Test Connection" button available
- ✅ Tests production credentials

**Test Section:**
- ✅ Slides down and becomes visible
- ✅ "Test Credentials" button available
- ✅ Runs automated test suite

### Live/Production Environment Selected

**Production Section:**
- ✅ Always visible
- ✅ "Test Connection" button available
- ✅ Tests production credentials

**Test Section:**
- ❌ Slides up and hidden
- ❌ "Test Credentials" button not accessible
- ❌ Clean production-focused interface

## Button Functionality

### "Test Connection" Button (Production Section)

**Location:** Below production credential fields
**Visibility:** Always visible
**Functionality:**
- Tests authentication with production credentials
- Uses existing `test_connection` endpoint
- Validates API connectivity
- Shows connection status results

**Example:**
```html
<button type="button" class="btn btn-info peppol-test-connection" data-provider="ademico">
    <i class="fa fa-plug"></i> Test Connection
</button>
```

### "Test Credentials" Button (Test Section)

**Location:** Below test credential fields
**Visibility:** Only in sandbox environment
**Functionality:**
- Runs complete automated test suite
- Uses new `test_credentials` endpoint
- Validates credentials with comprehensive tests
- Shows detailed test results

**Example:**
```html
<button type="button" class="btn btn-warning peppol-test-credentials" data-provider="ademico">
    <i class="fa fa-vial"></i> Test Credentials
</button>
```

## User Experience Benefits

### Clear Separation of Concerns

**Production Users:**
- Clear focus on production credentials
- No confusion with test-related options
- Immediate access to production connection testing

**Development/Testing Users:**
- Test credentials clearly separated from production
- Comprehensive testing tools readily available
- Visual distinction between different credential types

### Contextual Relevance

**Environment-Aware Interface:**
- Test options only appear when relevant (sandbox mode)
- Production options always available when needed
- Reduces cognitive load and interface clutter

**Logical Grouping:**
- Each button tests the credentials it's grouped with
- No ambiguity about which credentials are being tested
- Intuitive workflow for credential validation

## Technical Implementation

### Provider View Structure

Each provider now follows this consistent pattern:

```php
<!-- Production Credentials Section -->
<h5>Production Credentials</h5>
<div class="row">
    <!-- Production credential fields -->
</div>
<div class="row">
    <div class="col-md-12">
        <button class="btn btn-info peppol-test-connection" data-provider="[provider]">
            Test Connection
        </button>
    </div>
</div>

<!-- Test Credentials Section (environment-conditional) -->
<div class="peppol-test-credentials-section" style="[conditional-display]">
    <h5>Test/Sandbox Credentials</h5>
    <div class="row">
        <!-- Test credential fields -->
    </div>
    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-warning peppol-test-credentials" data-provider="[provider]">
                Test Credentials
            </button>
        </div>
    </div>
    <!-- Warning about test credentials -->
</div>
```

### JavaScript Environment Handling

The visibility logic now only needs to handle the test sections:

```javascript
function toggleTestCredentials() {
    var environment = $('select[name="settings[peppol_environment]"]').val();
    var testCredSections = $('.peppol-test-credentials-section');
    
    if (environment === 'sandbox') {
        testCredSections.slideDown(300);
    } else {
        testCredSections.slideUp(300);
    }
}
```

### Backend Endpoints

**Two separate endpoints for different purposes:**

1. **`/admin/peppol/test_connection`**
   - Tests basic connectivity
   - Uses production or specified credentials
   - Quick validation

2. **`/admin/peppol/test_credentials`**
   - Runs comprehensive test suite
   - Uses test credentials from form
   - Detailed validation

## Visual Design

### Color Coding

**Production "Test Connection" Button:**
- Color: Blue (`btn-info`)
- Icon: Plug (`fa-plug`)
- Conveys: Connection/connectivity testing

**Test "Test Credentials" Button:**
- Color: Orange/Yellow (`btn-warning`)
- Icon: Vial (`fa-vial`)
- Conveys: Testing/validation process

### Spacing and Layout

- Clear visual separation between sections
- Consistent button placement within each section
- Proper spacing with horizontal rules
- Mobile-responsive design

## Examples by Provider

### Ademico Provider

**Production Section:**
- OAuth2 Client ID field
- OAuth2 Client Secret field
- "Test Connection" button (blue)

**Test Section:** (sandbox only)
- Test OAuth2 Client ID field
- Test OAuth2 Client Secret field
- "Test Credentials" button (orange)
- Security warning

### Unit4 Provider

**Production Section:**
- Username field
- Password field
- "Test Connection" button (blue)

**Test Section:** (sandbox only)
- Test Username field
- Test Password field
- "Test Credentials" button (orange)
- Security warning

### Recommand Provider

**Production Section:**
- API Key field
- Company ID field
- "Test Connection" button (blue)

**Test Section:** (sandbox only)
- Test API Key field
- Test Company ID field
- "Test Credentials" button (orange)
- Security warning

## Migration Benefits

### Improved Usability

- **Intuitive Layout**: Buttons test the credentials they're grouped with
- **Reduced Confusion**: Clear separation of production vs test functionality
- **Better Workflow**: Natural progression from credential entry to testing

### Enhanced Security

- **Context Awareness**: Test credentials only accessible in appropriate environment
- **Visual Clarity**: Obvious distinction between production and test operations
- **Reduced Errors**: Less likely to mix up credential types

### Maintainability

- **Consistent Pattern**: All providers follow same layout structure
- **Modular Design**: Easy to update individual sections
- **Clear Separation**: Production and test functionality clearly delineated

This separated button layout provides a much more logical and user-friendly interface while maintaining all the powerful functionality of both connection testing and comprehensive credential validation.