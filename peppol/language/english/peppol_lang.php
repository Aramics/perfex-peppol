<?php

defined('BASEPATH') or exit('No direct script access allowed');

// General
$lang['peppol'] = 'PEPPOL';
$lang['peppol_status'] = 'PEPPOL Status';
$lang['peppol_not_sent'] = 'Not sent';
$lang['peppol_invoices'] = 'PEPPOL Invoices';

// Status labels
$lang['peppol_status_pending'] = 'Pending';
$lang['peppol_status_queued'] = 'Queued';
$lang['peppol_status_sending'] = 'Sending';
$lang['peppol_status_sent'] = 'Sent';
$lang['peppol_status_delivered'] = 'Delivered';
$lang['peppol_status_failed'] = 'Failed';
$lang['peppol_status_received'] = 'Received';
$lang['peppol_status_processed'] = 'Processed';

// Actions
$lang['peppol_send_invoice'] = 'Send via PEPPOL';
$lang['peppol_resend'] = 'Resend';
$lang['peppol_view_ubl'] = 'View UBL';
$lang['peppol_download_ubl'] = 'Download UBL';

// Bulk Actions
$lang['peppol_bulk_actions'] = 'PEPPOL Actions';
$lang['peppol_send_all_unsent'] = 'Send all unsent invoices';
$lang['peppol_retry_all_failed'] = 'Retry all failed invoices';
$lang['peppol_download_all_sent'] = 'Download all sent UBL files';

// Messages
$lang['peppol_confirm_send'] = 'Are you sure you want to send this invoice via PEPPOL?';
$lang['peppol_confirm_resend'] = 'Are you sure you want to resend this invoice?';
$lang['peppol_will_affect'] = 'This will affect';
$lang['peppol_invoices'] = 'invoice(s)';
$lang['continue'] = 'Continue';
$lang['peppol_no_invoices_found'] = 'No invoices found matching the selected criteria.';
$lang['peppol_error_getting_stats'] = 'Error getting statistics for this action.';
$lang['peppol_preparing_invoices'] = 'Preparing invoices...';
$lang['peppol_processing'] = 'Processing...';
$lang['peppol_starting'] = 'Starting...';
$lang['peppol_completed'] = 'Completed';
$lang['peppol_success'] = 'Success';
$lang['peppol_failed'] = 'Failed';
$lang['cancel'] = 'Cancel';
$lang['peppol_operation_completed'] = 'Operation completed successfully';
$lang['peppol_operation_failed'] = 'Operation failed';
$lang['peppol_operation_partial_success'] = 'Completed with %d successes and %d errors';
$lang['peppol_operation_timeout'] = 'Operation timed out. Some invoices may have been processed.';
$lang['peppol_preparing_download'] = 'Preparing download...';
$lang['peppol_sending_invoice'] = 'Sending invoice via PEPPOL...';
$lang['peppol_sending_credit_note'] = 'Sending credit note via PEPPOL...';
$lang['peppol_preparing_invoices'] = 'Preparing invoices...';
$lang['peppol_preparing_credit_notes'] = 'Preparing credit notes...';
$lang['peppol_invoice_sent_successfully'] = 'Invoice sent successfully via PEPPOL';
$lang['peppol_invoice_send_failed'] = 'Failed to send invoice via PEPPOL';

// Client related
$lang['peppol_client_no_identifier'] = 'Client does not have a PEPPOL identifier configured';
$lang['peppol_add_client_identifier'] = 'Add PEPPOL Identifier';
$lang['peppol_invoice_ready_to_send'] = 'Invoice is ready to be sent via PEPPOL';
$lang['peppol_document_id'] = 'PEPPOL Document ID';
$lang['peppol_sent_at'] = 'Sent at';

// Settings and Configuration
$lang['peppol_not_configured'] = 'PEPPOL is not configured yet';
$lang['peppol_company_identifier'] = 'Company PEPPOL Identifier';
$lang['peppol_company_scheme'] = 'Company PEPPOL Scheme';
$lang['peppol_active_provider'] = 'Active PEPPOL Provider';
$lang['peppol_environment'] = 'Environment';
$lang['peppol_provider_settings'] = 'Provider Settings';
$lang['peppol_test_connection'] = 'Test Connection';
$lang['peppol_test_mode'] = 'Test Mode';
$lang['peppol_auto_send'] = 'Auto Send Documents';
$lang['peppol_webhook_url'] = 'Webhook URL';
$lang['peppol_webhook_secret'] = 'Webhook Secret';
$lang['peppol_field_required'] = 'Field %s is required';
$lang['peppol_configuration_incomplete'] = 'PEPPOL configuration is incomplete';
$lang['peppol_connection_test_success'] = 'Connection test successful';
$lang['peppol_connection_test_failed'] = 'Connection test failed';
$lang['peppol_settings'] = 'PEPPOL Settings';
$lang['peppol_general_settings'] = 'General Settings';
$lang['peppol_connection_settings'] = 'Connection Settings';
$lang['peppol_automation_settings'] = 'Automation Settings';
$lang['save_settings'] = 'Save Settings';

