# PEPPOL Provider Development Guide

This comprehensive guide explains how to create custom PEPPOL access point providers for the Perfex CRM PEPPOL module.

## Overview

PEPPOL providers are classes that extend `Abstract_peppol_provider` and implement the `Peppol_provider_interface`. They handle the actual transmission of documents to PEPPOL access points and manage the communication between your Perfex CRM system and the PEPPOL network.

## Architecture

The provider framework follows these design principles:

- **Interface-based**: All providers implement a common interface ensuring consistency
- **Extensible**: Easy to add new providers without modifying core code
- **Configurable**: Dynamic settings system with validation and UI generation
- **Testable**: Built-in connection testing and validation methods
- **Secure**: Proper handling of credentials and sensitive information

## Quick Start

### 1. Create Your Provider Class

Create a new PHP class that extends the abstract provider:

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
            'test_connection' => true,
            'supports_webhooks' => true
        ];
    }
    
    public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info) {
        $settings = $this->get_settings();
        
        try {
            // Your send implementation
            $response = $this->call_api($settings['endpoint'] . '/send', [
                'document' => base64_encode($ubl_content),
                'document_type' => $document_type,
                'sender' => $sender_info,
                'receiver' => $receiver_info,
                'metadata' => $document_data
            ]);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Document sent successfully',
                    'document_id' => $response['document_id'],
                    'tracking_id' => $response['tracking_id'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Provider error: ' . $response['error'],
                    'error_code' => $response['error_code'] ?? null
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function test_connection() {
        $settings = $this->get_settings();
        
        if (empty($settings['api_key']) || empty($settings['endpoint'])) {
            return [
                'success' => false,
                'message' => 'API key and endpoint are required'
            ];
        }
        
        try {
            $response = $this->call_api($settings['endpoint'] . '/health');
            
            return [
                'success' => $response['success'],
                'message' => $response['success'] 
                    ? 'Connection successful - Provider is ready'
                    : 'Connection failed: ' . $response['error']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Network error: ' . $e->getMessage()
            ];
        }
    }
    
    public function get_setting_inputs() {
        return [
            'endpoint' => [
                'type' => 'url',
                'label' => 'API Endpoint',
                'placeholder' => 'https://api.example.com',
                'required' => true,
                'help' => 'Your PEPPOL provider API endpoint URL',
                'validation' => 'url'
            ],
            'api_key' => [
                'type' => 'password',
                'label' => 'API Key',
                'required' => true,
                'help' => 'Your authentication key from the provider',
                'placeholder' => 'Enter your API key'
            ],
            'company_id' => [
                'type' => 'text',
                'label' => 'Company ID',
                'required' => true,
                'help' => 'Your company identifier with this provider'
            ],
            'environment' => [
                'type' => 'select',
                'label' => 'Environment',
                'options' => [
                    'sandbox' => 'Sandbox (Testing)',
                    'production' => 'Production (Live)'
                ],
                'default' => 'sandbox',
                'help' => 'Select testing or production environment'
            ],
            'timeout' => [
                'type' => 'number',
                'label' => 'Request Timeout (seconds)',
                'default' => 30,
                'attributes' => ['min' => 5, 'max' => 300],
                'help' => 'Maximum time to wait for API responses'
            ],
            'debug_mode' => [
                'type' => 'checkbox',
                'label' => 'Enable Debug Logging',
                'help' => 'Log detailed information for troubleshooting'
            ],
            'webhook_secret' => [
                'type' => 'password',
                'label' => 'Webhook Secret',
                'help' => 'Secret for validating incoming webhooks (optional)'
            ],
            // Hidden configuration values
            'api_version' => [
                'type' => 'hidden',
                'default' => 'v2.1'
            ],
            'max_retries' => [
                'type' => 'hidden',
                'default' => 3
            ]
        ];
    }
    
    // Optional: Handle webhook notifications
    public function webhook($payload) {
        $settings = $this->get_settings();
        
        // Validate webhook signature if secret is configured
        if (!empty($settings['webhook_secret'])) {
            if (!$this->validate_webhook_signature($payload, $settings['webhook_secret'])) {
                return ['success' => false, 'message' => 'Invalid webhook signature'];
            }
        }
        
        $document_id = $payload['document_id'] ?? null;
        $status = $payload['status'] ?? null;
        $message = $payload['message'] ?? '';
        
        if ($document_id && $status) {
            // Update document status in the system
            $this->update_document_status($document_id, $status, $message);
            
            return [
                'success' => true,
                'message' => 'Status updated successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid webhook payload - missing required fields'
        ];
    }
    
    // Optional: Override supported document types
    public function supported_documents() {
        return [
            'invoice' => 'Commercial Invoice',
            'credit_note' => 'Credit Note',
            'purchase_order' => 'Purchase Order' // If supported by your provider
        ];
    }
    
    // Helper method for API calls
    private function call_api($url, $data = null) {
        $settings = $this->get_settings();
        
        $headers = [
            'Authorization: Bearer ' . $settings['api_key'],
            'Content-Type: application/json',
            'User-Agent: Perfex-PEPPOL-Module/1.0'
        ];
        
        if ($settings['debug_mode']) {
            log_message('debug', 'PEPPOL API Call: ' . $url);
            if ($data) {
                log_message('debug', 'PEPPOL API Data: ' . json_encode($data));
            }
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $settings['timeout'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
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
            if ($settings['debug_mode']) {
                log_message('error', 'PEPPOL API cURL Error: ' . $error);
            }
            return ['success' => false, 'error' => $error];
        }
        
        $decoded_response = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            if ($settings['debug_mode']) {
                log_message('debug', 'PEPPOL API Success: HTTP ' . $http_code);
            }
            return ['success' => true] + ($decoded_response ?: []);
        } else {
            $error_msg = $decoded_response['message'] ?? 'HTTP ' . $http_code;
            if ($settings['debug_mode']) {
                log_message('error', 'PEPPOL API Error: ' . $error_msg);
            }
            return [
                'success' => false,
                'error' => $error_msg,
                'http_code' => $http_code
            ];
        }
    }
    
    // Helper method for webhook signature validation
    private function validate_webhook_signature($payload, $secret) {
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        $computed_signature = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals('sha256=' . $computed_signature, $signature);
    }
}
```

### 2. Register Your Provider

Add this to your module's hook file or initialization code:

```php
// Register the provider class
peppol_register_provider_class('My_peppol_provider');
```

### 3. File Location

Place your provider class file in one of these locations:

- `/modules/my_module/libraries/providers/My_peppol_provider.php`
- `/modules/peppol/libraries/providers/My_peppol_provider.php`
- Or include it in your module's autoloading system

## Interface Requirements

### Required Methods

All providers must implement these essential methods:

#### `get_provider_info()`

Returns provider metadata that describes your provider to the system:

```php
public function get_provider_info() {
    return [
        'id' => 'unique_provider_id',           // Required: Unique identifier (no spaces)
        'name' => 'Display Name',               // Required: Human readable name
        'description' => 'Brief description',   // Optional: Provider description
        'version' => '1.0.0',                   // Optional: Provider version
        'icon' => 'fa-cloud',                   // Optional: FontAwesome icon class
        'test_connection' => true,              // Optional: Show connection test button
        'supports_webhooks' => true,           // Optional: Webhook support indicator
        'website' => 'https://provider.com',   // Optional: Provider website
        'documentation' => 'https://docs...'   // Optional: Provider documentation URL
    ];
}
```

#### `send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info)`

The core method for sending documents via PEPPOL:

**Parameters:**
- `$document_type`: Document type ('invoice', 'credit_note', etc.)
- `$ubl_content`: UBL XML string (fully formatted)
- `$document_data`: Document metadata (id, number, date, amount, etc.)
- `$sender_info`: Company information (PEPPOL ID, name, address, etc.)
- `$receiver_info`: Customer information (PEPPOL ID, name, address, etc.)

**Return Format:**
```php
[
    'success' => true|false,
    'message' => 'Status message',
    'document_id' => 'provider_document_id',  // Required on success
    'tracking_id' => 'tracking_reference',    // Optional
    'error_code' => 'ERROR_CODE'              // Optional on failure
]
```

#### `test_connection()`

Tests connectivity and authentication with your provider:

**Return Format:**
```php
[
    'success' => true|false,
    'message' => 'Connection status message'
]
```

#### `get_setting_inputs()`

Defines the configuration form fields for your provider:

```php
public function get_setting_inputs() {
    return [
        'field_name' => [
            'type' => 'text|password|email|url|number|checkbox|select|hidden',
            'label' => 'Field Label',
            'placeholder' => 'Placeholder text',
            'help' => 'Help text shown below field',
            'required' => true|false,
            'default' => 'default_value',
            'options' => ['key' => 'label'],    // For select fields
            'attributes' => ['min' => 1],       // HTML attributes
            'validation' => 'email|url|numeric' // Built-in validation
        ]
    ];
}
```

### Optional Methods

These methods have default implementations but can be overridden:

#### `webhook($payload)`

Handle incoming webhook notifications from your provider:

```php
public function webhook($payload) {
    // Process status updates, delivery confirmations, etc.
    return ['success' => true, 'message' => 'Webhook processed'];
}
```

#### `supported_documents()`

Return array of supported document types:

```php
public function supported_documents() {
    return [
        'invoice' => 'Commercial Invoice',
        'credit_note' => 'Credit Note',
        'purchase_order' => 'Purchase Order'
    ];
}
```

#### `get_settings()` / `set_settings()`

Settings management (default implementation handles database storage):

```php
public function get_settings() {
    // Returns array of current settings
}

public function set_settings($settings) {
    // Saves settings to database
}
```

## Input Types and Validation

### Supported Input Types

#### Basic Input Types
- **text**: Basic text input field
- **password**: Password field (masked input)
- **email**: Email input with validation
- **url**: URL input with validation
- **number**: Numeric input with optional min/max
- **checkbox**: Boolean checkbox

#### Advanced Input Types
- **select**: Dropdown with predefined options
- **hidden**: Not rendered in UI, always uses default value

### Input Field Properties

```php
[
    'type' => 'text',                    // Required: Input type
    'label' => 'API Endpoint',           // Display label
    'placeholder' => 'Enter URL...',     // Placeholder text
    'help' => 'Your API endpoint URL',   // Help text below field
    'required' => true,                  // Whether field is required
    'default' => 'default_value',        // Default value
    'validation' => 'url',               // Built-in validation
    'attributes' => [                    // HTML attributes
        'min' => 1,
        'max' => 100,
        'class' => 'custom-class'
    ],
    'options' => [                       // For select fields
        'value1' => 'Display Label 1',
        'value2' => 'Display Label 2'
    ]
]
```

### Built-in Validation

The system provides several validation options:

- **email**: Valid email address format
- **url**: Valid URL format
- **numeric**: Numeric values only
- **required**: Field must not be empty

## Settings Management

### Automatic Storage

Settings are automatically stored in the database using the pattern:
```
peppol_{provider_id}_{field_name}
```

### Getting and Setting Values

```php
// Get all settings for this provider
$settings = $this->get_settings();

// Get specific setting with default fallback
$api_key = $settings['api_key'] ?? 'default_value';

// Set multiple settings
$this->set_settings([
    'api_key' => 'new_key',
    'endpoint' => 'https://new.endpoint.com'
]);
```

### Hidden Fields

Hidden fields are never rendered in the UI or stored in the database. They always return their default value and are useful for:

- Provider metadata (API versions, feature flags)
- Internal configuration values
- Constants that shouldn't be user-configurable

## Best Practices

### Error Handling

Always implement comprehensive error handling:

```php
public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info) {
    try {
        // Validate input parameters
        if (empty($ubl_content)) {
            return [
                'success' => false,
                'message' => 'UBL content is required'
            ];
        }
        
        // Check provider configuration
        $settings = $this->get_settings();
        if (empty($settings['api_key'])) {
            return [
                'success' => false,
                'message' => 'Provider not configured - API key missing'
            ];
        }
        
        // Attempt API call
        $result = $this->call_provider_api($ubl_content, $document_data);
        
        // Process response
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Document sent successfully',
                'document_id' => $result['id']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Provider error: ' . $result['error'],
                'error_code' => $result['code'] ?? null
            ];
        }
        
    } catch (Exception $e) {
        // Log the error for debugging
        log_message('error', 'PEPPOL Provider Error: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ];
    }
}
```

### Security Considerations

1. **Secure Credential Storage**: Use password input types for sensitive data
2. **Validate Input**: Always validate and sanitize input parameters
3. **HTTPS Only**: Ensure API communications use HTTPS
4. **Webhook Validation**: Verify webhook signatures when supported
5. **Error Disclosure**: Don't expose sensitive information in error messages

### Logging and Debugging

Implement comprehensive logging:

```php
private function debug_log($message, $data = null) {
    $settings = $this->get_settings();
    
    if ($settings['debug_mode']) {
        $log_message = 'PEPPOL [' . $this->get_provider_info()['name'] . ']: ' . $message;
        
        if ($data) {
            $log_message .= ' Data: ' . json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        
        log_message('debug', $log_message);
    }
}
```

### API Communication

Follow these patterns for reliable API communication:

```php
private function call_api($endpoint, $data = null, $method = 'POST') {
    $settings = $this->get_settings();
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $settings['timeout'] ?? 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_USERAGENT => 'Perfex-PEPPOL-Module/' . $this->get_provider_info()['version'],
        CURLOPT_HTTPHEADER => $this->get_api_headers()
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    $decoded = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true] + ($decoded ?: []);
    } else {
        return [
            'success' => false,
            'error' => $decoded['message'] ?? 'HTTP ' . $http_code,
            'http_code' => $http_code
        ];
    }
}
```

## Testing Your Provider

### Development Workflow

1. **Create Provider Class**: Implement all required methods
2. **Register Provider**: Add registration code to hooks
3. **Test Registration**: Verify provider appears in settings
4. **Configure Settings**: Fill in provider configuration
5. **Test Connection**: Use built-in connection test
6. **Send Test Document**: Try sending to sandbox environment
7. **Monitor Logs**: Check for errors and debug information

### Unit Testing

Consider creating unit tests for your provider:

```php
class My_peppol_provider_test extends CI_Controller 
{
    private $provider;
    
