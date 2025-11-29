<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="scroller arrow-left tw-mt-px"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right tw-mt-px"><i class="fa fa-angle-right"></i></div>
    <!-- Nav tabs -->
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <li role="presentation" class="active">
                <a href="#peppol_general" aria-controls="peppol_general" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_general_settings'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_bank" aria-controls="peppol_bank" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_bank_information'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_providers" aria-controls="peppol_providers" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_providers'); ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#peppol_notifications" aria-controls="peppol_notifications" role="tab" data-toggle="tab">
                    <?php echo _l('peppol_cron'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Tab panes -->
<div class="tab-content mtop30">
    <!-- General Settings Tab -->
    <div role="tabpanel" class="tab-pane active" id="peppol_general">
        <?php require_once(__DIR__ . '/settings/general.php'); ?>
    </div>

    <!-- Bank Information Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_bank">
        <?php require_once(__DIR__ . '/settings/bank.php'); ?>
    </div>

    <!-- Providers Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_providers">
        <?php require_once(__DIR__ . '/settings/providers.php'); ?>
    </div>

    <!-- Notifications Tab -->
    <div role="tabpanel" class="tab-pane" id="peppol_notifications">
        <?php require_once(__DIR__ . '/settings/notifications.php'); ?>
    </div>
</div>

<?php require_once(__DIR__ . '/settings/scripts.php'); ?>