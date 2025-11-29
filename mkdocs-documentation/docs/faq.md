# FAQ and Support

## Frequently Asked Questions

### General Questions

**Q: What is PEPPOL and why do I need it?**
A: PEPPOL (Pan-European Public Procurement On-Line) is a standardized electronic document exchange network. It's mandatory for B2G transactions in many EU countries and increasingly required for B2B transactions.

**Q: What Perfex CRM version is required?**
A: Perfex CRM version 3.1.* or higher is required.

**Q: Do I need a PEPPOL provider account?**
A: Yes, you need an active account with a PEPPOL access point provider to send and receive documents via the PEPPOL network.

### Technical Questions

**Q: Which document types are supported?**
A: The module supports invoices and credit notes. Purchase orders and other document types may be added in future versions.

**Q: What PEPPOL providers are supported?**
A: The module includes built-in support for Ademico provider. Additional providers can be added through the provider development framework. You can reach out for quote to implement your provider.

**Q: What happens if a document send fails?**
A: Failed documents are marked with SEND_FAILED status. You can view error details in the document management section and retry sending after fixing the issue.

### Configuration Questions

**Q: How do I find my customer's PEPPOL identifier?**
A: Ask your customers directly, check their official documents, or use PEPPOL directory services to search for registered participants.

**Q: What PEPPOL scheme codes should I use?**
A: Common schemes include 0208 (Norwegian organization number), 0007 (Swedish organization number), 9956 (Belgian enterprise number). Check with your provider for specific requirements.

**Q: Can I test before going live?**
A: Yes, most providers offer sandbox environments for testing. Configure your provider in sandbox mode first.

### Expense Management

**Q: When can I create expenses from received documents?**
A: Only from FULLY_PAID invoices and ACCEPTED credit notes. The document must be received from an external party.

**Q: How are expenses created from received documents?**
A: The system extracts available information from UBL content and pre-fills expense forms. You can review and modify the details before creating the expense.

**Q: What information is extracted for expenses?**
A: The system extracts amounts, dates, vendor information, and other available data from the UBL document content.

### Troubleshooting

**Q: Why don't I see PEPPOL options for a customer?**
A: Ensure the customer has valid PEPPOL identifier and scheme configured in their profile (custom field tab).

**Q: What if my provider connection test fails?**
A: Check your API credentials, ensure network connectivity, and verify with your provider that your account is active.

**Q: Can I resend failed documents?**
A: Yes, you can retry sending failed documents after fixing the underlying issue.

## Support Information

### Getting Help

- **Documentation**: This comprehensive documentation covers installation, configuration, and usage
- **Provider Support**: Contact your PEPPOL access point provider for network-related issues
- **System Logs**: Check PEPPOL logs in the admin interface for detailed error information. You can also check the Perfex log files.

### What We Provide

✅ Complete installation and configuration documentation
✅ Usage guides with screenshots and examples  
✅ Provider development framework documentation
✅ Bug fixes for confirmed issues
✅ Necessary Regular updates and improvements

### What Requires Additional Service

⚠️ Custom provider development
⚠️ On-site installation and setup
⚠️ Custom integration with third-party systems
⚠️ Advanced configuration consulting

### Before You Purchase

Please review the documentation and ask any questions before purchase. We want to ensure the module meets your specific requirements.

### Reporting Issues

When reporting issues, please include:

1. **Perfex CRM version**
2. **PHP version**
3. **Error messages from logs**
4. **Steps to reproduce the issue**
5. **Provider information** (if relevant)

### Best Practices for Support

1. **Check Documentation First**: Most questions are covered in this guide
2. **Review Provider Settings**: Many issues relate to provider configuration
3. **Test in Sandbox**: Always test configuration changes in sandbox mode
4. **Keep Backups**: Maintain backups before making system changes
5. **Monitor Logs**: Regular log monitoring helps identify issues early

### Contact Information

For technical support beyond the scope of this documentation:

- **Email**: support@turnsaas.com
- **Provider Issues**: Contact your PEPPOL access point provider directly
- **PEPPOL General**: Visit official PEPPOL community resources

### Response Times

We strive to respond to support requests within 24-48 hours during business days. Complex technical issues may require additional time for investigation and resolution.
