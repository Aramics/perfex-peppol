<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="scroller arrow-left tw-mt-px"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right tw-mt-px"><i class="fa fa-angle-right"></i></div>
    <!-- Nav tabs -->
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <li role="presentation" class="active">
                <a href="#peppol_general" aria-controls="peppol_general" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_general_settings'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_bank" aria-controls="peppol_bank" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_bank_information'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_providers" aria-controls="peppol_providers" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_providers'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Tab panes -->
<div class="tab-content mtop30">
    <!-- General Settings Tab -->
    <div role="tabpanel" class="tab-pane active" id="peppol_general">
        <?php
        // Load the PEPPOL input helper
        require_once(__DIR__ . '/../../helpers/peppol_input_helper.php');

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
    </div>

    <!-- Bank Information Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_bank">
        <?php
        // Get current bank information values
        $bank_account = get_option('peppol_bank_account') ?: '';
        $bank_bic = get_option('peppol_bank_bic') ?: '';
        $bank_name = get_option('peppol_bank_name') ?: '';
        ?>

        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <?php echo _l('peppol_bank_information_help'); ?>
        </div>

        <!-- Bank Account Number/IBAN -->
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?php echo e(_l('peppol_bank_account_help')); ?>"></i>
        <?php echo render_input('settings[peppol_bank_account]', _l('peppol_bank_account'), $bank_account, 'text', ['placeholder' => _l('peppol_bank_account_placeholder'), 'id' => 'peppol-bank-account']); ?>

        <!-- Bank Account Name -->
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?php echo e(_l('peppol_bank_name_help')); ?>"></i>
        <?php echo render_input('settings[peppol_bank_name]', _l('peppol_bank_name'), $bank_name, 'text', ['placeholder' => _l('peppol_bank_name_placeholder'), 'id' => 'peppol-bank-name']); ?>

        <!-- Bank BIC/SWIFT Code -->
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?php echo e(_l('peppol_bank_bic_help')); ?>"></i>
        <?php echo render_input('settings[peppol_bank_bic]', _l('peppol_bank_bic'), $bank_bic, 'text', ['placeholder' => _l('peppol_bank_bic_placeholder'), 'id' => 'peppol-bank-bic']); ?>

    </div>

    <!-- Providers Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_providers">
        <?php
        // Get registered provider instances
        $provider_instances = peppol_get_registered_providers();
        $active_provider = get_option('peppol_active_provider', '');

        // Convert provider instances to info for display
        $providers = [];
        foreach ($provider_instances as $provider_id => $instance) {
            try {
                if ($instance instanceof Abstract_peppol_provider) {
                    $providers[] = $instance->get_provider_info();
                }
            } catch (Exception $e) {
                // Skip invalid providers
                continue;
            }
        }
        ?>

        <?php if (empty($providers)) : ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <?php echo _l('peppol_no_providers_registered'); ?>
        </div>
        <?php else : ?>
        <form method="post" action="<?php echo admin_url('settings'); ?>">
            <input type="hidden" name="group" value="peppol" />

            <!-- Active Provider Selection -->
            <div class="form-group">
                <?php echo render_select('settings[peppol_active_provider]', $providers, ['id', 'name'], _l('peppol_active_provider'), $active_provider, ['onchange' => 'peppolProviderChanged()']); ?>
                <small class="help-block"><?php echo _l('peppol_active_provider_help'); ?></small>
            </div>

            <hr />

            <!-- Provider Configurations -->
            <?php foreach ($providers as $provider) : ?>
            <div class="provider-config" id="provider-config-<?php echo e($provider['id']); ?>"
                style="display: <?php echo $provider['id'] === $active_provider ? 'block' : 'none'; ?>;">
                <?php
                        // Render provider-specific configuration fields from the instance
                        try {
                            if (isset($provider_instances[$provider['id']])) {
                                $instance = $provider_instances[$provider['id']];
                                // Render settings using the provider's own inputs and values
                                echo $instance->render_setting_inputs();
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error loading provider settings: ' . e($e->getMessage()) . '</div>';
                        }
                        ?>

                <?php if (!empty($provider['test_connection']) && $provider['test_connection']) : ?>
                <hr />
                <div class="form-group">
                    <button type="button" class="btn btn-info btn-test-connection"
                        data-provider="<?php echo e($provider['id']); ?>">
                        <i class="fa fa-plug"></i>
                        <?php echo _l('peppol_test_connection'); ?>
                    </button>
                    <div id="test-result-<?php echo e($provider['id']); ?>" class="test-connection-result"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </form>
        <?php endif; ?>
    </div>

</div>

<style>
.form-control.has-error {
    border-color: #d73925;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #f4a193;
}

.form-control.has-success {
    border-color: #84c541;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #b7df8b;
}

.text-danger {
    color: #d73925 !important;
}
</style>

<script>
function peppolProviderChanged() {
    var activeProvider = $('select[name="settings[peppol_active_provider]"]').val();

    // Hide all provider configs
    $('.provider-config').hide();

    // Show selected provider config
    if (activeProvider) {
        $('#provider-config-' + activeProvider).show();
    }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    peppolProviderChanged();

    $(document).on('click', '.btn-test-connection', function() {
        var provider = $(this).data('provider');
        var button = $(this);
        var resultDiv = $('#test-result-' + provider);

        button.prop('disabled', true);
        button.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('peppol_testing'); ?>...');
        resultDiv.html('');

        // Get provider settings
        var providerSettings = {};
        $('#provider-config-' + provider + ' input, #provider-config-' + provider + ' select').each(
            function() {
                var fieldName = $(this).attr('name');
                var fieldValue = $(this).val();

                // Extract clean field name from settings[peppol_provider_field] format
                if (fieldName && fieldName.startsWith('settings[')) {
                    var cleanName = fieldName.slice(9, -1); // Remove 'settings[' and ']'
                    providerSettings[cleanName] = fieldValue;
                }
            });

        $.post(admin_url + 'peppol/test_provider_connection', {
            provider: provider,
            settings: providerSettings
        }, function(response) {

            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            if (response.success) {
                resultDiv.html(
                    '<div class="alert alert-success mtop10"><i class="fa fa-check"></i> ' +
                    response.message + '</div>');
            } else {
                resultDiv.html(
                    '<div class="alert alert-danger mtop10"><i class="fa fa-times"></i> ' +
                    response.message + '</div>');
            }
        }).fail(function() {
            resultDiv.html(
                '<div class="alert alert-danger mtop10"><i class="fa fa-times"></i> <?php echo _l('something_went_wrong'); ?></div>'
            );
        }).always(function() {
            button.prop('disabled', false);
            button.html(
                '<i class="fa fa-plug"></i> <?php echo _l('peppol_test_connection'); ?>');
        });
    });

    // Bank Information Validation
    $('#peppol-bank-account').on('input', function() {
        var iban = $(this).val().replace(/\s/g, ''); // Remove spaces
        var isValid = validateIBAN(iban) || validateAccountNumber(iban);

        if (iban.length > 0) {
            if (isValid) {
                $(this).removeClass('has-error').addClass('has-success');
            } else {
                $(this).removeClass('has-success').addClass('has-error');
            }
        } else {
            $(this).removeClass('has-error has-success');
        }
    });

    $('#peppol-bank-bic').on('input', function() {
        var bic = $(this).val().toUpperCase();
        var isValid = validateBIC(bic);

        if (bic.length > 0) {
            if (isValid) {
                $(this).removeClass('has-error').addClass('has-success');
            } else {
                $(this).removeClass('has-success').addClass('has-error');
            }
        } else {
            $(this).removeClass('has-error has-success');
        }
    });
});

