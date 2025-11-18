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

        // Has PEPPOL record - show status badge
        $badge_class = 'label-default';

        switch ($status) {
            case 'pending':
            case 'queued':
                $badge_class = 'label-warning';
                break;

            case 'delivered':
                $badge_class = 'label-default';
                break;

            case 'failed':
                $badge_class = 'label-danger';
                break;

            case 'received':
                $badge_class = 'label-info';
                break;

            case 'processed':
                $badge_class = 'label-primary';
                break;
        }

        $status_text = ucfirst($status);
        return '<span class="label ' . $badge_class . ' peppol-status-badge" 
                data-item-id="' . $item_id . '" data-status="' . $status . '">'
            . $status_text . '</span>';
    }
}