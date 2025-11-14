# PEPPOL Integration Module for Perfex CRM

This module enables sending and receiving invoices through the PEPPOL network using various access point providers.

## Features

- **Multi-Provider Support**: Supports Ademico, Unit4, and Recommand PEPPOL access points
- **Live & Sandbox Environments**: Switch between testing and production environments
- **Automatic UBL Generation**: Converts Perfex CRM invoices to PEPPOL-compliant UBL format
- **Invoice Sending**: Send invoices directly to PEPPOL network from CRM
- **Document Receiving**: Receive and process incoming PEPPOL documents
- **Status Tracking**: Monitor delivery status and maintain audit logs
- **Webhook Support**: Real-time notifications for document events
- **Auto-Processing**: Automatically convert received documents to CRM invoices

## Requirements

- Perfex CRM version 2.3.0 or higher
- PHP 7.4 or higher
- Active PEPPOL access point account (Ademico, Unit4, or Recommand)
- Valid PEPPOL participant identifier for your company

## Installation

1. **Upload the Module**
   ```
   Upload the 'peppol' folder to your Perfex CRM modules directory:
   /modules/peppol/
   ```

2. **Activate the Module**
   - Go to Setup → Modules
   - Find "PEPPOL Integration" and click Activate
   - The module will automatically create required database tables

3. **Configure Settings**
   - Go to Setup → Settings → PEPPOL
   - Configure your provider settings and company PEPPOL identifier

## Configuration

### 1. Basic Settings

**Active Provider**: Choose your PEPPOL access point provider
- Ademico Software
- Unit4 Access Point  
- Recommand

**Environment**: Select testing or production environment
- Sandbox: For testing (safe)
- Live: For production use

**Auto-send**: Automatically send invoices when marked as "Sent"
**Auto-process**: Automatically convert received documents to invoices

### 2. Company PEPPOL Settings

**PEPPOL Identifier**: Your company's PEPPOL participant identifier
**Identifier Scheme**: The scheme used (e.g., 0088 for GLN)

Example:
- Identifier: `1234567890123`
- Scheme: `0088` (GLN)

### 3. Provider-Specific Settings

#### Ademico Software
- **API Key**: Your Ademico API key
- **Company ID**: Your Ademico company identifier
- **Endpoint URL**: Production API endpoint
- **Sandbox Endpoint**: Testing API endpoint

#### Unit4 Access Point
- **Username**: Your Unit4 username
- **Password**: Your Unit4 password
- **Endpoint URL**: Production API endpoint
- **Sandbox Endpoint**: Testing API endpoint

#### Recommand
- **API Key**: Your Recommand API key
- **Company ID**: Your Recommand company identifier
- **Endpoint URL**: Production API endpoint
- **Sandbox Endpoint**: Testing API endpoint

### 4. Client Configuration

For each client that should receive PEPPOL invoices:

1. Edit the client in Perfex CRM
2. Add their **PEPPOL Identifier**
3. Select the appropriate **Identifier Scheme**

## Usage

### Sending Invoices

1. **Manual Sending**:
   - Create an invoice in Perfex CRM
   - Mark the invoice as "Sent"
   - Go to Sales → PEPPOL Invoices
   - Click "Send Now" for the invoice

2. **Automatic Sending**:
   - Enable "Auto-send" in PEPPOL settings
   - Invoices will be sent automatically when marked as "Sent"

### Receiving Documents

Received PEPPOL documents are automatically processed if auto-processing is enabled:

1. Document arrives via webhook
2. System validates the UBL content
3. Creates a new invoice in Perfex CRM
4. Links to existing client or creates new client

### Monitoring

- **PEPPOL Invoices**: View all sent invoices and their status
- **PEPPOL Logs**: Detailed audit trail of all activities
- **Received Documents**: View and process incoming documents

## Status Types

- **Pending**: Queued for sending
- **Sending**: Currently being sent
- **Sent**: Successfully sent to PEPPOL network
- **Delivered**: Confirmed delivery to recipient
- **Failed**: Sending failed (check logs for details)

## Webhooks

Configure webhooks in your PEPPOL provider account:

**Webhook URLs**:
- General: `https://your-domain.com/peppol/webhook?provider=ademico`
- Ademico specific: `https://your-domain.com/peppol/webhook/ademico`
- Unit4 specific: `https://your-domain.com/peppol/webhook/unit4`
- Recommand specific: `https://your-domain.com/peppol/webhook/recommand`

Replace `your-domain.com` with your actual domain. These endpoints are publicly accessible and don't require authentication.

## Cron Jobs

Add this to your server's cron job for automatic processing:

```bash
# Process PEPPOL tasks every 5 minutes
*/5 * * * * /usr/bin/php /path/to/perfex/index.php peppol/cron
```

Or add this to your existing Perfex CRM cron job - the module will hook into the cron system automatically.

## Troubleshooting

### Common Issues

1. **"PEPPOL not configured" error**
   - Check that all required settings are filled
   - Test connection with your provider

2. **"Client does not have PEPPOL identifier" error**
   - Add PEPPOL identifier to the client record
   - Ensure the identifier format is correct

3. **Connection test fails**
   - Verify API credentials
   - Check that the correct environment is selected
   - Ensure firewall allows outbound connections

4. **UBL generation errors**
   - Verify invoice has all required fields
   - Check that company information is complete

### Debug Mode

Enable debug logging by adding this to your `application/config/config.php`:

```php
$config['log_threshold'] = 2; // Enable error and debug logging
```

Check logs in `application/logs/` for detailed error information.

### Support

For technical support:
1. Check the PEPPOL logs in the admin panel
2. Review server error logs
3. Verify provider-specific documentation
4. Test with sandbox environment first

## Security Notes

- Always test in sandbox environment before going live
- Keep API credentials secure and rotate regularly
- Monitor logs for suspicious activity
- Ensure webhook endpoints are properly secured

## Technical Details

### UBL Format

The module generates PEPPOL BIS Billing 3.0 compliant UBL invoices with:
- CustomizationID: `urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0`
- ProfileID: `urn:fdc:peppol.eu:2017:poacc:billing:01:1.0`

### Database Tables

- `peppol_invoices`: Tracks sent invoices and their status
- `peppol_received_documents`: Stores received PEPPOL documents  
- `peppol_logs`: Comprehensive audit trail

### API Integration

The module supports multiple PEPPOL access point providers through a unified interface, making it easy to switch providers or add new ones.

## License

This module is provided as-is for integration with Perfex CRM. Ensure compliance with PEPPOL regulations in your jurisdiction.