<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Nav tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active">
        <a href="#peppol_general" aria-controls="peppol_general" role="tab" data-toggle="tab">
            <?php echo _l('peppol_general_settings'); ?>
        </a>
    </li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <!-- General Settings Tab -->
    <div role="tabpanel" class="tab-pane active" id="peppol_general">
        <?php
        // Load the PEPPOL input helper
        require_once(__DIR__ . '/../../helpers/peppol_input_helper.php');

        // Use the reusable component for settings
        echo render_peppol_settings_input(
            get_option('peppol_company_scheme') ?: '0208',
            get_option('peppol_company_identifier') ?: '',
        );
        ?>
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
document.addEventListener("DOMContentLoaded", function() {
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

});
</script>