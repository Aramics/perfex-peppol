<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

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