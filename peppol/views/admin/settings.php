<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="horizontal-scrollable-tabs">
    <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <li role="presentation" class="active">
                <a href="#peppol_general" aria-controls="peppol_general" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_general_settings'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_connection" aria-controls="peppol_connection" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_connection_settings'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_automation" aria-controls="peppol_automation" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_automation_settings'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
    <!-- General Settings Tab -->
    <div role="tabpanel" class="tab-pane active" id="peppol_general">
        <div class="form-group">
            <label class="control-label clearfix">
                <?php echo _l('peppol_company_identifier'); ?>
            </label>

            <!-- PEPPOL Identifier with scheme:identifier format -->
            <div class="peppol-identifier-group">
                <div class="input-group">
                    <input type="text" class="form-control peppol-scheme-input" name="settings[peppol_company_scheme]"
                        value="<?php echo get_option('peppol_company_scheme'); ?>" list="peppol_scheme_suggestions"
                        placeholder="0208" required>

                    <span class="input-group-addon">:</span>

                    <input type="text" class="form-control peppol-identifier-input"
                        name="settings[peppol_company_identifier]"
                        value="<?php echo get_option('peppol_company_identifier'); ?>" placeholder="0123456789"
                        required>
                </div>

                <!-- Scheme suggestions datalist -->
                <datalist id="peppol_scheme_suggestions">
                    <option value="0002">0002 - SIRENE</option>
                    <option value="0007">0007 - ODETTE</option>
                    <option value="0009">0009 - EAN Location Code</option>
                    <option value="0037">0037 - LY (Norwegian)</option>
                    <option value="0060">0060 - DUNS (Data Universal Numbering System)</option>
                    <option value="0088">0088 - GLN (Global Location Number)</option>
                    <option value="0096">0096 - GTIN (Global Trade Item Number)</option>
                    <option value="0135">0135 - SIA Object Identifiers</option>
                    <option value="0142">0142 - SECETI Object Identifiers</option>
                    <option value="0151">0151 - Australian Business Number</option>
                    <option value="0183">0183 - Swiss Enterprise Identification Number</option>
                    <option value="0184">0184 - Danish CVR / DIGSTØ Identifier</option>
                    <option value="0190">0190 - Dutch Originator's Identification Number</option>
                    <option value="0191">0191 - Centre for Research Libraries</option>
                    <option value="0192">0192 - Belgian Enterprise Number / CBE</option>
                    <option value="0193">0193 - UBL.BE Party Identifier</option>
                    <option value="0195">0195 - Singapore UEN</option>
                    <option value="0196">0196 - Kennitala (Iceland)</option>
                    <option value="0198">0198 - ERSTORG</option>
                    <option value="0199">0199 - Legal Entity Identifier (LEI)</option>
                    <option value="0200">0200 - Legal entity code (Lithuania)</option>
                    <option value="0201">0201 - Codice Univoco Unità Organizzativa iPA</option>
                    <option value="0202">0202 - Indirizzo Telematico Ente</option>
                    <option value="0203">0203 - Codice Fiscale</option>
                    <option value="0204">0204 - Partita IVA</option>
                    <option value="0208">0208 - Leitweg-ID (Germany)</option>
                    <option value="0209">0209 - Entreprise ID</option>
                    <option value="0210">0210 - CODICE IPA</option>
                    <option value="0211">0211 - CODICE DESTINATARIO</option>
                    <option value="0212">0212 - Leitweg-ID (Austria)</option>
                    <option value="0213">0213 - Belgian KBO Number / CBE Number</option>
                    <option value="9901">9901 - Danish Ministry ID</option>
                    <option value="9902">9902 - Norwegian ID</option>
                    <option value="9904">9904 - Hungarian VAT</option>
                    <option value="9905">9905 - PEPPOL Contract Party ID</option>
                    <option value="9906">9906 - Andes</option>
                    <option value="9907">9907 - Andorra VAT</option>
                    <option value="9910">9910 - Hungary VAT</option>
                    <option value="9912">9912 - European Medicines Agency</option>
                    <option value="9913">9913 - Business Registers Network</option>
                    <option value="9914">9914 - Austrian Organisation Code</option>
                    <option value="9915">9915 - Austrian Ersatzverfahren ID</option>
                    <option value="9918">9918 - SWIFT BIC Code</option>
                    <option value="9919">9919 - German Company Register</option>
                    <option value="9920">9920 - Spanish VAT</option>
                    <option value="9922">9922 - Andorra Registration</option>
                    <option value="9923">9923 - French Enterprise Number</option>
                    <option value="9924">9924 - RETGS (Galicia)</option>
                    <option value="9925">9925 - VAT number (Generic)</option>
                    <option value="9926">9926 - Belgian VAT Number</option>
                    <option value="9955">9955 - Norwegian Organization Number</option>
                    <option value="9956">9956 - Swedish Organization Number</option>
                    <option value="9957">9957 - French VAT</option>
                    <option value="9958">9958 - German VAT</option>
                </datalist>
            </div>
            <small class="help-block">
                <?php echo _l('peppol_company_scheme_help'); ?>.
                <?php echo _l('peppol_identifier_format_help'); ?>
            </small>

            <hr />
        </div>

        <div class="form-group">
            <?php
            $providers = [
                'manual' => _l('peppol_provider_manual'),
                'peppol_service' => _l('peppol_provider_service'),
                'custom' => _l('peppol_provider_custom')
            ];
            ?>
            <label for="settings[peppol_active_provider]" class="control-label clearfix">
                <?php echo _l('peppol_active_provider'); ?>
            </label>
            <select class="selectpicker" name="settings[peppol_active_provider]" data-width="100%" required>
                <?php foreach ($providers as $value => $label) : ?>
                <option value="<?php echo $value; ?>"
                    <?php echo get_option('peppol_active_provider') == $value ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <?php
            $environments = [
                'sandbox' => _l('peppol_environment_sandbox'),
                'production' => _l('peppol_environment_production')
            ];
            ?>
            <label for="settings[peppol_environment]" class="control-label clearfix">
                <?php echo _l('peppol_environment'); ?>
            </label>
            <select class="selectpicker" name="settings[peppol_environment]" data-width="100%" required>
                <?php foreach ($environments as $value => $label) : ?>
                <option value="<?php echo $value; ?>"
                    <?php echo get_option('peppol_environment') == $value ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <hr />
            <small class="help-block"><?php echo _l('peppol_environment_help'); ?></small>
        </div>
    </div>

    <!-- Connection Settings Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_connection">
        <div class="form-group">
            <label for="settings[peppol_webhook_url]" class="control-label clearfix">
                <?php echo _l('peppol_webhook_url'); ?>
            </label>
            <input type="url" class="form-control" name="settings[peppol_webhook_url]"
                value="<?php echo get_option('peppol_webhook_url'); ?>">
            <hr />
            <small class="help-block"><?php echo _l('peppol_webhook_url_help'); ?></small>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-info" id="test-connection-btn">
                <i class="fa fa-plug"></i> <?php echo _l('peppol_test_connection'); ?>
            </button>
            <hr />
            <small class="help-block"><?php echo _l('peppol_test_connection_help'); ?></small>
        </div>
    </div>

    <!-- Automation Settings Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_automation">
        <div class="form-group">
            <div class="checkbox checkbox-primary">
                <input type="checkbox" id="peppol_test_mode" name="settings[peppol_test_mode]" value="1"
                    <?php echo get_option('peppol_test_mode') == '1' ? 'checked' : ''; ?>>
                <label for="peppol_test_mode"><?php echo _l('peppol_test_mode'); ?></label>
            </div>
            <hr />
            <small class="help-block"><?php echo _l('peppol_test_mode_help'); ?></small>
        </div>

        <div class="form-group">
            <div class="checkbox checkbox-primary">
                <input type="checkbox" id="peppol_auto_send" name="settings[peppol_auto_send]" value="1"
                    <?php echo get_option('peppol_auto_send') == '1' ? 'checked' : ''; ?>>
                <label for="peppol_auto_send"><?php echo _l('peppol_auto_send'); ?></label>
            </div>
            <hr />
            <small class="help-block"><?php echo _l('peppol_auto_send_help'); ?></small>
        </div>
    </div>
