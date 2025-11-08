<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- General Settings Tab Content -->
<div class="row">
    <div class="col-md-6">
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?= _l('peppol_company_identifier_help'); ?>"></i>
        <?php echo render_input('settings[peppol_company_identifier]', _l('peppol_company_identifier'), get_option('peppol_company_identifier'), 'text', [
            'data-toggle' => 'tooltip',
            'title' => _l('peppol_company_identifier_help')
        ]); ?>
    </div>

    <div class="col-md-6">
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?= _l('peppol_company_scheme_help'); ?>"></i>
        <?php
        echo render_select(
            'settings[peppol_company_scheme]',
            [
                ['id' => '0088', 'name' => '0088 - GLN'],
                ['id' => '0060', 'name' => '0060 - DUNS'],
                ['id' => '0007', 'name' => '0007 - Swedish organization number'],
                ['id' => '0037', 'name' => '0037 - LY-tunnus'],
                ['id' => '0096', 'name' => '0096 - GTIN'],
                ['id' => '0135', 'name' => '0135 - SIA Object Identifier'],
                ['id' => '0183', 'name' => '0183 - Corporate number']
            ],
            ['id', 'name'],
            _l('peppol_company_scheme'),
            get_option('peppol_company_scheme', '0088'),
            ['data-toggle' => 'tooltip', 'title' => _l('peppol_company_scheme_help')]
        );
        ?>
    </div>
</div>

<div class="tw-mt-4 tw-mb-4">
    <hr />
</div>

<div class="row">
    <div class="col-md-6">
        <?php echo render_yes_no_option('peppol_auto_send_enabled', _l('peppol_auto_send_enabled'), _l('peppol_auto_send_help')); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_yes_no_option('peppol_auto_process_received', _l('peppol_auto_process_received'), _l('peppol_auto_process_help')); ?>
    </div>
</div>