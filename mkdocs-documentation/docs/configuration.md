# Configuration

The PEPPOL module requires configuration in multiple areas to function properly. Navigate to **Settings > PEPPOL Settings** to access all configuration options through four main tabs.

![PEPPOL Config](./media/01.png)

## General Settings Tab

The General Settings tab contains your company's core PEPPOL configuration:

### Company PEPPOL Identifier

- **PEPPOL Scheme**: Select from available identifier schemes (e.g., 0208 for Norwegian organization number, 0007 for Swedish organization number)
- **PEPPOL Identifier**: Your unique business identifier corresponding to the selected scheme
- **Help**: The system provides autocomplete suggestions for common schemes. Format: scheme:identifier (e.g., 0208:0123456789) . You can get the information from where you register your PEPPOL.

### Company Country

- **Country Selection**: Choose your company's country from the dropdown list
- **Purpose**: Used in UBL documents and PEPPOL transmission for proper routing and compliance

## Bank Information Tab

Configure banking details that will be included in UBL documents for payment processing:

### Bank Account Information

- **Bank Account Number/IBAN**: Your company's bank account number or IBAN
- **Bank Name**: The name of your banking institution  
- **Bank BIC/SWIFT Code**: International bank identifier code

### Validation Features

- **IBAN Validation**: Real-time validation for IBAN format and country-specific lengths
- **BIC Validation**: Automatic validation of BIC/SWIFT code format
- **Account Number Validation**: Basic validation for non-IBAN account numbers

## Providers Tab

Manage your PEPPOL access point provider configuration:

### Provider Selection

- **Active Provider**: Choose from registered providers (currently includes Ademico)
- **Provider Information**: Each provider displays its name, description, and version

### Provider-Specific Settings

Settings vary by provider, but typically include:

- **API Credentials**: Authentication keys and tokens
- **Environment Settings**: Sandbox vs Production mode selection
- **Connection Parameters**: Endpoints, timeouts, and other technical settings

### Connection Testing

- **Test Connection Button**: Verify your provider configuration
- **Real-time Results**: Immediate feedback on connection status and errors

### Ademico Provider Settings

When Ademico is selected as your provider, configure these specific settings:

- **API Endpoint**: Provider's API URL
- **API Key**: Authentication token from Ademico
- **Company ID**: Your company identifier with Ademico
- **Environment**: Sandbox or Production mode
- **Connection Testing**: Built-in connectivity verification

## Cron and Notifications Tab

Configure automated processing and notification settings:

### Notification Timing

- **Notification Lookup Time**: Hours to look back for notifications with the Access Point Provider (APP) (default: 72 hours)
- **Cron Interval**: Minutes between automatic notification checks (default: 5 minutes)

### Automatic Processing

- **Last Notification Check**: Displays when the system last checked for notifications
- **Next Notification Check**: Shows calculated next check time based on interval settings

### Expense Auto-Creation

- **Auto Create Invoice Expenses**: Automatically create expenses from FULLY_PAID received invoices
- **Auto Create Credit Note Expenses**: Automatically create negative amount expenses from ACCEPTED received credit notes. 

We have these option as Perfex do not support vendor credit and invoices by default.

## Testing Your Configuration

Before going live, test your setup:

1. **Test Provider Connection**: Use the "Test Connection" button in the Providers tab
2. **Verify Company Information**: Ensure PEPPOL identifier and scheme are correct
3. **Send Test Documents**: Send documents to sandbox environments first
4. **Monitor Notification Processing**: Check the timing settings in the Notifications tab

## Next Steps

After configuration, proceed to [Usage](usage.md) to start sending and receiving PEPPOL documents.
