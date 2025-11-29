<?php
/**
 * PEPPOL Module - Unused Translation Keys Analysis
 * Generated: <?php echo date('Y-m-d H:i:s'); ?>
 * 
 * This file contains analysis of translation keys from the PEPPOL language file
 * classified by usage confidence level.
 */

// ==============================================
// CONFIRMED USED TRANSLATIONS
// ==============================================
// These translation keys are actively used in the codebase

$confirmed_used_translations = [
    // Core module translations
    'peppol' => 'Used in menu and general references',
    'peppol_settings' => 'Used in settings tab',
    'peppol_documents' => 'Used in documents management page',
    'peppol_logs' => 'Used in logs page and permissions',
    'peppol_documents_menu' => 'Used in admin menu',
    'peppol_logs_menu' => 'Used in admin menu',
    'peppol_settings_menu' => 'Used in admin menu',
    
    // Document management
    'peppol_document_type' => 'Used in documents table header',
    'peppol_provider_document_id' => 'Used in documents table header',
    'peppol_local_reference' => 'Used in documents table header',
    'peppol_total_amount' => 'Used in documents table header',
    'peppol_status' => 'Used in documents table and status displays',
    'peppol_provider' => 'Used in documents table header',
    'peppol_date' => 'Used in documents table header',
    'peppol_document_not_found' => 'Used in error handling',
    'peppol_document_details' => 'Used in document modal',
    'peppol_loading_document_details' => 'Used in loading state',
    
    // Status management
    'peppol_update_status' => 'Used in document view button',
    'peppol_update_document_status' => 'Used in modal title',
    'peppol_mark_status_help' => 'Used in status update form',
    'peppol_response_status' => 'Used in status form',
    'peppol_select_status' => 'Used in status dropdown',
    'peppol_status_acknowledged' => 'Used in status options',
    'peppol_status_in_process' => 'Used in status options',
    'peppol_status_accepted' => 'Used in status options',
    'peppol_status_rejected' => 'Used in status options',
    'peppol_status_paid' => 'Used in status options',
    'peppol_response_note' => 'Used in status form',
    'peppol_response_note_placeholder' => 'Used in status form',
    'peppol_effective_date' => 'Used in status form',
    'peppol_send_response' => 'Used in status form button',
    
    // Expense management
    'peppol_create_expense' => 'Used in expense creation buttons and modals',
    'peppol_view_expense' => 'Used in expense view button',
    'peppol_view_expense_record' => 'Used in expense tooltip',
    'peppol_auto_detected_help' => 'Used in expense form',
    'negative_for_credit_note' => 'Used in expense form',
    
    // Logs management
    'peppol_clear_logs' => 'Used in logs page button',
    'peppol_logs_cleared_successfully' => 'Used in logs clear success',
    'peppol_logs_clear_failed' => 'Used in logs clear error',
    'peppol_confirm_clear_logs' => 'Used in logs confirmation',
    'peppol_message' => 'Used in logs table header',
    'peppol_date_created' => 'Used in logs table header',
    
    // Download and UBL
    'peppol_download_ubl' => 'Used in download button tooltip',
    'peppol_generate_view_ubl' => 'Used in dropdown action',
    'peppol_generate_download_ubl' => 'Used in dropdown action',
    'peppol_resend' => 'Used in dropdown action',
    'peppol_not_available' => 'Used in dropdown when unavailable',
    'peppol_confirm_send' => 'Used in send confirmation',
    
    // Bulk operations
    'peppol_bulk_actions' => 'Used in bulk actions dropdown',
    'peppol_processing' => 'Used in bulk operation progress',
    'peppol_starting' => 'Used in bulk operation progress',
    'peppol_success' => 'Used in bulk operation results',
    'peppol_failed' => 'Used in bulk operation results',
    'peppol_completed' => 'Used in bulk operation completion',
    'peppol_operation_completed' => 'Used in bulk operation success',
    'peppol_operation_failed' => 'Used in bulk operation failure',
    'peppol_operation_timeout' => 'Used in bulk operation timeout',
    'peppol_operation_partial_success' => 'Used in partial success messages',
    'peppol_will_affect' => 'Used in bulk confirmation',
    'peppol_no_invoices_found' => 'Used when no documents found',
    'peppol_error_getting_stats' => 'Used in stats error',
    'peppol_preparing_download' => 'Used in download preparation',
    
    // Settings page
    'peppol_general_settings' => 'Used in settings tabs',
    'peppol_bank_information' => 'Used in settings tabs',
    'peppol_providers' => 'Used in settings tabs',
    'peppol_cron' => 'Used in settings tabs',
    'peppol_bank_account' => 'Used in bank settings',
    'peppol_bank_account_help' => 'Used in bank settings help',
    'peppol_bank_account_placeholder' => 'Used in bank settings placeholder',
    'peppol_bank_name' => 'Used in bank settings',
    'peppol_bank_name_help' => 'Used in bank settings help',
    'peppol_bank_name_placeholder' => 'Used in bank settings placeholder',
    'peppol_bank_bic' => 'Used in bank settings',
    'peppol_bank_bic_help' => 'Used in bank settings help',
    'peppol_bank_bic_placeholder' => 'Used in bank settings placeholder',
    'peppol_active_provider' => 'Used in provider selection',
    'peppol_active_provider_help' => 'Used in provider selection help',
    'peppol_test_connection' => 'Used in provider test button',
    'peppol_notification_lookup_time' => 'Used in cron settings',
    'peppol_notification_lookup_hours_help' => 'Used in cron settings help',
    'peppol_cron_interval' => 'Used in cron settings',
    'peppol_cron_interval_help' => 'Used in cron settings help',
    'peppol_last_notification_check' => 'Used in cron status',
    'peppol_next_notification_check' => 'Used in cron status',
    'peppol_calculating' => 'Used in cron calculation',
    'peppol_no_providers_registered' => 'Used when no providers available',
    
    // Error handling
    'peppol_access_denied' => 'Used in access control',
    'peppol_invalid_request_data' => 'Used in validation',
    'peppol_invalid_provider' => 'Used in provider validation',
    'peppol_provider_not_found' => 'Used in provider errors',
    'peppol_cannot_update_status_outbound' => 'Used in status update validation',
    
    // Statistics and expenses
    'peppol_invoice_documents' => 'Used in statistics cards',
    'peppol_credit_note_documents' => 'Used in statistics cards',
    'peppol_total_expenses_created' => 'Used in statistics cards',
    'peppol_total_expense_amount' => 'Used in statistics cards',
    'peppol_invoice_expenses' => 'Used in statistics cards',
    'peppol_invoice_expenses_subtitle' => 'Used in statistics cards',
    'peppol_credit_note_expenses' => 'Used in statistics cards',
    'peppol_credit_note_expenses_subtitle' => 'Used in statistics cards',
    
    // Filters and UI
    'peppol_all_document_types' => 'Used in document filters',
    'peppol_all_statuses' => 'Used in status filters',
    'peppol_all_providers' => 'Used in provider filters',
    'peppol_apply_filters' => 'Used in filter application',
    'peppol_clear_filters' => 'Used in filter clearing',
    
    // Auto creation settings
    'peppol_auto_create_invoice_expenses' => 'Used in expense settings',
    'peppol_auto_create_invoice_expenses_help' => 'Used in expense settings help',
    'peppol_auto_create_credit_note_expenses' => 'Used in expense settings',
    'peppol_auto_create_credit_note_expenses_help' => 'Used in expense settings help',
];

