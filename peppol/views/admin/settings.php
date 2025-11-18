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
                    <option value="<?php echo $country['iso2']; ?>" <?php if ($country_value == $country['iso2']) echo 'selected'; ?>>
                        <?php echo $country['short_name']; ?>
                    </option>
                <?php } ?>
            </select>
            <small class="help-block text-muted">Select the country for your company. This will be used in UBL documents
                and PEPPOL transmission.</small>
        </div>
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
                    <div class="provider-config" id="provider-config-<?php echo e($provider['id']); ?>" style="display: <?php echo $provider['id'] === $active_provider ? 'block' : 'none'; ?>;">
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
                                <button type="button" class="btn btn-info btn-test-connection" data-provider="<?php echo e($provider['id']); ?>">
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
    });
</script>
