# Simplified Peppol Directory Lookup

This feature automatically looks up and fills customer Peppol fields using the existing custom fields from install.php:
- `customers_peppol_scheme` 
- `customers_peppol_identifier`

## Usage

### 1. UI Modal (Main Feature)

On the Peppol Documents page (`admin/peppol/documents`), there's an "Auto Lookup" button next to "Process Notifications". This opens a modal with:

- **Option 1**: Auto-lookup all customers without Peppol data
- **Option 2**: Select specific customers from a list
- **Progress tracking**: Shows real-time progress (e.g., "5/20 processed")  
- **Results summary**: 
  - Successfully Updated
  - Failed 
  - Multiple Results (when no VAT available)

### 2. Single Customer Auto-Lookup

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

1. **Auto-lookup**: Uses customer's VAT number first (most accurate), falls back to company name
2. **Batch processing**: Processes customers without existing Peppol data only
3. **Smart updates**: Only updates empty Peppol fields, won't overwrite existing data
4. **Rate limiting**: Includes delays to respect API limits
5. **Cron protection**: Won't run more than once every 12 hours

## Files Added

- `libraries/Peppol_directory_lookup.php` - Core lookup logic
- `controllers/traits/Peppol_directory_lookup_trait.php` - AJAX endpoints  
- `assets/js/peppol_directory_lookup.js` - Simple JavaScript
- Helper functions in `helpers/peppol_helper.php`
- Language strings in `language/english/peppol_lang.php`

Total: 5 files modified/added, uses existing custom fields from install.php