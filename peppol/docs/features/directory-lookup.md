# Directory Lookup Feature

The Directory Lookup feature provides comprehensive management of customer PEPPOL participant information. It automatically searches the PEPPOL Directory Service to find and fill missing customer PEPPOL identifiers.

## Overview

The Directory feature offers a centralized view of all customers and their PEPPOL information, with powerful tools for automatic data enrichment.

## Accessing the Directory

1. Navigate to your Perfex CRM admin panel
2. Go to **Peppol > Directory** from the main menu
3. The directory page displays all customers with their current PEPPOL information

## Features

### Customer Directory Table

The directory displays a comprehensive table with the following information:

| Column                | Description                                                |
| --------------------- | ---------------------------------------------------------- |
| **Company**           | Customer company name (clickable to view customer profile) |
| **VAT Number**        | Customer VAT number for PEPPOL lookup                      |
| **Peppol Scheme**     | Current PEPPOL scheme identifier (if available)            |
| **Peppol Identifier** | Current PEPPOL participant identifier (if available)       |
| **Status**            | Customer account status (Active/Inactive)                  |
| **Options**           | Individual lookup action button                            |

### Search and Filtering

-   **Real-time search**: Search across all customer data instantly
-   **Column sorting**: Sort by any column (company, VAT, scheme, identifier, status)
-   **Pagination**: Efficiently browse large customer lists
-   **Server-side processing**: Fast performance with large datasets

### Auto Lookup Functionality

#### Batch Lookup

1. Click the **"Auto Lookup"** button at the top of the directory page
2. Choose from two options:
    - **All customers**: Process all customers in the system
    - **Select customers**: Choose specific customers from the list
3. Click **"Start Lookup"** to begin processing
4. Monitor real-time progress with detailed status updates
5. Review comprehensive results summary upon completion

#### Individual Lookup

-   Click the lookup button (🔍) next to any customer in the Options column
-   View instant success message with retrieved PEPPOL data
-   Table automatically refreshes to show updated information

## How Auto Lookup Works

### Search Strategy

The auto-lookup feature uses an intelligent search strategy:

1. **Primary Search**: Uses customer's VAT number (most accurate method)
2. **Fallback Search**: Uses company name if VAT search fails
3. **Smart Updates**: Only fills empty fields, preserves existing data
4. **Rate Limiting**: Respects API limits with built-in delays

### Progress Tracking

During batch operations, you'll see:

-   **Real-time progress**: "5/20 processed" style updates
-   **Success indicators**: Green notifications for successful lookups
-   **Error handling**: Clear error messages for failed lookups
-   **Multiple results**: Notifications when manual review is needed

### Results Categories

| Result Type              | Description                                    |
| ------------------------ | ---------------------------------------------- |
| **Successfully Updated** | Customer data found and fields updated         |
| **Failed**               | No matching data found in directory            |
| **Multiple Results**     | Multiple matches found, requires manual review |

## Data Sources

The lookup feature searches the official PEPPOL Directory Service:

-   **URL**: `https://directory.peppol.eu/api/v1`
-   **Coverage**: EU and international PEPPOL participants
-   **Accuracy**: Official, up-to-date participant data
-   **Rate Limits**: Respectful API usage with built-in delays

## Automation Options

### Cron Job Setup

For automated daily processing, add this to your server crontab:

```bash
# Run daily at 2:00 AM
0 2 * * * cd /path/to/perfex && php index.php peppol/cron_lookup
```

### Protection Mechanisms

-   **Frequency Control**: Automated runs limited to once every 12 hours
-   **Error Handling**: Graceful handling of API failures
-   **Logging**: All activities logged for monitoring
-   **Safe Updates**: Only empty fields are modified

## Best Practices

### When to Use Auto Lookup

-   **New Customer Onboarding**: Fill PEPPOL data for new customers
-   **Data Migration**: Populate existing customer records
-   **Regular Maintenance**: Keep directory information current
-   **Compliance Preparation**: Ensure complete PEPPOL participant data

### Tips for Success

1. **Ensure VAT Numbers**: Customers with VAT numbers have higher success rates
2. **Review Multiple Results**: Some companies may have multiple PEPPOL identifiers
3. **Regular Updates**: Run lookups periodically to catch new participants
4. **Verify Data**: Review auto-filled data before sending documents

## Technical Implementation

### Architecture

-   **Server-side Processing**: Efficient handling of large customer datasets
-   **AJAX Communication**: Real-time updates without page refreshes
-   **DataTable Integration**: Uses Perfex CRM's standard table patterns
-   **Subquery Optimization**: Prevents duplicate entries from database joins

### Files Structure

```
modules/peppol/
├── controllers/traits/
│   └── Peppol_directory_lookup_trait.php    # Directory controller methods
├── libraries/
│   └── Peppol_directory_lookup.php          # Core lookup logic
├── views/admin/
│   ├── peppol/directory.php                 # Main directory page
│   └── tables/peppol_directory.php          # DataTable configuration
├── assets/js/
│   └── peppol_directory_lookup.js           # Frontend functionality
└── language/*/
    └── peppol_lang.php                      # Translations
```

### Database Schema

Uses existing Perfex CRM custom fields:

-   `customers_peppol_scheme`: PEPPOL scheme identifier (e.g., "0208")
-   `customers_peppol_identifier`: PEPPOL participant identifier (e.g., "123456789")

## Troubleshooting

### Common Issues

| Issue                  | Solution                                 |
| ---------------------- | ---------------------------------------- |
| **No results found**   | Verify customer VAT number is correct    |
| **Multiple results**   | Manually select appropriate PEPPOL ID    |
| **Lookup failures**    | Check internet connection and API status |
| **Performance issues** | Use batch processing for large datasets  |

### Error Messages

-   **"Invalid customer ID"**: Customer record not found
-   **"Request failed"**: Network or API connectivity issue
-   **"Lookup failed: [reason]"**: Specific API error returned

### Debugging

1. Check browser console for JavaScript errors
2. Review Perfex CRM logs for server-side issues
3. Verify API connectivity with connection test
4. Ensure proper permissions for PEPPOL module access

## API Reference

### Endpoints

-   `GET /admin/peppol/directory` - Directory page with DataTable
-   `POST /admin/peppol/ajax_batch_lookup_progress` - Batch processing
-   `CLI php index.php peppol/cron_lookup` - Automated cron processing

### Response Format

```json
{
	"success": true,
	"participant": {
		"name": "Example Company Ltd",
		"scheme": "0208",
		"identifier": "123456789"
	}
}
```

## Future Enhancements

Planned improvements for the Directory Lookup feature:

-   **Advanced Filtering**: Filter by PEPPOL status, country, or scheme
-   **Export Functionality**: Export directory data to CSV/Excel
-   **Bulk Import**: Import PEPPOL identifiers from external sources
-   **Validation Tools**: Verify PEPPOL identifier validity
-   **History Tracking**: Track lookup history and changes
