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
        $badge_class =  $CI->peppol_model->get_status_display($status ?? '')['label'] ?? 'label-default';

        $status_text = ucfirst($status);
        return '<span class="label ' . $badge_class . ' peppol-status-badge" 
                data-item-id="' . $item_id . '" data-status="' . $status . '">'
            . $status_text . '</span>';
    }
}

if (!function_exists('peppol_process_notifications')) {
    /**
     * Run every 15 minutes via cron
     */
    function peppol_process_notifications()
    {
        $CI = &get_instance();

        // Option key to store last execution timestamp
        $option_key = 'peppol_cron_last_run';
        $last_run = (int)get_option($option_key);

        // Current timestamp
        $now = time();

        // Only run if 15 minutes have passed since last run
        if ($last_run && ($now - $last_run) < 15 * 60) {
            return;
        }

        // Update last run timestamp immediately to avoid duplicate execution
        update_option($option_key, $now);

        try {
            $CI->peppol_service->process_notifications();
        } catch (\Throwable $th) {
            log_message('error', 'Error in my_custom_cron_job: ' . $th->getMessage());
        }
    }
}