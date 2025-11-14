# PEPPOL Provider Implementations

This document outlines the three PEPPOL access point providers implemented in this module and their specific characteristics.

## Overview

All providers implement the `Peppol_provider_interface` which ensures consistent functionality across different access points. The module uses a factory pattern to manage provider instantiation and configuration.

## Provider Comparison

| Feature | Ademico | Unit4 | Recommand |
|---------|---------|-------|-----------|
| **Authentication** | Bearer Token | Basic Auth | Bearer Token |
| **Document Format** | UBL XML | UBL XML | JSON + UBL |
| **Webhook Support** | ✅ | ✅ | ✅ |
| **Status Tracking** | ✅ | ✅ | ✅ |
| **Sandbox Environment** | ✅ | ✅ | ✅ |
| **Document Upload** | JSON API | Multipart Form | JSON API |
| **Signature Verification** | HMAC SHA-256 | Not Required | HMAC SHA-256 |

## Provider Details

### 1. Ademico Software

**Configuration:**
- API Key (Bearer token)
- Company ID
- Endpoint URL

**Endpoints:**
- Live: `https://api.ademico-software.com/peppol`
- Sandbox: `https://sandbox-api.ademico-software.com/peppol`

**Features:**
- JSON-based API with base64-encoded UBL content
- Webhook signature verification using HMAC SHA-256
- Real-time status updates via webhooks
- Document download capability

**Webhook Events:**
- `document_received` - New document received
- `document_delivered` - Document delivered to recipient
- `document_failed` - Document delivery failed

**API Methods:**
- `POST /documents/send` - Send document
- `GET /documents/{id}/status` - Get delivery status
- `GET /documents/{id}/content` - Download document
- `GET /ping` - Connection test

### 2. Unit4 Access Point

**Configuration:**
- Username
- Password
- Endpoint URL

**Endpoints:**
- Live: `https://ap.unit4.com`
- Sandbox: `https://test-ap.unit4.com`

**Features:**
- Multipart form data upload for documents
- Basic authentication
- Both JSON webhooks (status) and XML webhooks (documents)
- RESTful API design

**Webhook Events:**
- `DocumentDelivered` - Document delivered successfully
- `DocumentFailed` - Document delivery failed
- `DocumentReceived` - Document received notification
- Raw UBL XML for incoming documents

**API Methods:**
- `POST /rest/outbound/documents` - Send document
- `GET /rest/outbound/documents/{id}/status` - Get status
- `GET /rest/status` - Health check

### 3. Recommand

**Configuration:**
- API Key (Bearer token)
- Company ID

**Endpoints:**
- Live: `https://peppol.recommand.eu/api`
- Sandbox: `https://sandbox-peppol.recommand.eu/api`

**Features:**
- Simplified JSON format with automatic UBL conversion
- No need to generate UBL manually - accepts invoice data as JSON
- Webhook signature verification
- Company-specific API endpoints

**Webhook Events:**
- `document.received` - New document received
- `document.delivered` - Document delivered
- `document.failed` - Document failed
- `document.processed` - Document processed successfully

**API Methods:**
- `POST /peppol/{companyId}/sendDocument` - Send document
- `GET /peppol/{companyId}/documents/{id}` - Get document status
- `GET /peppol/{companyId}/documents/{id}/content` - Get document content
- `GET /health` - Health check

## Implementation Details

### Interface Design

All providers implement the `Peppol_provider_interface` with these required methods:

```php
public function send_document($ubl_content, $invoice, $client);
public function test_connection($environment = null);
public function get_delivery_status($document_id);
public function handle_webhook();
```

### Abstract Base Class

The `Abstract_peppol_provider` provides common functionality:

- Configuration validation
- Activity logging
- Response standardization
- UBL identifier extraction
- Document reference generation

### Factory Pattern

The `Peppol_provider_factory` manages:

- Provider registration and configuration
- Dynamic provider instantiation
- Capability checking
- Connection testing
- Configuration validation

## Adding New Providers

To add a new PEPPOL access point provider:

1. **Create Provider Class:**
   ```php
   class NewProvider_provider extends Abstract_peppol_provider
   {
       // Implement interface methods
   }
   ```

2. **Register with Factory:**
   ```php
   Peppol_provider_factory::register_provider('newprovider', [
       'name' => 'New Provider',
       'class' => 'NewProvider_provider',
       'config_fields' => ['api_key', 'endpoint'],
       'required_fields' => ['peppol_newprovider_api_key'],
       'endpoints' => [
           'live' => 'https://api.newprovider.com',
           'sandbox' => 'https://sandbox.newprovider.com'
       ],
       'features' => ['send', 'receive', 'status_tracking'],
       'authentication' => 'bearer_token'
   ]);
   ```

3. **Add Language Strings:**
   ```php
   $lang['peppol_newprovider_api_key'] = 'API Key';
   $lang['peppol_newprovider_endpoint'] = 'Endpoint URL';
   ```

4. **Update Installation:**
   ```php
   add_option('peppol_newprovider_api_key', '');
   add_option('peppol_newprovider_endpoint_url', '');
   ```

## Error Handling

Each provider implements consistent error handling:

- **Connection Errors:** Network timeouts, DNS failures
- **Authentication Errors:** Invalid credentials, expired tokens
- **Validation Errors:** Missing required fields, invalid UBL
- **API Errors:** Rate limits, server errors, invalid requests

## Security Considerations

### Webhook Security

- **Ademico:** HMAC SHA-256 signature verification
- **Unit4:** IP-based filtering (implementation dependent)
- **Recommand:** HMAC SHA-256 signature verification

### Credential Storage

- All API credentials stored in Perfex CRM options table
- Passwords encrypted where possible
- No hardcoded credentials in source code

### Data Protection

- UBL content temporarily stored during processing
- Sensitive data logged only in debug mode
- Webhook endpoints publicly accessible but secured

## Testing

Each provider includes test endpoints and methods:

- **Connection Testing:** Verify API credentials and connectivity
- **Sandbox Environments:** Safe testing without real document transmission
- **Health Checks:** Monitor provider API availability

## Monitoring

The module provides comprehensive logging:

- **Activity Logs:** All provider interactions logged
- **Error Tracking:** Failed operations with detailed error messages
- **Performance Metrics:** Response times and success rates
- **Statistics Dashboard:** Overview of document processing

## Support

For provider-specific issues:

- **Ademico:** Contact Ademico support with API logs
- **Unit4:** Check Unit4 documentation and support portal
- **Recommand:** Use Recommand's developer support channels

For module issues, check the PEPPOL logs in the admin panel and review the error messages for troubleshooting guidance.