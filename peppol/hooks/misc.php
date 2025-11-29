<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** 
 * Let make a quick processsing of notification after status update to document.
 * It give user realtime fealing.
 */
hooks()->add_action('peppol_after_document_status_updated', function ($document, $result) {
    try {
        // Get for the last 30 seconds and process.
        get_instance()->peppol_service->process_notifications((1 / 2) / 60);
    } catch (\Throwable $th) {
        log_message('error', 'Error in peppol_process_notifications: ' . $th->getMessage());
    }
}, 10, 2);