</div>

<style>
/* Enhanced styling for PEPPOL identifier inputs */
.peppol-identifier-group .input-group {
    width: 100%;
    display: flex;
}

.peppol-identifier-group .input-group-addon {
    background-color: #f5f5f5;
    border-color: #ddd;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    flex: 0 0 auto;
}

.peppol-scheme-input {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    text-align: center;
    flex: 0 0 80px;
    min-width: 80px;
    max-width: 100px;
}

.peppol-identifier-input {
    font-family: 'Courier New', monospace;
    flex: 1 1 auto;
}

.peppol-preview code {
    background-color: #f8f9fa;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 14px;
    color: #495057;
    border: 1px solid #e9ecef;
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    .peppol-scheme-input {
        flex: 0 0 70px;
        min-width: 70px;
        max-width: 80px;
    }
}
</style>

<script>
$(document).ready(function() {
    var schemeInput = $('.peppol-scheme-input');
    var identifierInput = $('.peppol-identifier-input');
    var preview = $('#peppol-identifier-preview');

    // Update live preview of PEPPOL identifier
    function updatePeppolPreview() {
        var scheme = schemeInput.val() || '0208';
        var identifier = identifierInput.val() || '0123456789';
        preview.text(scheme + ':' + identifier);
    }

    // Update preview on input changes
    schemeInput.on('input', updatePeppolPreview);
    identifierInput.on('input', updatePeppolPreview);

    // Enhanced placeholder handling
    schemeInput.on('focus', function() {
        $(this).attr('placeholder', '0208');
    });

    identifierInput.on('focus', function() {
        $(this).attr('placeholder', '<?php echo _l('peppol_enter_company_identifier'); ?>');
    });

    schemeInput.on('blur', function() {
        if (!$(this).val()) {
            $(this).attr('placeholder', '0208');
        }
    });

    identifierInput.on('blur', function() {
        if (!$(this).val()) {
            $(this).attr('placeholder', '0123456789');
        }
    });

    // Validate scheme format
    schemeInput.on('blur', function() {
        var value = $(this).val();
        if (value && !/^\d{4}$/.test(value)) {
            // If it's not a 4-digit code, check if it's a valid custom scheme
            if (value.length > 0 && !/^[0-9a-zA-Z-]{2,10}$/.test(value)) {
                alert_float('warning', '<?php echo _l('peppol_scheme_validation_error'); ?>');
            }
        }
        updatePeppolPreview();
    });

    // Show scheme description on selection
    schemeInput.on('change', function() {
        var selectedValue = $(this).val();
        var datalist = $('#peppol_scheme_suggestions');
        var selectedOption = datalist.find('option[value="' + selectedValue + '"]');

        if (selectedOption.length) {
            var description = selectedOption.text().split(' - ')[1];
            if (description) {
                alert_float('info', '<?php echo _l('peppol_selected_scheme'); ?>: ' + description,
                3000);
            }
        }
        updatePeppolPreview();
    });

    // Test Connection
    $('#test-connection-btn').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();

        btn.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('peppol_testing'); ?>...').prop(
            'disabled', true);

        $.ajax({
            url: admin_url + 'peppol/test_connection',
            type: 'POST',
            dataType: 'json',
            data: {
                [csrfData.token_name]: csrfData.hash
            },
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Connection test failed: ' + error);
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Environment change warning
    $('select[name="settings[peppol_environment]"]').on('change', function() {
        if ($(this).val() === 'production') {
            if (!confirm('<?php echo _l('peppol_production_warning'); ?>')) {
                $(this).val('sandbox');
                $(this).selectpicker('refresh');
            }
        }
    });

    // Provider change handling
    $('select[name="settings[peppol_active_provider]"]').on('change', function() {
        var provider = $(this).val();

        if (provider === 'manual') {
            alert_float('info', '<?php echo _l('peppol_manual_mode_notice'); ?>');
        }
    });
});
</script>