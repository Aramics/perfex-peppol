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

// Client bulk actions
$lang['peppol_bulk_send'] = 'PEPPOL Send';
$lang['peppol_bulk_send_credit_notes'] = 'PEPPOL Send Credit Notes';
$lang['peppol_bulk_send_tooltip'] = 'Send selected documents via PEPPOL';
$lang['peppol_bulk_send_invoices'] = 'Bulk Send Invoices via PEPPOL';
$lang['peppol_client_bulk_send_info'] = 'Send multiple documents for this client via PEPPOL network. Select which documents to send and configure send options.';
$lang['peppol_select_documents'] = 'Select Documents';
$lang['peppol_send_selected_only'] = 'Send selected documents only';
$lang['peppol_send_all_unsent'] = 'Send all unsent documents for this client';
$lang['peppol_selected'] = 'selected';
$lang['peppol_send_options'] = 'Send Options';
$lang['peppol_test_mode_bulk'] = 'Test mode (no actual sending)';
$lang['peppol_test_mode_bulk_help'] = 'Documents will be validated but not actually sent to PEPPOL network';
$lang['peppol_force_send'] = 'Force send (ignore previous failures)';
$lang['peppol_force_send_help'] = 'Resend documents that previously failed to send';
$lang['peppol_sending_progress'] = 'Sending Progress';
$lang['peppol_sent'] = 'Sent';
$lang['peppol_failed'] = 'Failed';
$lang['peppol_skipped'] = 'Skipped';
$lang['peppol_total'] = 'Total';
$lang['peppol_start_sending'] = 'Start Sending';
$lang['peppol_sending'] = 'Sending';
$lang['peppol_no_items_selected'] = 'No items selected. Please select at least one document to send.';
$lang['peppol_bulk_send_error'] = 'Bulk send error';
$lang['peppol_no_documents_found'] = 'No documents found for this client';
$lang['peppol_test_completed'] = 'Test completed: %d of %d documents validated successfully';
$lang['peppol_bulk_send_completed'] = 'Bulk send completed: %d of %d documents sent successfully';

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
$lang['peppol_client_no_identifier'] = 'Client #%s does not have a PEPPOL identifier configured';
$lang['peppol_add_client_identifier'] = 'Add PEPPOL Identifier';
$lang['peppol_invoice_ready_to_send'] = 'Invoice is ready to be sent via PEPPOL';
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
$lang['cf_translate_customers_peppol_identifier'] = 'PEPPOL Identifier';
$lang['cf_translate_customers_peppol_scheme'] = 'PEPPOL Scheme';
$lang['cf_translate_credit_notes_peppol_status'] = 'PEPPOL Status';

// Provider Management
$lang['peppol_providers'] = 'Providers';
$lang['peppol_access_point_providers'] = 'PEPPOL Access Point Providers';
$lang['peppol_active_provider'] = 'Active Provider';
$lang['peppol_active_provider_help'] = 'Select which PEPPOL provider to use for sending documents';
$lang['peppol_no_providers_registered'] = 'No PEPPOL providers are currently registered.';
$lang['peppol_no_providers_help'] = 'PEPPOL providers can be registered through modules, extensions, or custom implementations.';
$lang['peppol_invalid_provider'] = 'Invalid provider specified';
$lang['peppol_provider_not_found'] = 'Provider not found or not properly configured';
$lang['peppol_provider_no_test_available'] = 'Connection testing is not available for this provider';

// Empty State Information
$lang['peppol_for_developers'] = 'For Developers';
$lang['peppol_register_provider_info'] = 'To add PEPPOL providers, create a class that extends Abstract_peppol_provider and register it using the simplified hook system:';
$lang['peppol_provider_registration_location'] = 'Place your provider class and registration code in your module\'s hook file or a custom plugin file.';

// Provider Operations
$lang['peppol_ubl_validation_failed'] = 'UBL validation failed';
$lang['peppol_send_failed'] = 'Document send failed';
$lang['peppol_document_sent_successfully'] = '%s sent successfully via PEPPOL';
$lang['peppol_send_exception'] = 'Send exception';
$lang['peppol_status_retrieved'] = 'Document status retrieved successfully';
$lang['peppol_status_failed'] = 'Failed to retrieve status (HTTP %d)';
$lang['peppol_status_exception'] = 'Status retrieval exception';

