# Peppol Module Documentation

Welcome to the Perfex CRM Peppol Module documentation. This module enables seamless integration with the PEPPOL (Pan-European Public Procurement On-Line) network for electronic document exchange.

## What is PEPPOL?

PEPPOL is a standardized way of sending and receiving electronic business documents across Europe and beyond. It enables businesses to exchange invoices, credit notes, and other business documents electronically in a standardized format.

## Module Features

### 📄 Document Management
- Send invoices and credit notes via PEPPOL
- Receive and process incoming documents
- Track document status and delivery
- Automated expense creation from received documents

### 📋 Directory Lookup
- **NEW**: Dedicated Peppol Directory page
- Automatic customer Peppol identifier lookup
- Batch processing for multiple customers
- Individual and bulk lookup options
- Real-time progress tracking

### 🔌 Provider Integration
- Support for multiple PEPPOL access point providers
- Extensible provider architecture
- Provider-specific configuration
- Connection testing and validation

### 📊 Status Management
- Document response handling
- Invoice response codes (Acknowledged, Accepted, Rejected, etc.)
- Custom clarification messages
- Automated status updates

## Quick Start

1. **Install** the module following the [installation guide](getting-started/installation.md)
2. **Configure** your PEPPOL provider in [settings](getting-started/configuration.md)
3. **Set up** customer PEPPOL identifiers using the [Directory Lookup](features/directory-lookup.md)
4. **Send** your first PEPPOL document via the [user guide](user-guides/sending-documents.md)

## Directory Lookup Feature (Latest)

The newly added Directory Lookup feature provides a comprehensive solution for managing customer PEPPOL information:

- **Dedicated Page**: Navigate to **Peppol > Directory** for full directory management
- **Complete Customer Overview**: View all customers with their current PEPPOL data
- **Automatic Lookup**: Fill missing PEPPOL identifiers automatically
- **Batch Processing**: Process multiple customers at once
- **Real-time Updates**: See progress and results instantly

[Learn more about Directory Lookup →](features/directory-lookup.md)

## Module Architecture

The Peppol module follows Perfex CRM's modular architecture:

```
modules/peppol/
├── controllers/          # Request handling
├── libraries/           # Core business logic
├── models/             # Database operations  
├── views/              # User interface
├── hooks/              # System integration
└── language/           # Multi-language support
```

## Requirements

- Perfex CRM 3.0+
- PHP 7.4+
- Active PEPPOL access point provider account
- SSL certificate for webhook endpoints (production)

## Support

- [Troubleshooting Guide](troubleshooting.md)
- [Frequently Asked Questions](faq.md)
- [Developer Documentation](developer/architecture.md)

## Contributing

This module is designed to be extensible. See the [Developer Guide](developer/extending.md) for information on:

- Creating custom providers
- Extending functionality
- Adding new features
- Contributing to the codebase