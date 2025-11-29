<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Get current notification settings
$lookup_hours = get_option('peppol_notification_lookup_hours') ?: '72';
$cron_interval = get_option('peppol_cron_interval') ?: '5';
?>
<div class="row">
    <div class="col-md-6">
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?php echo e(_l('peppol_notification_lookup_hours_help')); ?>"></i>
        <?php echo render_input('settings[peppol_notification_lookup_hours]', _l('peppol_notification_lookup_time'), $lookup_hours, 'number', [
            'step' => '0.01',
            'placeholder' => '72',
            'id' => 'peppol-notification-lookup-hours'
        ]); ?>
    </div>

    <div class="col-md-6">
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?php echo e(_l('peppol_cron_interval_help')); ?>"></i>
        <?php echo render_input('settings[peppol_cron_interval]', _l('peppol_cron_interval'), $cron_interval, 'number', [
            'step' => '1',
            'placeholder' => '5',
            'id' => 'peppol-cron-interval'
        ]); ?>
    </div>
</div>

<hr />

<div id="peppol-notification-status">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><?php echo _l('peppol_last_notification_check'); ?></label>
                <div class="form-control-static" id="last-notification-check">
                    <?php
                    $last_check = get_option('peppol_last_notification_check');
                    echo $last_check ? date('Y-m-d H:i:s', strtotime($last_check)) : _l('never');
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?php echo _l('peppol_next_notification_check'); ?></label>
                <div class="form-control-static" id="next-notification-check">
                    <span id="calculated-next-check"><?php echo _l('peppol_calculating'); ?>...</span>
                </div>
            </div>
        </div>
    </div>
</div>