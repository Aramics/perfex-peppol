# Environment-Based Test Credential UI

This document explains the enhanced PEPPOL settings UI that dynamically shows/hides test credential fields based on the selected environment.

## Overview

The PEPPOL module now provides an intelligent UI that:
- **Shows test credentials only in sandbox environment**: Test credential fields appear only when "Sandbox (Testing)" is selected
- **Hides test credentials in production**: When "Live (Production)" is selected, test credential fields are hidden
- **Dynamic updates**: Environment changes trigger immediate UI updates without page reload
- **Consistent behavior**: All providers (Ademico, Unit4, Recommand) follow the same pattern

## UI Behavior

### Environment Selection Impact

**When "Sandbox (Testing)" is selected:**
- ✅ Test credential sections become visible
- ✅ "Test Credentials" button appears
- ✅ Smooth slide-down animation
- ✅ All test credential fields are accessible

**When "Live (Production)" is selected:**
- ❌ Test credential sections are hidden
- ❌ "Test Credentials" button disappears  
- ❌ Smooth slide-up animation
- ❌ Clean production-focused interface

### Initial State

The UI automatically determines the initial state based on the current `peppol_environment` setting:
- If environment is "sandbox" → Test credentials visible on page load
- If environment is "live" → Test credentials hidden on page load

## Technical Implementation

### JavaScript Logic

```javascript
function toggleTestCredentials() {
    var environment = $('select[name="settings[peppol_environment]"]').val();
    var testCredSections = $('.peppol-test-credentials-section');
    var testCredButtons = $('.peppol-test-credentials');
    
    if (environment === 'sandbox') {
        testCredSections.slideDown(300);
        testCredButtons.show();
    } else {
        testCredSections.slideUp(300);
        testCredButtons.hide();
    }
}
```

### Event Handling

1. **Page Load**: Checks current environment and sets initial visibility
2. **Environment Change**: Triggers when user changes environment dropdown
3. **Provider Change**: Re-evaluates visibility when switching providers

### CSS Classes

- `.peppol-test-credentials-section`: Wraps all test credential fields
- `.peppol-test-credentials`: The "Test Credentials" button

### Server-Side Integration

Each provider view includes server-side logic for initial state:
```php
<div class="peppol-test-credentials-section" 
     style="<?php echo get_option('peppol_environment', 'sandbox') !== 'sandbox' ? 'display: none;' : ''; ?>">
```

## Provider Implementation

### All Providers Follow Same Pattern

**Ademico Provider:**
```php
<!-- Test credentials section - shown only in sandbox environment -->
<div class="peppol-test-credentials-section" style="<?php echo get_option('peppol_environment', 'sandbox') !== 'sandbox' ? 'display: none;' : ''; ?>">
    <h5><?php echo _l('peppol_test_credentials'); ?></h5>
    <!-- Test credential fields -->
    <div class="alert alert-warning">
        <!-- Security warning -->
    </div>
</div>
```

**Unit4 & Recommand**: Follow identical pattern with provider-specific fields

## User Experience Benefits

### For Production Users
- **Clean Interface**: No unnecessary test fields cluttering the production settings
- **Focus**: Only production-relevant configuration is visible
- **Reduced Confusion**: No chance of accidentally entering test credentials in wrong fields

### For Development/Testing Users  
- **Easy Access**: Test credentials appear automatically in sandbox mode
- **Clear Separation**: Visual distinction between production and test settings
- **Guidance**: Warning messages explain proper use of test credentials

### For Mixed Usage
- **Instant Switching**: Change environment to instantly reveal/hide test fields
- **No Page Reload**: Smooth transitions without losing unsaved changes
- **Context Awareness**: UI adapts to current workflow needs

## Animation Details

### Smooth Transitions
- **Slide Down**: 300ms smooth animation when showing test credentials
- **Slide Up**: 300ms smooth animation when hiding test credentials
- **Button Show/Hide**: Instant visibility change for buttons
- **No Jarring**: Smooth user experience without sudden layout shifts

### Responsive Design
- Animations work across all screen sizes
- Mobile-friendly touch interactions
- Accessibility-compliant transitions

## Security Considerations

### UI-Level Protection
- Test credentials only visible in appropriate environment
- Reduces risk of accidental production credential mixing
- Clear visual indicators for environment context

### Validation Benefits
- Users less likely to enter wrong credentials in wrong fields
- Environment-context helps users understand credential purpose
- Warning messages reinforce security best practices

## Testing the Feature

### Manual Testing Steps

1. **Navigate to PEPPOL Settings**
   - Go to Settings → PEPPOL → Provider Settings

2. **Test Environment Switching**
   - Change Environment from "Sandbox" to "Live"
   - Observe test credentials slide up and disappear
   - Change back to "Sandbox"
   - Observe test credentials slide down and appear

3. **Test Provider Switching**
   - Switch between different providers (Ademico, Unit4, Recommand)
   - Verify test credentials visibility remains consistent
   - Check that all providers follow same pattern

4. **Test Initial State**
   - Set environment to "Live" and save
   - Refresh page
   - Verify test credentials start hidden
   - Change to "Sandbox"
   - Verify test credentials appear

### Automated Testing

The test framework automatically adapts to this new UI:
- Test credential loading priority remains unchanged
- Settings-based credentials work regardless of UI state
- Test suite runs consistently in sandbox environment

## Browser Compatibility

### Supported Browsers
- ✅ Chrome 60+
- ✅ Firefox 55+  
- ✅ Safari 12+
- ✅ Edge 79+

### JavaScript Requirements
- Requires jQuery (already included in Perfex CRM)
- Uses CSS3 animations (graceful degradation on older browsers)
- No external dependencies

## Future Enhancements

### Potential Improvements

1. **Environment Indicators**
   - Color-coded environment badges
   - Visual environment status in header

2. **Field Validation**
   - Real-time validation of test vs production URLs
   - Environment-specific format checking

3. **Auto-Save Awareness**
   - Prevent hiding sections with unsaved changes
   - Smart save prompts on environment switch

4. **Advanced Animations**
   - Fade effects for smoother transitions
   - Progress indicators for long operations

This environment-based UI enhancement significantly improves the user experience by providing contextually appropriate interface elements while maintaining all existing functionality and security measures.