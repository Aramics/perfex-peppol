<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Helper Functions
 */

/**
 * Check if PEPPOL is configured
 */
if (!function_exists('is_peppol_configured')) {
    function is_peppol_configured()
    {
        $company_identifier = get_option('peppol_company_identifier');

        return !empty($company_identifier);
    }
}

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