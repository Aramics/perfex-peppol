# PEPPOL Provider Development Guide

This guide explains how to create custom PEPPOL access point providers for the Perfex CRM PEPPOL module.

## Overview

PEPPOL providers are classes that extend `Abstract_peppol_provider` and implement the `Peppol_provider_interface`. They handle the actual transmission of documents to PEPPOL access points.

## Quick Start

### 1. Create Your Provider Class

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class My_peppol_provider extends Abstract_peppol_provider 
{
    public function get_provider_info() {
        return [
            'id' => 'my_provider',
            'name' => 'My PEPPOL Provider',
            'description' => 'Custom PEPPOL access point integration',
            'version' => '1.0.0',
            'icon' => 'fa-cloud',
            'test_connection' => true
        ];
    }
    
    public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info) {
        // Your send implementation
        $settings = $this->get_settings();
        
        // Example API call
        $response = $this->call_api($settings['endpoint'], [
            'document' => base64_encode($ubl_content),
            'type' => $document_type,
            'sender' => $sender_info,
            'receiver' => $receiver_info
        ]);
        
        if ($response['success']) {
            return [
                'success' => true, 
                'message' => 'Document sent successfully',
                'document_id' => $response['document_id']
            ];
        } else {
            return [
                'success' => false,
                'message' => $response['error']
            ];
        }
    }
    
    public function test_connection() {
        $settings = $this->get_settings();
        
        // Test API connection
        $response = $this->call_api($settings['endpoint'] . '/health');
        
        return [
            'success' => $response['success'],
            'message' => $response['success'] ? 'Connection successful' : $response['error']
        ];
    }
    
    public function get_setting_inputs() {
        return [
            'endpoint' => [
                'type' => 'url',
                'label' => 'API Endpoint',
                'placeholder' => 'https://api.example.com',
                'required' => true,
                'help' => 'Your PEPPOL provider API endpoint'
            ],
            'api_key' => [
                'type' => 'password',
                'label' => 'API Key',
                'required' => true,
                'help' => 'Your authentication key'
            ],
            'environment' => [
                'type' => 'select',
                'label' => 'Environment',
                'options' => [
                    'sandbox' => 'Sandbox (Testing)',
                    'production' => 'Production (Live)'
                ],
                'default' => 'sandbox'
            ],
            'timeout' => [
                'type' => 'number',
                'label' => 'Timeout (seconds)',
                'default' => 30,
                'attributes' => ['min' => 5, 'max' => 300]
            ],
            'debug_mode' => [
                'type' => 'checkbox',
                'label' => 'Enable Debug Logging'
            ],
            // Hidden fields (not shown in UI, always use default)
            'api_version' => [
                'type' => 'hidden',
                'default' => 'v2.1'
            ]
        ];
    }
    
    // Optional: Override webhook handling
    public function webhook($payload) {
        // Handle status updates from your provider
        $document_id = $payload['document_id'] ?? null;
        $status = $payload['status'] ?? null;
        
        if ($document_id && $status) {
            // Update document status in your system
            return ['success' => true, 'message' => 'Webhook processed'];
        }
        
        return ['success' => false, 'message' => 'Invalid webhook payload'];
    }
    
    // Optional: Override supported documents
    public function supported_documents() {
        return ['invoice', 'credit_note', 'purchase_order']; // Add custom types
    }
    
    // Helper method for API calls
    private function call_api($url, $data = null) {
        $settings = $this->get_settings();
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $settings['timeout'],
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $settings['api_key'],
                'Content-Type: application/json'
            ]
        ]);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true] + json_decode($response, true);
        } else {
            return ['success' => false, 'error' => 'HTTP ' . $http_code];
        }
    }
}
```

### 2. Register Your Provider

Add this to your module's hook file or a custom plugin:

```php
// Register the provider class
peppol_register_provider_class('My_peppol_provider');
```

### 3. File Location

Place your provider class file in one of these locations:
- `/modules/my_module/libraries/providers/My_peppol_provider.php`
- `/modules/peppol/libraries/providers/My_peppol_provider.php` 
- Or include it in your module's autoloading

## Interface Requirements

### Required Methods

#### `get_provider_info()`
Returns provider metadata:
```php
return [
    'id' => 'unique_provider_id',           // Required: Unique identifier
    'name' => 'Display Name',               // Required: Human readable name
    'description' => 'Provider description', // Optional: Brief description
    'version' => '1.0.0',                   // Optional: Provider version
    'icon' => 'fa-cloud',                   // Optional: FontAwesome icon
    'test_connection' => true               // Optional: Show test button
];
```

#### `send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info)`
Send documents via PEPPOL:
- `$document_type`: 'invoice', 'credit_note', etc.
- `$ubl_content`: UBL XML string
- `$document_data`: Document metadata (id, date, etc.)
- `$sender_info`: Company information
- `$receiver_info`: Client information

Returns: `['success' => bool, 'message' => string, 'document_id' => string]`

#### `test_connection()`
Test provider connectivity:
Returns: `['success' => bool, 'message' => string]`

#### `get_setting_inputs()`
Define configuration fields:
```php
return [
    'field_name' => [
        'type' => 'text|password|email|url|number|checkbox|select|hidden',
        'label' => 'Field Label',
        'placeholder' => 'Placeholder text',
        'help' => 'Help text',
        'required' => true|false,
        'default' => 'default_value',
        'options' => ['key' => 'label'], // For select fields
        'attributes' => ['min' => 1]     // HTML attributes
    ]
];
```

### Optional Methods (Have Defaults)

#### `webhook($payload)`
Handle webhook callbacks. Default returns "not supported".

#### `supported_documents()`
Return supported document types. Default: `['invoice', 'credit_note']`

#### `get_settings()` / `set_settings()`
Get/set configuration values. Default implementation handles database storage automatically.

#### `render_setting_inputs($inputs, $current_values)`
Render settings form. Default uses Perfex's `render_input()` and `render_select()`.

## Input Types

### Supported Input Types
- **text** - Basic text input
- **password** - Password field (masked)
- **email** - Email validation
- **url** - URL validation  
- **number** - Numeric input with min/max
- **checkbox** - Boolean checkbox
- **select** - Dropdown with options
- **hidden** - Not rendered, always uses default value

### Input Properties
- `type` - Input type (required)
- `label` - Display label
- `placeholder` - Placeholder text
- `help` - Help text shown below field
- `required` - Whether field is required
- `default` - Default value
- `options` - Array for select fields `['value' => 'label']`
- `attributes` - HTML attributes `['min' => 1, 'max' => 100]`

## Settings Management

### Automatic Storage
Settings are automatically stored in Perfex's options table with the pattern:
`peppol_{provider_id}_{field_name}`

### Getting Settings
```php
$settings = $this->get_settings();
$api_key = $settings['api_key'];
```

### Hidden Fields
Hidden fields are never rendered or stored in the database. They always return their default value and are useful for:
- Provider metadata
- API versions
- Feature flags
- Internal configuration

## Best Practices

### 1. Error Handling
Always wrap API calls in try-catch blocks and return meaningful error messages:

```php
public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info) {
    try {
        // API call logic
        $result = $this->callProviderAPI($ubl_content);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Document sent successfully',
                'document_id' => $result['id']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Provider error: ' . $result['error']
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ];
    }
}
```

### 2. Configuration Validation
Validate settings in your methods:

```php
public function test_connection() {
    $settings = $this->get_settings();
    
    if (empty($settings['api_key']) || empty($settings['endpoint'])) {
        return [
            'success' => false,
            'message' => 'API key and endpoint are required'
        ];
    }
    
    // Test connection logic
}
```

### 3. Logging
Use Perfex's logging for debugging:

```php
if ($settings['debug_mode']) {
    log_message('debug', 'PEPPOL Provider: Sending document ' . $document_data['id']);
}
```

### 4. Security
- Never store sensitive data in hidden fields
- Use password input type for API keys
- Validate all input parameters
- Use HTTPS for API calls in production

## Testing Your Provider

### 1. Register Your Provider
Add your registration code to a hook file.

### 2. Check Settings Page
Go to Admin → Settings → PEPPOL → Providers tab to see your provider listed.

### 3. Configure Settings
Fill in your provider's configuration fields.

### 4. Test Connection
Use the "Test Connection" button to verify your provider works.

### 5. Send Test Document
Try sending a test invoice or credit note.

## Troubleshooting

### Provider Not Showing
- Check class file location and autoloading
- Verify `peppol_register_provider_class()` is called
- Check for PHP syntax errors in your class

### Settings Not Saving
- Verify field names don't contain invalid characters
- Check `get_setting_inputs()` returns valid structure
- Ensure proper permissions for the settings page

### Connection Test Fails
- Check API credentials and endpoint URL
- Verify network connectivity from server
- Review error logs for detailed messages
- Test API endpoints with external tools (curl, Postman)

## Examples

See the `/examples/` directory for complete provider implementations:
- REST API Provider
- SOAP Provider  
- File-based Provider (for testing)

## Support

For questions about provider development:
1. Check this documentation
2. Review example providers
3. Check the module's issue tracker
4. Contact the module maintainer