// Credit Notes
$lang['peppol_credit_notes'] = 'PEPPOL Credit Notes';
$lang['peppol_send_credit_note'] = 'Send via PEPPOL';
$lang['peppol_credit_note_sent_successfully'] = 'Credit note sent successfully via PEPPOL';
$lang['peppol_credit_note_send_failed'] = 'Failed to send credit note via PEPPOL';
$lang['peppol_send_all_unsent_credit_notes'] = 'Send all unsent credit notes';
$lang['peppol_retry_all_failed_credit_notes'] = 'Retry all failed credit notes';
$lang['peppol_download_all_sent_credit_note_ubl'] = 'Download all sent credit note UBL files';

// Help texts
$lang['peppol_company_identifier_help'] = 'Your company\'s PEPPOL participant identifier';
$lang['peppol_company_scheme_help'] = 'The scheme used for your PEPPOL identifier (e.g., 0208)';
$lang['peppol_environment_help'] = 'Choose Sandbox for testing or Live for production use';

// Access and permissions
$lang['peppol_access_denied'] = 'Access denied';
$lang['peppol_no_permission'] = 'You do not have permission to perform this action';

// Error messages
$lang['peppol_invoice_not_found'] = 'Invoice not found';
$lang['peppol_credit_note_not_found'] = 'Credit note not found';
$lang['peppol_invoice_already_processed'] = 'Invoice already processed via PEPPOL';
$lang['peppol_credit_note_already_processed'] = 'Credit note already processed via PEPPOL';

// Activity messages
$lang['peppol_invoice_sent_activity'] = 'Invoice sent via PEPPOL';
$lang['peppol_credit_note_sent_activity'] = 'Credit note sent via PEPPOL';

// Error format messages
$lang['peppol_invoice_error_format'] = 'Invoice #%d: %s';
$lang['peppol_credit_note_error_format'] = 'Credit note #%d: %s';
$lang['peppol_unknown_action'] = 'Unknown action';

// Settings page translations
$lang['peppol_identifier_format_help'] = 'Format: <code>scheme:identifier</code> (e.g., <code>0208:0123456789</code>). Start typing in the scheme field to see suggestions.';
$lang['peppol_provider_manual'] = 'Manual (Test/Development)';
$lang['peppol_provider_service'] = 'PEPPOL Service Provider';
$lang['peppol_provider_custom'] = 'Custom API';
$lang['peppol_environment_sandbox'] = 'Sandbox (Testing)';
$lang['peppol_environment_production'] = 'Production (Live)';
$lang['peppol_webhook_url_help'] = 'URL for receiving status updates from PEPPOL provider';
$lang['peppol_test_connection_help'] = 'Test the connection with current settings';
$lang['peppol_test_mode_help'] = 'Enable test mode for development and testing';
$lang['peppol_auto_send_help'] = 'Automatically send invoices via PEPPOL when marked as sent';
$lang['peppol_enter_company_identifier'] = 'Enter your company identifier';
$lang['peppol_scheme_validation_error'] = 'Scheme identifier should be a 4-digit code (e.g. 0208) or a valid custom identifier';
$lang['peppol_selected_scheme'] = 'Selected scheme';
$lang['peppol_testing'] = 'Testing';
$lang['peppol_production_warning'] = 'Are you sure you want to switch to PRODUCTION environment? This will send real documents via PEPPOL network.';
$lang['peppol_manual_mode_notice'] = 'Manual mode is for testing only. No actual PEPPOL transmission will occur.';
$lang['peppol_not_available'] = 'PEPPOL not available for this document';

// Bulk operation results translations
$lang['peppol_bulk_operation_results'] = 'PEPPOL Bulk Operation Results';
$lang['peppol_operation_summary'] = 'Operation Summary';
$lang['peppol_total_processed'] = 'Total';
$lang['peppol_successful'] = 'Successful';
$lang['peppol_failed'] = 'Failed';
$lang['peppol_success_rate'] = 'Success Rate';
$lang['peppol_error_details'] = 'Error Details';
$lang['peppol_errors_shown'] = 'errors shown';
$lang['peppol_view_error'] = 'View Error';
$lang['peppol_no_errors_found'] = 'No error details found';
$lang['peppol_error_loading_details'] = 'Error loading error details';
$lang['peppol_error_message'] = 'Error Message';
$lang['peppol_technical_details'] = 'Technical Details';

// UBL download translations
$lang['peppol_download_all_ubl'] = 'Download All UBL Files';
$lang['peppol_download_all_credit_note_ubl'] = 'Download All Credit Note UBL Files';
$lang['peppol_generate_view_ubl'] = 'View UBL';
$lang['peppol_generate_download_ubl'] = 'Download UBL';

// Client custom fields
$lang['peppol_client_identifier'] = 'PEPPOL Identifier';
$lang['peppol_client_scheme'] = 'PEPPOL Scheme';
$lang['peppol_client_identifier_help'] = 'Enter the client\'s PEPPOL participant identifier (e.g., 0123456789)';
$lang['peppol_client_scheme_help'] = 'Select the scheme used for the client\'s PEPPOL identifier';
$lang['peppol_client_fields_section'] = 'PEPPOL Information';
$lang['peppol_identifier_preview'] = 'PEPPOL Identifier Preview';