// ==============================================
// LIKELY UNUSED TRANSLATIONS
// ==============================================
// These translation keys were not found in the codebase and are likely unused

$likely_unused_translations = [
    // Document sections not found in current implementation
    'peppol_document_preview' => 'Not found in search results',
    'peppol_attachments' => 'Not found in search results',
    'peppol_metadata' => 'Not found in search results',
    'peppol_transmission_info' => 'Not found in search results',
    'peppol_status_info' => 'Not found in search results',
    
    // Status values not found in current implementation
    'peppol_status_pending' => 'Used in filter dropdown but may be legacy',
    'peppol_status_sent' => 'Used in filter dropdown but may be legacy',
    'peppol_status_delivered' => 'Used in filter dropdown but may be legacy',
    'peppol_status_failed' => 'Used in filter dropdown but may be legacy',
    'peppol_status_received' => 'Used in filter dropdown but may be legacy',
    'peppol_status_rejected_inbound' => 'Used in filter dropdown but may be legacy',
    
    // UBL and technical terms not found
    'peppol_ubl_document' => 'Not found in search results',
    'peppol_xml_content' => 'Not found in search results',
    'peppol_transmission_id' => 'Not found in search results',
    'peppol_provider_metadata' => 'Not found in search results',
    
    // Advanced features not implemented
    'peppol_advanced_settings' => 'Not found in search results',
    'peppol_debug_mode' => 'Not found in search results',
    'peppol_test_mode' => 'Not found in search results',
    'peppol_sandbox_mode' => 'Not found in search results',
    
    // Clarifications feature
    'peppol_clarifications' => 'Found in status form but may not be fully implemented',
    'peppol_clarifications_optional' => 'Found in status form but may not be fully implemented',
    'peppol_add_clarification' => 'Found in status form but may not be fully implemented',
    'peppol_clarification_type' => 'Found in status form but may not be fully implemented',
    'peppol_clarification_code' => 'Found in status form but may not be fully implemented',
    'peppol_clarification_message' => 'Found in status form but may not be fully implemented',
    'peppol_clarification_message_placeholder' => 'Found in status form but may not be fully implemented',
    'peppol_select_type' => 'Found in status form but may not be fully implemented',
    'peppol_select_code' => 'Found in status form but may not be fully implemented',
    
    // Legacy or unused validation messages
    'peppol_validation_error' => 'Not found in search results',
    'peppol_connection_error' => 'Not found in search results',
    'peppol_timeout_error' => 'Not found in search results',
    'peppol_unknown_error' => 'Not found in search results',
    
    // Unused statistics or features
    'peppol_statistics' => 'Not found in search results',
    'peppol_reports' => 'Not found in search results',
    'peppol_export' => 'Not found in search results',
    'peppol_import' => 'Not found in search results',
];

