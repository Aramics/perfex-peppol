<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Ademico Provider Settings - OAuth2 Only -->
<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_ademico_oauth2_client_identifier]', _l('peppol_ademico_client_id'), get_option('peppol_ademico_oauth2_client_identifier'), 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_ademico_client_id_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_ademico_oauth2_client_secret]', _l('peppol_ademico_client_secret'), get_option('peppol_ademico_oauth2_client_secret'), 'password', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_ademico_client_secret_help')
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
        <?php echo render_input('settings[peppol_ademico_oauth2_client_identifier_test]', _l('peppol_ademico_client_id_test'), get_option('peppol_ademico_oauth2_client_identifier_test'), 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_ademico_client_id_test_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_ademico_oauth2_client_secret_test]', _l('peppol_ademico_client_secret_test'), get_option('peppol_ademico_oauth2_client_secret_test'), 'password', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_ademico_client_secret_test_help')
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