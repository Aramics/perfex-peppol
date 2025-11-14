<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Unit4 Provider Settings -->
<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_unit4_username]', _l('peppol_unit4_username'), get_option('peppol_unit4_username'), 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_unit4_username_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_unit4_password]', _l('peppol_unit4_password'), get_option('peppol_unit4_password'), 'password', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_unit4_password_help')
        ]); ?>
    </div>
</div>

<div class="tw-mt-4 tw-mb-4">
    <hr />
</div>

<!-- Test Credentials -->
<h5><?php echo _l('peppol_test_credentials'); ?></h5>
<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_unit4_username_test]', _l('peppol_unit4_username_test'), get_option('peppol_unit4_username_test'), 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_unit4_username_test_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_unit4_password_test]', _l('peppol_unit4_password_test'), get_option('peppol_unit4_password_test'), 'password', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_unit4_password_test_help')
        ]); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            <strong><?php echo _l('peppol_test_credentials_note'); ?></strong><br>
            <?php echo _l('peppol_test_credentials_help'); ?>
        </div>
    </div>
</div>

<div class="tw-mt-4 tw-mb-4">
    <hr />
</div>

<h5><?php echo _l('peppol_endpoint_configuration'); ?></h5>
<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_unit4_endpoint_url]', _l('peppol_unit4_endpoint_url'), get_option('peppol_unit4_endpoint_url'), 'text', [
            'placeholder' => 'https://ap.unit4.com',
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_unit4_endpoint_url_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_unit4_sandbox_endpoint]', _l('peppol_unit4_sandbox_endpoint'), get_option('peppol_unit4_sandbox_endpoint'), 'text', [
            'placeholder' => 'https://test-ap.unit4.com',
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_unit4_sandbox_endpoint_help')
        ]); ?>
    </div>
</div>