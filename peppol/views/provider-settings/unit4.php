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