// Ademico Provider
$lang['peppol_ademico_provider_name'] = 'Ademico PEPPOL';
$lang['peppol_ademico_provider_description'] = 'Ademico PEPPOL access point integration';
$lang['peppol_ademico_client_id'] = 'Client ID';
$lang['peppol_ademico_client_id_placeholder'] = 'Your Ademico client ID';
$lang['peppol_ademico_client_id_help'] = 'Client ID provided by Ademico';
$lang['peppol_ademico_client_secret'] = 'Client Secret';
$lang['peppol_ademico_client_secret_placeholder'] = 'Your Ademico client secret';
$lang['peppol_ademico_client_secret_help'] = 'Client secret provided by Ademico';
$lang['peppol_ademico_environment_help'] = 'Choose environment for API calls';
$lang['peppol_ademico_timeout'] = 'Timeout (seconds)';
$lang['peppol_ademico_timeout_help'] = 'API request timeout in seconds';
$lang['peppol_ademico_api_version'] = 'API Version';
$lang['peppol_ademico_api_version_help'] = 'Ademico API version';
$lang['peppol_ademico_document_sent_success'] = 'Document sent successfully via Ademico';
$lang['peppol_ademico_api_error'] = 'Ademico API error: %s';
$lang['peppol_ademico_connection_failed'] = 'Connection failed: %s';
$lang['peppol_ademico_credentials_required'] = 'Client ID and Client Secret are required';
$lang['peppol_ademico_connection_success'] = 'Connection successful - Ademico API is accessible';
$lang['peppol_ademico_health_check_failed'] = 'API health check failed: %s';
$lang['peppol_ademico_token_failed'] = 'Failed to obtain access token - check credentials';
$lang['peppol_ademico_test_failed'] = 'Connection test failed: %s';
$lang['peppol_ademico_unknown_error'] = 'Unknown error';

// Environment and Production
$lang['peppol_sandbox'] = 'Sandbox';
$lang['peppol_production'] = 'Production';
$lang['peppol_environment'] = 'Environment';

// Provider Interface Messages
$lang['peppol_provider_initialization_failed'] = 'Provider initialization failed';
$lang['peppol_provider_config_invalid'] = 'Provider configuration is invalid';
$lang['peppol_provider_unsupported_operation'] = 'Operation not supported by this provider';
$lang['peppol_provider_rate_limit_exceeded'] = 'Rate limit exceeded for this provider';
$lang['peppol_provider_maintenance'] = 'Provider is currently under maintenance';

// Extensibility Messages
$lang['peppol_register_provider_example'] = 'Example: Register a custom provider';
$lang['peppol_provider_registered_successfully'] = 'Provider registered successfully';
$lang['peppol_provider_registration_failed'] = 'Failed to register provider';
$lang['peppol_multiple_providers_available'] = 'Multiple PEPPOL providers are available';

// Validation Messages
$lang['peppol_ubl_validation_passed'] = 'UBL validation passed';
$lang['peppol_ubl_has_warnings'] = 'UBL validation passed with warnings';
$lang['peppol_ubl_validation_errors'] = 'UBL validation errors found';
$lang['peppol_document_format_invalid'] = 'Document format is invalid';
$lang['peppol_identifier_format_invalid'] = 'PEPPOL identifier format is invalid';
$lang['peppol_scheme_not_supported'] = 'PEPPOL scheme not supported by this provider';

// Advanced Provider Features
$lang['peppol_webhook_support'] = 'Webhook Support';
$lang['peppol_webhook_not_supported'] = 'Webhooks are not supported by this provider';
$lang['peppol_webhook_configured'] = 'Webhook configured successfully';
$lang['peppol_rate_limits'] = 'Rate Limits';
$lang['peppol_no_rate_limits'] = 'No rate limits configured';
$lang['peppol_rate_limit_info'] = 'Rate limit information';
$lang['peppol_transmission_mode'] = 'Transmission Mode';
$lang['peppol_real_transmission'] = 'Real Transmission';
$lang['peppol_test_transmission'] = 'Test/Simulation Only';

// PEPPOL Identifier Validation
$lang['peppol_validation_failed'] = 'PEPPOL validation failed';
$lang['peppol_sender_identifier_required'] = 'Company PEPPOL identifier is required. Please configure it in PEPPOL settings.';
$lang['peppol_sender_scheme_required'] = 'Company PEPPOL scheme is required. Please configure it in PEPPOL settings.';
$lang['peppol_receiver_identifier_required'] = 'Client PEPPOL identifier is required. Please set the "PEPPOL Identifier" custom field for this client.';
$lang['peppol_receiver_scheme_required'] = 'Client PEPPOL scheme is required. Please set the "PEPPOL Scheme" custom field for this client.';
$lang['peppol_identifier_too_short'] = 'PEPPOL identifier must be at least 3 characters long.';
$lang['peppol_scheme_invalid_format'] = 'PEPPOL scheme must be a 4-digit code (e.g., "0208").';
