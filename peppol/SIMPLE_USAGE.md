# Peppol Directory Lookup

This feature automatically looks up and fills customer Peppol fields using the existing custom fields from install.php:
- `customers_peppol_scheme` 
- `customers_peppol_identifier`

## Quick Start Guide

### Accessing the Directory
1. Navigate to your Perfex CRM admin panel
2. Go to **Peppol > Directory** from the main menu
3. The directory page will display all customers with their current Peppol information

### Performing Auto Lookup
**For All Customers:**
1. Click the "Auto Lookup" button at the top of the directory page
2. Select "All customers" or choose specific customers
3. Click "Start Lookup" and monitor the progress
4. Review the results summary when complete

**For Individual Customers:**
1. Find the customer in the directory table
2. Click the lookup button (🔍) in the Options column
3. View the success message with retrieved Peppol data

## Detailed Usage

### 1. Peppol Directory Page (Main Feature)

Navigate to **Peppol > Directory** to access the dedicated directory management page. This page provides:

**Features:**
- **Customer Directory Table**: Displays all customers with their current Peppol information
  - Clickable customer names (links to customer profile)
  - VAT numbers
  - Current Peppol scheme and identifier values
  - Customer status (Active/Inactive)
  - Individual lookup buttons per customer
- **Batch Auto Lookup**: "Auto Lookup" button for processing all customers at once
- **Search & Filter**: Real-time search across all customer data
- **Progress Tracking**: Real-time progress display during batch operations

**Auto Lookup Modal Options:**
- **Option 1**: Auto-lookup all customers in the system
- **Option 2**: Select specific customers from a filtered list
- **Progress tracking**: Shows real-time progress (e.g., "5/20 processed")  
- **Results summary**: 
  - Successfully Updated
  - Failed 
  - Multiple Results (requires manual review)

### 2. Individual Customer Lookup

**From Directory Page:**
Click the lookup button (🔍) next to any customer in the directory table for instant individual lookup.

**From Customer Forms:**
Add this button to customer forms:
```php
<?= peppol_auto_lookup_button($customer_id) ?>
```

### 3. Cron Job (Automatic)

Add to server crontab for daily processing:
```bash
0 2 * * * cd /path/to/crm && php index.php peppol/cron_lookup
```

## How It Works

1. **Auto-lookup Strategy**: Uses customer's VAT number first (most accurate), falls back to company name search
2. **Directory Display**: Shows all customers in the system with their current Peppol data status
3. **Batch Processing**: Processes selected customers or all customers without existing Peppol data
4. **Smart Updates**: Only updates empty Peppol fields, won't overwrite existing data
5. **Rate Limiting**: Includes delays between API calls to respect directory service limits
6. **Cron Protection**: Automated runs won't execute more than once every 12 hours
7. **Real-time Updates**: Directory table refreshes automatically after successful lookups

## Implementation Details

### Files Added/Modified

**Core Logic:**
- `libraries/Peppol_directory_lookup.php` - Core directory lookup functionality
- `controllers/traits/Peppol_directory_lookup_trait.php` - Controller methods for directory and AJAX endpoints

**User Interface:**
- `views/admin/peppol/directory.php` - Dedicated directory page view
- `views/admin/tables/peppol_directory.php` - DataTable configuration for directory listing
- `assets/js/peppol_directory_lookup.js` - JavaScript for modal and real-time updates

**Configuration:**
- `hooks/add_admin_menu_permissions.php` - Added "Directory" menu item to Peppol submenu
- Language strings added to all language files (`language/*/peppol_lang.php`)

**Features Implemented:**
- Dedicated directory page with full customer listing
- Server-side DataTable with search, sorting, and pagination
- Individual and batch lookup functionality
- Real-time progress tracking during batch operations
- Modal interface for customer selection and progress display
- Translation support for all UI elements
- Menu integration in Peppol module

### Technical Architecture

- **DataTable Pattern**: Uses Perfex CRM's standard `render_datatable()` and `initDataTable()` patterns
- **Server-side Processing**: Efficient handling of large customer datasets
- **Subquery Optimization**: Prevents duplicate entries from custom field joins
- **AJAX Communication**: Real-time updates without page refreshes
- **Responsive Design**: Mobile-friendly table and modal interfaces

Total: 8 files added/modified, uses existing custom fields from install.php

## Full Documentation

For comprehensive documentation, see the MkDocs documentation:

```bash
# View documentation locally
cd modules/peppol
mkdocs serve
```

Documentation covers:
- **Getting Started**: Installation, configuration, and first steps
- **User Guides**: Step-by-step instructions for all features
- **Features**: Detailed feature documentation including Directory Lookup
- **Administration**: Settings, providers, logs, and automation
- **Developer Guide**: Architecture, extending, and custom providers
- **API Reference**: Complete API documentation
- **Troubleshooting**: Common issues and solutions

Online documentation: [View Full Documentation](docs/index.md)