// ==============================================
// UNCERTAIN TRANSLATIONS
// ==============================================
// These translations might be used in dynamic contexts or special cases

$uncertain_translations = [
    // Dynamic status translations (peppol_status_* pattern)
    // These might be generated dynamically based on status codes from API
    'peppol_status_queued' => 'Might be used for QUEUED status display',
    'peppol_status_technical_acceptance' => 'Might be used for TECHNICAL_ACCEPTANCE status',
    'peppol_status_buyer_acknowledge' => 'Might be used for BUYER_ACKNOWLEDGE status',
    'peppol_status_under_query' => 'Might be used for UNDER_QUERY status',
    'peppol_status_conditionally_accepted' => 'Might be used for CONDITIONALLY_ACCEPTED status',
    'peppol_status_partially_paid' => 'Might be used for PARTIALLY_PAID status',
    'peppol_status_fully_paid' => 'Might be used for FULLY_PAID status',
    'peppol_status_send_failed' => 'Might be used for SEND_FAILED status',
    
    // Process notifications and background tasks
    'peppol_process_notifications' => 'Used in manual notification processing button',
    'peppol_unknown_action' => 'Used as fallback in bulk operations',
    
    // Vendor and contact information (might be used in UBL generation)
    'vendor_identifier' => 'Used in expense form - standard Perfex translation',
    
    // Standard Perfex translations that might be reused
    'auto_detected' => 'Standard Perfex translation reused in forms',
    'auto_detected_information' => 'Standard Perfex translation reused in forms',
    
    // Expense-related standard translations
    'expense_category' => 'Standard Perfex translation',
    'expense_name' => 'Standard Perfex translation',
    'expense_date' => 'Standard Perfex translation',
    'expense_note' => 'Standard Perfex translation',
    'expense_details' => 'Standard Perfex translation',
    'payment_mode' => 'Standard Perfex translation',
    'reference_no' => 'Standard Perfex translation',
    'amount' => 'Standard Perfex translation',
    'tax_1' => 'Standard Perfex translation',
    'tax_2' => 'Standard Perfex translation',
    'tax_rate' => 'Standard Perfex translation',
];

// ==============================================
// RECOMMENDATIONS
// ==============================================

echo "/**\n";
echo " * ANALYSIS SUMMARY\n";
echo " * ================\n";
echo " * \n";
echo " * CONFIRMED USED: " . count($confirmed_used_translations) . " translation keys\n";
echo " * LIKELY UNUSED: " . count($likely_unused_translations) . " translation keys\n";
echo " * UNCERTAIN: " . count($uncertain_translations) . " translation keys\n";
echo " * \n";
echo " * RECOMMENDATIONS:\n";
echo " * 1. Keep all 'CONFIRMED USED' translations - these are actively referenced\n";
echo " * 2. Review 'LIKELY UNUSED' translations - these can probably be removed\n";
echo " * 3. Test 'UNCERTAIN' translations - verify if they're used in dynamic contexts\n";
echo " * 4. Focus removal efforts on status-related legacy translations if API codes changed\n";
echo " * 5. Clarifications feature appears partially implemented - review if needed\n";
echo " */\n";

// Print unused translations for easy copying
echo "\n// LIKELY UNUSED TRANSLATION KEYS FOR REVIEW:\n";
foreach ($likely_unused_translations as $key => $note) {
    echo "// '$key' => '$note',\n";
}

echo "\n// END OF ANALYSIS\n";