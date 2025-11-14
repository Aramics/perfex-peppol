<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <li role="presentation" class="active">
                <a href="#general_settings" aria-controls="general_settings" role="tab" data-toggle="tab">
                    <?php echo _l('general'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#provider_settings" aria-controls="provider_settings" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_provider_settings'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#test_settings" aria-controls="test_settings" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_tests'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content mtop30">
    <!-- General Settings Tab -->
    <div role="tabpanel" class="tab-pane active" id="general_settings">
        <?php $this->load->view(PEPPOL_MODULE_NAME . '/settings/general'); ?>
    </div>

    <!-- Provider Settings Tab -->
    <div role="tabpanel" class="tab-pane" id="provider_settings">
        <?php $this->load->view(PEPPOL_MODULE_NAME . '/settings/providers'); ?>
    </div>

    <!-- Tests Tab -->
    <div role="tabpanel" class="tab-pane" id="test_settings">
        <?php $this->load->view(PEPPOL_MODULE_NAME . '/settings/tests'); ?>
    </div>
</div>

<script>
// Shared JavaScript for all PEPPOL settings tabs
document.addEventListener("DOMContentLoaded", function() {
    // Set provider configuration data for the PEPPOL module
    if (typeof setPeppolProviders === 'function') {
        setPeppolProviders(<?php echo json_encode($providers); ?>);
    } else {
        // Fallback: store in global variable if function not loaded yet
        window.peppolProviders = <?php echo json_encode($providers); ?>;
    }
});
</script>