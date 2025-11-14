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
        $active_provider = get_option('peppol_active_provider');
        
        return !empty($company_identifier) && !empty($active_provider);
    }
}

/**
 * Render PEPPOL status column for tables
 */
if (!function_exists('render_peppol_status_column')) {
    function render_peppol_status_column($status = null, $item_id, $peppol_item = null)
    {
        if (!staff_can('view', 'peppol') || !is_peppol_configured()) {
            return '<span class="text-muted">-</span>';
        }

        if ($status === null) {
            // No PEPPOL record - show not sent status
            return '<span class="text-muted">' . _l('peppol_not_sent') . '</span>';
        }

        // Has PEPPOL record - show status badge
        $badge_class = 'label-default';
        $icon = 'fa-circle-o';

        switch ($status) {
            case 'pending':
            case 'queued':
                $badge_class = 'label-warning';
                $icon = 'fa-clock-o';
                break;
            
            case 'sent':
            case 'delivered':
                $badge_class = 'label-success';
                $icon = 'fa-check';
                break;
            
            case 'failed':
                $badge_class = 'label-danger';
                $icon = 'fa-times';
                break;
            
            case 'received':
                $badge_class = 'label-info';
                $icon = 'fa-download';
                break;
            
            case 'processed':
                $badge_class = 'label-primary';
                $icon = 'fa-check-circle';
                break;
        }

        $status_text = ucfirst($status);
        return '<span class="label ' . $badge_class . ' peppol-status-badge" 
                data-item-id="' . $item_id . '" data-status="' . $status . '">
                <i class="fa ' . $icon . '"></i> ' . $status_text . '
                </span>';
    }
}