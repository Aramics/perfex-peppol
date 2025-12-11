# First Steps with PEPPOL Module

This guide helps you get started with the PEPPOL module after installation and configuration.

## Quick Start Checklist

- [ ] PEPPOL module installed
- [ ] Provider configured and tested
- [ ] Company PEPPOL identifier set
- [ ] At least one customer with PEPPOL data
- [ ] Test document ready to send

## Step 1: Set Up Customer PEPPOL Data

Before sending PEPPOL documents, you need customer PEPPOL identifiers.

### Using Directory Lookup (Recommended)

The fastest way to get started:

1. **Navigate to Directory**
   - Go to **PEPPOL > Directory**
   - View all customers in one place

2. **Run Auto Lookup**
   - Click **"Auto Lookup"** button
   - Select "All customers"
   - Click "Start Lookup"
   - Wait for processing to complete

3. **Review Results**
   - Check how many customers got PEPPOL data
   - Review any failed lookups
   - Handle multiple results manually

### Manual Entry

If auto-lookup doesn't find data:

1. **Edit Customer Record**
   - Go to **Customers > [Customer Name]**
   - Find the "PEPPOL Information" section
   - Add PEPPOL Scheme and Identifier manually

2. **Common PEPPOL Schemes**
   - `0208`: Norway organization numbers
   - `0007`: Sweden organization numbers  
   - `0088`: Private companies
   - `9906`: IT:VAT numbers
   - `9925`: IT:CIUS

## Step 2: Send Your First PEPPOL Document

### Sending an Invoice

1. **Create/Select Invoice**
   - Go to **Sales > Invoices**
   - Create a new invoice or select existing one
   - Ensure customer has PEPPOL identifier

2. **Send via PEPPOL**
   - Click the invoice actions dropdown
   - Select **"Send via PEPPOL"**
   - Confirm the sending action

3. **Monitor Status**
   - Go to **PEPPOL > Documents**
   - Track document status
   - View delivery confirmation

### Sending a Credit Note

1. **Create Credit Note**
   - Go to **Sales > Credit Notes**
   - Create new credit note
   - Link to original invoice if applicable

2. **PEPPOL Delivery**
   - Use the credit note actions menu
   - Select **"Send via PEPPOL"**
   - Confirm transmission

## Step 3: Handle Incoming Documents

### Processing Received Documents

1. **Check Documents Page**
   - Navigate to **PEPPOL > Documents**
   - Filter for "Received" status
   - Review incoming invoices/credit notes

2. **Create Expenses (Optional)**
   - Click on received invoice
   - Use **"Create Expense"** button
   - Review and save expense record

3. **Update Document Status**
   - Mark documents as processed
   - Send responses to senders
   - Track payment status

## Step 4: Explore Advanced Features

### Bulk Operations

1. **Bulk Document Sending**
   - Go to **Sales > Invoices**
   - Select multiple invoices
   - Use "PEPPOL Send" bulk action

2. **Batch Directory Updates**
   - Use **PEPPOL > Directory**
   - Process multiple customers at once
   - Monitor batch progress

### Automation Setup

1. **Automatic Processing**
   - Configure notification checking
   - Set up cron jobs for regular updates
   - Enable automatic expense creation

2. **Status Monitoring**
   - Review **PEPPOL > Logs**
   - Set up monitoring alerts
   - Track system performance

## Common First-Time Issues

### Customer Not Found

**Problem**: "Client PEPPOL identifier is required" error

**Solutions**:
1. Use Directory Lookup to find PEPPOL data
2. Ask customer for their PEPPOL identifier
3. Check if customer is PEPPOL-enabled

### Connection Issues

**Problem**: Documents fail to send

**Solutions**:
1. Test provider connection in settings
2. Verify API credentials
3. Check internet connectivity
4. Review provider documentation

### Invalid Document Format

**Problem**: "UBL validation failed" error

**Solutions**:
1. Check invoice/credit note data completeness
2. Verify customer information accuracy
3. Ensure all required fields are filled
4. Contact provider support if needed

## Best Practices for New Users

### Data Preparation

1. **Clean Customer Data**
   - Ensure accurate company names
   - Add VAT numbers where available
   - Verify address information

2. **PEPPOL Identifier Collection**
   - Ask customers for PEPPOL IDs during onboarding
   - Use Directory Lookup for existing customers
   - Maintain accurate PEPPOL data

### Testing Strategy

1. **Start with Test Environment**
   - Use sandbox/test provider settings
   - Send test documents first
   - Verify end-to-end process

2. **Gradual Rollout**
   - Start with willing customers
   - Process a few documents first
   - Scale up after successful testing

### Monitoring and Maintenance

1. **Regular Check-ins**
   - Review document status daily
   - Handle failed transmissions promptly
   - Update customer data regularly

2. **Performance Tracking**
   - Monitor delivery success rates
   - Track customer adoption
   - Identify process improvements

## Next Steps

Once you're comfortable with basic operations:

1. **Explore Advanced Features**
   - Learn about document responses
   - Set up automated workflows
   - Configure custom settings

2. **Integration Optimization**
   - Customize document templates
   - Set up automated notifications
   - Configure provider-specific options

3. **User Training**
   - Train staff on PEPPOL processes
   - Document your workflows
   - Create user guidelines

## Getting Help

### Built-in Resources

- **Documentation**: Full user guides and API reference
- **Troubleshooting**: Common issues and solutions
- **FAQ**: Frequently asked questions

### Support Channels

- **System Logs**: Check PEPPOL logs for detailed error information
- **Provider Support**: Contact your PEPPOL provider for network issues
- **Community**: Join PEPPOL user communities for tips and advice

### Useful Links

- [PEPPOL Official Documentation](https://peppol.eu/documentation/)
- [Directory Service](https://directory.peppol.eu/)
- [Provider Documentation](administration/providers.md)
- [Troubleshooting Guide](../troubleshooting.md)

## Success Metrics

Track these metrics to measure PEPPOL adoption success:

- **Documents Sent**: Number of successful PEPPOL transmissions
- **Customer Coverage**: Percentage of customers with PEPPOL data
- **Processing Time**: Time from invoice creation to PEPPOL delivery
- **Error Rate**: Percentage of failed transmissions
- **Response Time**: Time to process incoming documents

Regular monitoring of these metrics helps identify areas for improvement and demonstrates the value of PEPPOL integration.