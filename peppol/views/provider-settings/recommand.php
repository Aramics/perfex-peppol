<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Recommand Provider Settings -->
<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_recommand_api_key]', _l('peppol_recommand_api_key'), get_option('peppol_recommand_api_key'), 'password', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_recommand_api_key_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_recommand_company_id]', _l('peppol_recommand_company_id'), get_option('peppol_recommand_company_id'), 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_recommand_company_id_help')
        ]); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_recommand_endpoint_url]', _l('peppol_recommand_endpoint_url'), get_option('peppol_recommand_endpoint_url'), 'text', [
            'placeholder' => 'https://peppol.recommand.eu/api',
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_recommand_endpoint_url_help')
        ]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[peppol_recommand_sandbox_endpoint]', _l('peppol_recommand_sandbox_endpoint'), get_option('peppol_recommand_sandbox_endpoint'), 'text', [
            'placeholder' => 'https://sandbox-peppol.recommand.eu/api',
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_recommand_sandbox_endpoint_help')
        ]); ?>
    </div>
</div>