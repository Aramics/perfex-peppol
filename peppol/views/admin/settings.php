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