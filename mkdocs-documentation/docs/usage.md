# Usage

After activation and configuration, the PEPPOL module integrates seamlessly into your Perfex CRM workflow. This guide covers all aspects of using the module.

## Navigation and Interface

The PEPPOL module adds several new menu items to your CRM:

- **PEPPOL Menu**: Main navigation menu for document management
- **PEPPOL Documents**: Centralized view of all PEPPOL documents
- **PEPPOL Logs**: Detailed logging and audit trails
- **Customer Integration**: PEPPOL options appear in customer profiles and invoices

## Sending Documents via PEPPOL

### Customer PEPPOL Fields

For customers to receive PEPPOL documents, add their PEPPOL information in customer profiles:

![Customer PEPPOL](./media/02.png)

Navigate to the customer profile and provide the PEPPOL register scheme and identifier. We have provided suggest list for scheme. Identifier is mostly part or all of the VAT number. 

- **PEPPOL Identifier**: Customer's business identifier
- **PEPPOL Scheme**: Customer's identifier scheme

#### Finding Customer PEPPOL Information

- Ask customers for their PEPPOL participant details
- Use PEPPOL directory services to search for registered participants
- Check with customers' finance departments or PEPPOL registration authority for accurate information

### Individual Invoice Sending

![Individual Sending](./media/03.png)

1. **Create Invoice**: Create an invoice as usual in Perfex CRM
2. **PEPPOL Indicator**: If the customer has PEPPOL information configured, you'll see a PEPPOL send option
3. **Send via PEPPOL**: Click the "Send via PEPPOL" button in the invoice view
4. **Status Tracking**: Monitor document status from the PEPPOL Documents page

### Bulk Document Operations

![Bulk Action Sending](./media/04.png)

The module supports bulk operations for efficiency:

1. Navigate to **Sales → Invoices** or **Sales → Credit Notes** or Customer Profile Invoice/Credit Note tab
2. You will find a drop down menu
3. Apply bulk actions like "Send All Unsent" or "Retry Failed" e.t.c

Possible bulk action are : sending and downloading of UBL.

### Sent Document Status Flow

After you send a document, PEPPOL documents follow these status transitions:

- **QUEUED**: Document is prepared and waiting to be sent
- **SENT**: Successfully transmitted to customer's access point
- **SEND_FAILED**: Transmission failed (requires retry)
- **REJECTED**: The customer have rejected your received document
- **FULLY_PAID**: Invoice has been marked as paid by customer
e.t.c

## Receiving Documents

### Document Reception

The module can receive PEPPOL documents from external parties:

1. **Provider Integration**: Documents are received through your PEPPOL provider
2. **UBL Processing**: Document data is extracted from UBL format  
3. **Document Storage**: Creates entries in PEPPOL Documents list
4. **Status Management**: Track received document status and responses

### Received Document Status Flow

After you received a document, PEPPOL documents follow these status transitions:

- **RECEIVED**: Document received from external party
- **ACKNOWLEDGE**: Document received from external party is acknowledged
- **ACCEPTED**: You have accepted the received document
- **REJECTED**: You have rejected the received document
e.t.c 

## Document Tracking and Monitoring

### Documents Dashboard

![Documents Dashboard](./media/05.png)

The PEPPOL Documents page provides:

- **Statistics Cards**: Overview of sent, received, failed, and paid documents
- **Expense Statistics**: Total expenses created from PEPPOL documents
- **Filter Options**: Search by status, type, provider, or date range
- **Export Functions**: Export document lists for reporting

### Managing Received/Sent Documents

For each document (sent to a customer or received), you can:

- **View Details**: See complete document information and UBL content
- **Download UBL**: Save original UBL files for records
- **Manage Status**: Respond to the sender about document status (received documents only)
- **Create Expense**: Convert received document to expenses (see below) 

### Document Details

![Document Details](./media/06.png)

Click on any document to view:

- **Metadata**: Document type, dates, amounts, status history
- **UBL Content**: Original UBL XML content (formatted)
- **Transmission Info**: Provider details and transmission logs
- **Associated Records**: Links to related invoices or expenses
- **Attachments**: Any additional files or documents

## Updating Status (Received Documents)

![Document Status Updating](./media/07.png)

For received documents, you can respond to seller by updating status i.e acknowleging receipt, marking as paid or rejected. You have the option to provide reasons and reason codes according to PEPPOL standard.

## Expense Management

### Converting Documents to Expenses

Create expenses from received PEPPOL documents:

![Expense Creation](./media/08.png)

1. **Eligible Documents**: Only FULLY_PAID invoices and ACCEPTED credit notes that were received from external parties
2. **Data Extraction**: Document information is extracted from UBL content
3. **Pre-filled Forms**: Expense forms are pre-populated with available document data
4. **Manual Review**: Review and modify the expense details before creating

### Document Data Processing

When creating expenses, the system:
- **Extracts Basic Info**: Amount, date, vendor information from UBL
- **Includes References**: PEPPOL document ID and vendor details in notes
- **Handles Currency**: Uses document currency and amounts
- **Links Records**: Maintains connection between PEPPOL document and expense

## Advanced Features

### Provider Integration

Current provider support:

- **Built-in Ademico Provider**: Ready to use with proper configuration
- **Provider Framework**: Architecture supports additional providers through development
- **Configuration Management**: Each provider has its own settings and connection testing

## Best Practices

### Sending Documents

1. **Verify Customer Data**: Always confirm PEPPOL identifiers before first use
2. **Test in Sandbox**: Use provider sandbox environments for initial testing
3. **Monitor Status**: Regularly check document status and respond to customer actions
4. **Maintain Records**: Keep backup copies of important documents

### Managing Expenses

1. **Review Before Creating**: Always review auto-detected tax and payment information
2. **Categorize Properly**: Assign appropriate expense categories
3. **Track References**: Maintain clear links between PEPPOL documents and expenses
4. **Regular Reconciliation**: Periodically reconcile PEPPOL expenses with bank statements

### Troubleshooting

Common issues and solutions:

- **Send Failures**: Check customer PEPPOL information is correctly configured
- **Provider Connection Issues**: Verify API credentials and provider settings
- **Expense Creation Issues**: Ensure document status is FULLY_PAID (invoices) or ACCEPTED (credit notes)
- **Document Status Issues**: Check provider connectivity and document processing logs

For detailed technical information and custom provider development, see the [Provider Development](provider_development.md) guide.