// IBAN Validation
function validateIBAN(iban) {
    if (!iban || iban.length < 4) return false;

    // Basic IBAN format check
    var ibanRegex = /^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/;
    if (!ibanRegex.test(iban)) return false;

    // IBAN length check by country
    var countryLengths = {
        'AD': 24,
        'AE': 23,
        'AL': 28,
        'AT': 20,
        'AZ': 28,
        'BA': 20,
        'BE': 16,
        'BG': 22,
        'BH': 22,
        'BR': 29,
        'BY': 28,
        'CH': 21,
        'CR': 22,
        'CY': 28,
        'CZ': 24,
        'DE': 22,
        'DK': 18,
        'DO': 28,
        'EE': 20,
        'EG': 29,
        'ES': 24,
        'FI': 18,
        'FO': 18,
        'FR': 27,
        'GB': 22,
        'GE': 22,
        'GI': 23,
        'GL': 18,
        'GR': 27,
        'GT': 28,
        'HR': 21,
        'HU': 28,
        'IE': 22,
        'IL': 23,
        'IS': 26,
        'IT': 27,
        'JO': 30,
        'KW': 30,
        'KZ': 20,
        'LB': 28,
        'LC': 32,
        'LI': 21,
        'LT': 20,
        'LU': 20,
        'LV': 21,
        'MC': 27,
        'MD': 24,
        'ME': 22,
        'MK': 19,
        'MR': 27,
        'MT': 31,
        'MU': 30,
        'NL': 18,
        'NO': 15,
        'PK': 24,
        'PL': 28,
        'PS': 29,
        'PT': 25,
        'QA': 29,
        'RO': 24,
        'RS': 22,
        'SA': 24,
        'SE': 24,
        'SI': 19,
        'SK': 24,
        'SM': 27,
        'TN': 24,
        'TR': 26,
        'UA': 29,
        'VG': 24,
        'XK': 20
    };

    var countryCode = iban.substr(0, 2);
    var expectedLength = countryLengths[countryCode];

    return expectedLength && iban.length === expectedLength;
}

// Basic Account Number Validation (for non-IBAN accounts)
function validateAccountNumber(account) {
    if (!account || account.length < 4) return false;

    // Allow various formats: digits, dashes, spaces
    var accountRegex = /^[0-9\-\s]+$/;
    return accountRegex.test(account) && account.replace(/[\-\s]/g, '').length >= 4;
}

// BIC/SWIFT Code Validation
function validateBIC(bic) {
    if (!bic || bic.length < 8) return false;

    // BIC format: 4 letters (bank) + 2 letters (country) + 2 alphanumeric (location) + optional 3 alphanumeric (branch)
    var bicRegex = /^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/;
    return bicRegex.test(bic) && (bic.length === 8 || bic.length === 11);
}
</script>