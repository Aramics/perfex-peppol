<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Load the PEPPOL input helper
require_once(__DIR__ . '/../../../helpers/peppol_input_helper.php');

// Get current values
$scheme_value = get_option('peppol_company_scheme') ?: '0208';
$identifier_value = get_option('peppol_company_identifier') ?: '';
$country_value = get_option('peppol_company_country_code') ?: 'BE';
?>

<!-- PEPPOL Company Identifier -->
<?php echo render_peppol_scheme_identifier_input([
    'scheme_name' => 'settings[peppol_company_scheme]',
    'identifier_name' => 'settings[peppol_company_identifier]',
    'scheme_value' => $scheme_value,
    'identifier_value' => $identifier_value,
    'label' => 'Company PEPPOL Identifier',
    'help_text' => 'Enter your company\'s PEPPOL participant identifier. Start typing in the scheme field to see suggestions. Format: scheme:identifier (e.g., 0208:0123456789)',
    'required' => false,
    'enable_autocomplete' => true,
    'container_id' => 'peppol_company_identifier'
]); ?>

<!-- Company Country -->
<div class="form-group">
    <label for="peppol-company-country-code"><?php echo _l('country'); ?></label>
    <select name="settings[peppol_company_country_code]" class="form-control" id="peppol-company-country-code">
        <?php foreach (get_all_countries() as $country) { ?>
        <option value="<?php echo $country['iso2']; ?>"
            <?php if ($country_value == $country['iso2']) echo 'selected'; ?>>
            <?php echo $country['short_name']; ?>
        </option>
        <?php } ?>
    </select>
    <small class="help-block text-muted">Select the country for your company. This will be used in UBL documents
        and PEPPOL transmission.</small>
</div>