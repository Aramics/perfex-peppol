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
    'label' => _l('peppol_company_identifier'),
    'help_text' => _l('peppol_company_scheme_identifier_help'),
    'required' => false,
    'enable_autocomplete' => true,
    'container_id' => 'peppol_company_identifier'
]); ?>

<!-- Company Country -->
<div class="form-group">
    <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
        data-title="<?php echo e(_l('peppol_company_country_code_help')); ?>"></i>
    <label for="peppol-company-country-code"><?php echo _l('clients_country'); ?></label>
    <select name="settings[peppol_company_country_code]" class="form-control" id="peppol-company-country-code">
        <?php foreach (get_all_countries() as $country) { ?>
        <option value="<?php echo $country['iso2']; ?>"
            <?php if ($country_value == $country['iso2']) echo 'selected'; ?>>
            <?php echo $country['short_name']; ?>
        </option>
        <?php } ?>
    </select>
    <small class="help-block text-muted"></small>
</div>

<hr />

<div class="row">
    <div class="col-md-12">
        <?php echo render_yes_no_option('peppol_auto_create_invoice_expenses', _l('peppol_auto_create_invoice_expenses'), _l('peppol_auto_create_invoice_expenses_help')); ?>
    </div>
    <div class="col-md-12">
        <?php echo render_yes_no_option('peppol_auto_create_credit_note_expenses', _l('peppol_auto_create_credit_note_expenses'), _l('peppol_auto_create_credit_note_expenses_help')); ?>
    </div>
</div>