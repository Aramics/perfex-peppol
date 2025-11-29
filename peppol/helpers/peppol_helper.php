<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Render PEPPOL status column for tables
 */
if (!function_exists('render_peppol_status_column')) {
    function render_peppol_status_column($item_id, $status = null)
    {
        if ($status === null) {
            // No PEPPOL record - show not sent status
            return '<span class="text-muted">' . _l('peppol_not_sent') . '</span>';
        }

        $CI = &get_instance();

        // Has PEPPOL record - show status badge
        $badge_class =  $CI->peppol_model->get_status_display($status ?? '')['color'] ?? 'label-default';

        $status_text = ucfirst($status);
        return '<span class="label ' . $badge_class . ' peppol-status-badge" 
                data-item-id="' . $item_id . '" data-status="' . $status . '">'
            . $status_text . '</span>';
    }
}

if (!function_exists('peppol_process_notifications')) {
    /**
     * Process PEPPOL notifications via cron
     * Frequency is configurable in settings (default: every 5 minutes)
     * @param bool $manual If the processing is called by human
     */
    function peppol_process_notifications($manual = false)
    {
        $CI = &get_instance();

        // Option key to store last execution timestamp
        $option_key = 'peppol_cron_last_run';
        $last_run = (int)get_option($option_key);

        // Current timestamp
        $now = time();

        if (!$manual) {
            // Get cron interval from settings (default 5 minutes)
            $cron_interval = (int)(get_option('peppol_cron_interval') ?: 5);
            $interval_seconds = $cron_interval * 60;

            // Only run if configured interval has passed since last run
            if ($last_run && ($now - $last_run) < $interval_seconds) {
                return;
            }
        }

        // Update last run timestamp immediately to avoid duplicate execution
        update_option($option_key, $now);

        // Update last notification check timestamp
        update_option('peppol_last_notification_check', date('Y-m-d H:i:s', $now));

        try {
            $CI->peppol_service->process_notifications();
        } catch (\Throwable $th) {
            log_message('error', 'Error in peppol_process_notifications: ' . $th->getMessage());
        }
    }
}