    public function setUp() {
        $this->provider = new My_peppol_provider();
        
        // Set test configuration
        $this->provider->set_settings([
            'endpoint' => 'https://sandbox-api.provider.com',
            'api_key' => 'test_key_123',
            'environment' => 'sandbox'
        ]);
    }
    
    public function test_connection() {
        $result = $this->provider->test_connection();
        $this->assertTrue($result['success']);
    }
    
    public function test_send_document() {
        // Test document sending with mock UBL
        $ubl = $this->get_test_ubl();
        $result = $this->provider->send('invoice', $ubl, [], [], []);
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['document_id']);
    }
}
```

## Troubleshooting

### Common Issues

#### Provider Not Showing in Settings

**Symptoms**: Your provider doesn't appear in the provider selection dropdown

**Solutions**:
1. Check class file location and naming
2. Verify `peppol_register_provider_class()` is called
3. Check for PHP syntax errors in your class
4. Ensure class extends `Abstract_peppol_provider`
5. Verify the class file is being loaded

#### Settings Form Not Rendering

**Symptoms**: Provider appears but settings form is empty or malformed

**Solutions**:
1. Check `get_setting_inputs()` returns valid array structure
2. Verify all required field properties are present
3. Check for invalid input types or malformed options arrays
4. Review field validation settings

#### Connection Test Failures

**Symptoms**: Connection test always fails even with correct credentials

**Solutions**:
1. Verify API endpoint URL is correct and accessible
2. Check authentication credentials and format
3. Test with external tools (curl, Postman) first
4. Review provider documentation for authentication requirements
5. Check server firewall and network connectivity
6. Enable debug logging to see detailed error messages

#### Document Send Failures

**Symptoms**: Documents fail to send with various error messages

**Solutions**:
1. Verify customer PEPPOL information is correct and valid
2. Check UBL content format and compliance
3. Confirm provider account status and credits
4. Review provider-specific requirements and limitations
5. Test with simpler documents first
6. Check for character encoding issues in UBL content

### Debug Information

Enable comprehensive logging in your provider:

```php
private function log_api_call($endpoint, $data, $response, $success) {
    if ($this->get_settings()['debug_mode']) {
        $log_data = [
            'endpoint' => $endpoint,
            'request_size' => strlen(json_encode($data)),
            'response_code' => $response['http_code'] ?? 'unknown',
            'success' => $success,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        log_message('debug', 'PEPPOL API Call: ' . json_encode($log_data));
        
        if (!$success) {
            log_message('error', 'PEPPOL API Error Details: ' . json_encode($response));
        }
    }
}
```

## Advanced Features

### Webhook Security

Implement secure webhook handling:

```php
public function webhook($payload) {
    // Verify webhook source
    if (!$this->verify_webhook_source($_SERVER)) {
        return ['success' => false, 'message' => 'Unauthorized webhook source'];
    }
    
    // Validate signature
    if (!$this->validate_webhook_signature($payload)) {
        return ['success' => false, 'message' => 'Invalid webhook signature'];
    }
    
    // Rate limiting
    if (!$this->check_webhook_rate_limit()) {
        return ['success' => false, 'message' => 'Rate limit exceeded'];
    }
    
    // Process webhook
    return $this->process_webhook_payload($payload);
}
```

### Retry Logic

Implement smart retry mechanisms:

```php
private function send_with_retry($data, $max_retries = 3) {
    $attempt = 0;
    
    while ($attempt < $max_retries) {
        $result = $this->call_api($this->endpoint, $data);
        
        if ($result['success']) {
            return $result;
        }
        
        $attempt++;
        
        // Exponential backoff
        if ($attempt < $max_retries) {
            sleep(pow(2, $attempt));
        }
        
        // Log retry attempt
        $this->debug_log("Retry attempt {$attempt} failed", $result);
    }
    
    return $result; // Return last failure
}
```

### Performance Optimization

Optimize for high-volume usage:

```php
private function batch_send($documents) {
    // Check if provider supports batch operations
    if (!$this->supports_batch_send()) {
        return $this->send_individually($documents);
    }
    
    // Implement batch sending
    $batch_data = $this->prepare_batch_data($documents);
    return $this->call_api($this->batch_endpoint, $batch_data);
}
```

## Support and Resources

### Getting Help

- **Provider Documentation**: Always refer to your PEPPOL provider's API documentation
- **PEPPOL Standards**: Review OpenPEPPOL specifications for compliance requirements
- **Community Forums**: Join PEPPOL developer communities for best practices
- **Testing Tools**: Use PEPPOL validation tools to test UBL compliance

### Resources

- **OpenPEPPOL**: [https://peppol.eu/](https://peppol.eu/)
- **UBL Standards**: [https://www.oasis-open.org/committees/ubl/](https://www.oasis-open.org/committees/ubl/)
- **PEPPOL Directory**: [https://directory.peppol.eu/](https://directory.peppol.eu/)
- **Provider List**: [https://peppol.eu/who-is-who/peppol-certified-aps/](https://peppol.eu/who-is-who/peppol-certified-aps/)

This comprehensive guide should provide everything needed to develop custom PEPPOL providers for the Perfex CRM PEPPOL module. For specific implementation questions or advanced use cases, consult your PEPPOL provider's documentation and the broader PEPPOL developer community.