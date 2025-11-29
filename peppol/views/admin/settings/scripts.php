<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

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
        var isValid = PeppolBankValidator.validateIBAN(iban) || PeppolBankValidator
            .validateAccountNumber(iban);

        if (iban.length > 0) {
            if (isValid) {
                $(this).closest('.form-group').removeClass('has-error').addClass('has-success');
            } else {
                $(this).closest('.form-group').removeClass('has-success').addClass('has-error');
            }
        } else {
            $(this).closest('.form-group').removeClass('has-error has-success');
        }
    });

    $('#peppol-bank-bic').on('input', function() {
        var bic = $(this).val().toUpperCase();
        var isValid = PeppolBankValidator.validateBIC(bic);

        if (bic.length > 0) {
            if (isValid) {
                $(this).closest('.form-group').removeClass('has-error').addClass('has-success');
            } else {
                $(this).closest('.form-group').removeClass('has-success').addClass('has-error');
            }
        } else {
            $(this).closest('.form-group').removeClass('has-error has-success');
        }
    });

    // Notification settings calculations
    function calculateNextNotificationCheck() {
        var cronInterval = $('#peppol-cron-interval').val() || 5;
        var lastCheck = '<?php echo get_option('peppol_last_notification_check'); ?>';

        if (lastCheck) {
            var nextCheck = new Date(lastCheck);
            nextCheck.setMinutes(nextCheck.getMinutes() + parseInt(cronInterval));
            // Format to match PHP date('Y-m-d H:i:s') format
            var formatted = nextCheck.getFullYear() + '-' +
                ('0' + (nextCheck.getMonth() + 1)).slice(-2) + '-' +
                ('0' + nextCheck.getDate()).slice(-2) + ' ' +
                ('0' + nextCheck.getHours()).slice(-2) + ':' +
                ('0' + nextCheck.getMinutes()).slice(-2) + ':' +
                ('0' + nextCheck.getSeconds()).slice(-2);
            $('#calculated-next-check').text(formatted);
        } else {
            $('#calculated-next-check').text('<?php echo _l('when_cron_runs'); ?>');
        }
    }

    // Update next check time when cron interval changes
    $('#peppol-cron-interval').change(calculateNextNotificationCheck);

    // Calculate on page load
    calculateNextNotificationCheck();
});

/**
 * Banking Validation Utility
 * Provides validation for IBAN, BIC/SWIFT and Basic Account Numbers
 */
var PeppolBankValidator = (function() {

    // IBAN length by country
    var IBAN_COUNTRY_LENGTHS = {
        'AD': 24, 'AE': 23, 'AL': 28, 'AT': 20, 'AZ': 28, 'BA': 20, 'BE': 16, 'BG': 22,
        'BH': 22, 'BR': 29, 'BY': 28, 'CH': 21, 'CR': 22, 'CY': 28, 'CZ': 24, 'DE': 22,
        'DK': 18, 'DO': 28, 'EE': 20, 'EG': 29, 'ES': 24, 'FI': 18, 'FO': 18, 'FR': 27,
        'GB': 22, 'GE': 22, 'GI': 23, 'GL': 18, 'GR': 27, 'GT': 28, 'HR': 21, 'HU': 28,
        'IE': 22, 'IL': 23, 'IS': 26, 'IT': 27, 'JO': 30, 'KW': 30, 'KZ': 20, 'LB': 28,
        'LC': 32, 'LI': 21, 'LT': 20, 'LU': 20, 'LV': 21, 'MC': 27, 'MD': 24, 'ME': 22,
        'MK': 19, 'MR': 27, 'MT': 31, 'MU': 30, 'NL': 18, 'NO': 15, 'PK': 24, 'PL': 28,
        'PS': 29, 'PT': 25, 'QA': 29, 'RO': 24, 'RS': 22, 'SA': 24, 'SE': 24, 'SI': 19,
        'SK': 24, 'SM': 27, 'TN': 24, 'TR': 26, 'UA': 29, 'VG': 24, 'XK': 20
    };

    return {
        /**
         * Validate an IBAN
         */
        validateIBAN: function(iban) {
            if (!iban || iban.length < 4) return false;

            iban = iban.replace(/\s+/g, '').toUpperCase();

            var regex = /^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/;
            if (!regex.test(iban)) return false;

            var country = iban.substring(0, 2);
            var expectedLength = IBAN_COUNTRY_LENGTHS[country];

            if (!expectedLength || iban.length !== expectedLength) return false;

            return true; // (Optional: mod97 check can be added for full IBAN checksum)
        },

        /**
         * Validate basic (non-IBAN) account numbers
         */
        validateAccountNumber: function(account) {
            if (!account) return false;

            var cleaned = account.replace(/[\s-]/g, '');
            if (cleaned.length < 4) return false;

            var regex = /^[0-9\-\s]+$/;
            return regex.test(account);
        },

        /**
         * Validate a BIC / SWIFT code
         */
        validateBIC: function(bic) {
            if (!bic) return false;

            bic = bic.toUpperCase();

            var regex = /^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/;
            return regex.test(bic) && (bic.length === 8 || bic.length === 11);
        }
    };

})();
</script>