<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Add visibility toggle button to credit note attachments.
 * This is only a patch to the CRM and should be removed when toggle 
 * is natively added to credit notes as in invoices.
 */
hooks()->add_action('before_credit_note_preview_more_menu_button', function ($credit_note) {
    $attachments = [];
    foreach ($credit_note->attachments as $key => $attachment) {
        $attachments[$attachment['id']] = $attachment;
    }
    get_instance()->load->view(
        PEPPOL_MODULE_NAME . '/scripts/add_credit_note_attachment_visibility_toggle',
        [
            'credit_note' => $credit_note,
            'attachments' => $attachments
        ]
    );
});