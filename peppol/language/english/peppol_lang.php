<?php

defined('BASEPATH') or exit('No direct script access allowed');

// General
$lang['peppol'] = 'PEPPOL';
$lang['peppol_status'] = 'PEPPOL Status';
$lang['peppol_not_sent'] = 'Not sent';
$lang['peppol_invoices'] = 'PEPPOL Invoices';
$lang['peppol_documents'] = 'PEPPOL Documents';
$lang['peppol_documents_menu'] = 'Documents';
$lang['peppol_settings_menu'] = 'Settings';

// Status labels
$lang['peppol_status_pending'] = 'Pending';
$lang['peppol_status_queued'] = 'Queued';
$lang['peppol_status_sending'] = 'Sending';
$lang['peppol_status_sent'] = 'Sent';
$lang['peppol_status_delivered'] = 'Delivered';
$lang['peppol_status_failed'] = 'Failed';
$lang['peppol_status_received'] = 'Received';
$lang['peppol_status_rejected'] = 'Rejected';
$lang['peppol_status_rejected_inbound'] = 'Rejected (Inbound)';
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

// Documents management
$lang['peppol_invoice_documents'] = 'Invoices';
$lang['peppol_credit_note_documents'] = 'Credit Notes';
$lang['peppol_received_documents'] = 'Received';
$lang['peppol_documents_received_from_network'] = 'Documents received from PEPPOL network';
$lang['peppol_configure'] = 'Configure';
$lang['peppol_filter_documents'] = 'Filter Documents';
$lang['peppol_all_document_types'] = 'All Document Types';
$lang['peppol_all_statuses'] = 'All Statuses';
$lang['peppol_all_providers'] = 'All Providers';
$lang['peppol_apply_filters'] = 'Apply Filters';
$lang['peppol_clear_filters'] = 'Clear Filters';
$lang['peppol_documents_list'] = 'Documents List';
$lang['peppol_document_type'] = 'Type';
$lang['peppol_document_number'] = 'Number';
$lang['peppol_total_amount'] = 'Amount';
$lang['peppol_provider'] = 'Provider';
$lang['peppol_date'] = 'Date';
$lang['peppol_document_details'] = 'Document Details';
$lang['peppol_document_information'] = 'Document Information';
$lang['peppol_transmission_details'] = 'Transmission Details';
$lang['peppol_provider_document_id'] = 'Provider Document ID';
$lang['peppol_sent_at'] = 'Sent At';
$lang['peppol_received_at'] = 'Received At';
$lang['peppol_created_at'] = 'Created At';
$lang['peppol_metadata'] = 'Metadata';
$lang['peppol_attachments'] = 'Attachments';
$lang['peppol_no_attachments_found'] = 'No attachments found for this document.';
$lang['peppol_document_not_found'] = 'Document not found';
$lang['peppol_loading_document_details'] = 'Loading document details';

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
$lang['peppol_identifier'] = 'PEPPOL Identifier';
$lang['peppol_buyer_information'] = 'Buyer Information';
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

// Payment Information
$lang['peppol_payment_information'] = 'Payment Information';
$lang['peppol_payment_included'] = 'Payment information included in UBL';
$lang['peppol_payment_status_paid'] = 'Invoice fully paid';
$lang['peppol_payment_status_partial'] = 'Invoice partially paid';
$lang['peppol_payment_total_received'] = 'Total payment received';
$lang['peppol_payment_balance_due'] = 'Balance due';
$lang['peppol_payment_method'] = 'Payment method';
$lang['peppol_payment_date'] = 'Payment date';
$lang['peppol_payment_transaction_id'] = 'Transaction ID';

// Bank Information Settings
$lang['peppol_bank_information'] = 'Bank Information';
$lang['peppol_bank_information_help'] = 'Configure bank account details for PEPPOL credit transfer payments. This information is required to comply with PEPPOL business rules BR-61 and BR-50 when using bank transfer payment methods.';
$lang['peppol_bank_account'] = 'Bank Account / IBAN';
$lang['peppol_bank_account_placeholder'] = 'e.g., BE12 3456 7890 1234 or 123-456-789';
$lang['peppol_bank_account_help'] = 'Enter your bank account number or IBAN. This will be used as Payment Account Identifier (BT-84) in PEPPOL documents for credit transfer payments.';
$lang['peppol_bank_bic'] = 'BIC/SWIFT Code';
$lang['peppol_bank_bic_placeholder'] = 'e.g., GEBABEBB';
$lang['peppol_bank_bic_help'] = 'Bank Identifier Code (BIC) or SWIFT code of your bank. Optional but recommended for international transfers.';
$lang['peppol_bank_name'] = 'Bank Account Name';
$lang['peppol_bank_name_placeholder'] = 'e.g., Example Bank';
$lang['peppol_bank_name_help'] = 'Name for the bank account. This will be used as the account name in PEPPOL documents.';

// Payment Terms Templates
$lang['peppol_payment_terms_partial'] = 'Payment of %s received on %s. Balance due: %s';
$lang['peppol_payment_terms_paid'] = 'Invoice fully paid. Total payment: %s received on %s';
$lang['peppol_payment_terms_refund'] = 'Refund of %s processed on %s';

// Credit Note Reason Templates
$lang['peppol_credit_note_correction_reason'] = 'Correction of referenced invoice';
$lang['peppol_credit_note_discount_reason'] = 'Credit note for discount or refund';

// Webhook and Document Processing
$lang['peppol_webhook_processed'] = 'Webhook notifications processed';
$lang['peppol_incoming_document_processed'] = 'Incoming document processed';
$lang['peppol_document_status_updated'] = 'Document status updated';
$lang['peppol_get_ubl_xml'] = 'Get UBL XML';
$lang['peppol_download_ubl_xml'] = 'Download UBL XML';
$lang['peppol_ubl_retrieved'] = 'UBL XML retrieved successfully';
$lang['peppol_failed_to_retrieve_ubl'] = 'Failed to retrieve UBL XML';
$lang['peppol_download_provider_ubl'] = 'Download Original UBL';
$lang['peppol_provider_ubl_not_available'] = 'Provider UBL not available for this document';
$lang['peppol_provider_not_found_error'] = 'Provider not found: %s';
$lang['peppol_provider_no_ubl_support'] = 'Provider does not support UBL retrieval: %s';
$lang['peppol_ubl_retrieve_failed'] = 'Failed to retrieve UBL from provider: %s';
$lang['peppol_ubl_content_empty'] = 'UBL content is empty or not found in provider response';
$lang['peppol_invalid_identifier_type'] = 'Invalid identifier type. Expected string (transmission_id) or document object';
$lang['peppol_no_transmission_id'] = 'No transmission ID available for UBL retrieval';
$lang['peppol_ubl_retrieve_error'] = 'Error retrieving UBL from provider: %s';
$lang['peppol_no_local_reference'] = 'No Reference';
$lang['peppol_expense_reference'] = 'Expense Ref.';

// Invoice Response
$lang['peppol_mark_document_status'] = 'Mark Document Status';
$lang['peppol_mark_status_help'] = 'Mark the status of this received document and optionally send a response to the seller.';
$lang['peppol_select_status'] = 'Select status...';
$lang['peppol_status_acknowledged'] = 'Acknowledged';
$lang['peppol_status_in_process'] = 'In Process';
$lang['peppol_status_accepted'] = 'Accepted';
$lang['peppol_status_rejected'] = 'Rejected';
$lang['peppol_status_paid'] = 'Paid';
$lang['peppol_status_updated_successfully'] = 'Document status updated successfully.';
$lang['peppol_send_response'] = 'Send Response';
$lang['peppol_response_info_text'] = 'You can send a response back to the seller to inform them about the status of this received document.';
$lang['peppol_response_status'] = 'Response Status';
$lang['peppol_select_response_status'] = 'Select response status...';
$lang['peppol_response_status_help'] = 'Choose the appropriate status code to inform the seller.';
$lang['peppol_effective_date'] = 'Effective Date';
$lang['peppol_effective_date_help'] = 'The date when this status became effective.';
$lang['peppol_response_note'] = 'Note';
$lang['peppol_response_note_placeholder'] = 'Optional note providing more details for the response...';
$lang['peppol_response_note_help'] = 'Additional information about this response.';
$lang['peppol_clarifications'] = 'Clarifications';
$lang['peppol_clarifications_optional'] = 'Optional';
$lang['peppol_add_clarifications'] = 'Add Clarifications';
$lang['peppol_hide_clarifications'] = 'Hide Clarifications';
$lang['peppol_add_clarification'] = 'Add Clarification';
$lang['peppol_clarification_type'] = 'Type';
$lang['peppol_clarification_code'] = 'Code';
$lang['peppol_clarification_text'] = 'Description';
$lang['peppol_clarification_text_placeholder'] = 'Optional description...';
$lang['peppol_status_reason'] = 'Status Reason';
$lang['peppol_status_action'] = 'Status Action';
$lang['peppol_response_preview'] = 'Response Preview';
$lang['peppol_response_sent_successfully'] = 'Response sent successfully to the seller.';
$lang['peppol_response_send_failed'] = 'Failed to send response: %s';
$lang['peppol_response_error'] = 'Error processing response: %s';
$lang['peppol_cannot_respond_to_document'] = 'Cannot respond to this document.';
$lang['peppol_invalid_request_data'] = 'Invalid request data.';
$lang['peppol_provider_no_response_support'] = 'Provider %s does not support invoice responses.';

// Response Status Codes
$lang['peppol_response_status_ab'] = 'Acknowledged';
$lang['peppol_response_status_ab_desc'] = 'Buyer has received a readable invoice message';
$lang['peppol_response_status_ip'] = 'In Process';
$lang['peppol_response_status_ip_desc'] = 'Processing of the invoice has started';
$lang['peppol_response_status_uq'] = 'Under Query';
$lang['peppol_response_status_uq_desc'] = 'Additional information needed from seller';
$lang['peppol_response_status_ca'] = 'Conditionally Accepted';
$lang['peppol_response_status_ca_desc'] = 'Accepting under stated conditions';
$lang['peppol_response_status_re'] = 'Rejected';
$lang['peppol_response_status_re_desc'] = 'Invoice rejected, will not be processed';
$lang['peppol_response_status_ap'] = 'Accepted';
$lang['peppol_response_status_ap_desc'] = 'Final approval given, next step is payment';
$lang['peppol_response_status_pd'] = 'Paid';
$lang['peppol_response_status_pd_desc'] = 'Payment has been initiated';

// Clarification Reason Codes
$lang['peppol_clarification_reason_non'] = 'No issue';
$lang['peppol_clarification_reason_ref'] = 'References incorrect';
$lang['peppol_clarification_reason_leg'] = 'Legal information incorrect';
$lang['peppol_clarification_reason_rec'] = 'Receiver unknown';
$lang['peppol_clarification_reason_qua'] = 'Item quality insufficient';
$lang['peppol_clarification_reason_del'] = 'Delivery not acceptable';
$lang['peppol_clarification_reason_pri'] = 'Prices incorrect';
$lang['peppol_clarification_reason_qty'] = 'Quantity incorrect';
$lang['peppol_clarification_reason_itm'] = 'Items incorrect';
$lang['peppol_clarification_reason_pay'] = 'Payment terms incorrect';
$lang['peppol_clarification_reason_unr'] = 'Not recognized';
$lang['peppol_clarification_reason_fin'] = 'Finance incorrect';
$lang['peppol_clarification_reason_ppd'] = 'Partially paid';
$lang['peppol_clarification_reason_oth'] = 'Other';

// Clarification Action Codes
$lang['peppol_clarification_action_noa'] = 'No action required';
$lang['peppol_clarification_action_pin'] = 'Provide information';
$lang['peppol_clarification_action_nin'] = 'Issue new invoice';
$lang['peppol_clarification_action_cnf'] = 'Credit fully';
$lang['peppol_clarification_action_cnp'] = 'Credit partially';
$lang['peppol_clarification_action_cna'] = 'Credit the amount';
$lang['peppol_clarification_action_oth'] = 'Other';

// Clarification Types
$lang['peppol_clarification_type_status_reason'] = 'Status Reason';
$lang['peppol_clarification_type_status_action'] = 'Status Action';

// UI Labels for Clarifications
$lang['peppol_select_type'] = 'Select type...';
$lang['peppol_select_code'] = 'Select code...';
$lang['peppol_clarification_message'] = 'Message';
$lang['peppol_clarification_message_placeholder'] = 'Enter clarification message...';

// Missing Required Field Messages
$lang['peppol_missing_required_field'] = 'Missing required field: %s';
$lang['peppol_invalid_response_code'] = 'Invalid response code. Must be one of: %s';
$lang['peppol_document_response_sent_success'] = 'Document response sent successfully';
$lang['peppol_connection_failed'] = 'Connection failed: %s';

// UX Improvement Messages
$lang['peppol_update_document_status'] = 'Update Document Status';
$lang['peppol_click_to_send_response'] = 'Click to send a response to the seller';

// Expense Creation Messages
$lang['peppol_create_expense'] = 'Create Expense';
$lang['peppol_view_expense_record'] = 'View Expense Record';
$lang['peppol_confirm_create_expense'] = 'Create an expense record from this received invoice?';
$lang['peppol_confirm_create_expense_credit'] = 'Create an expense record from this received credit note? (Amount will be negative)';
$lang['peppol_expense_created_successfully'] = 'Expense record created successfully';
$lang['peppol_expense_already_created'] = 'Expense record already exists for this document';
$lang['peppol_expense_only_received_documents'] = 'Expenses can only be created from received documents';
$lang['peppol_expense_invoice_not_paid'] = 'Cannot create expense: Invoice must be marked as paid first';
$lang['peppol_expense_credit_note_not_accepted'] = 'Cannot create expense: Credit note must be accepted first';
$lang['peppol_failed_to_parse_document'] = 'Failed to parse document data';
$lang['peppol_failed_to_create_expense'] = 'Failed to create expense record';

// Enhanced Expense Creation
$lang['auto_detected'] = 'Auto-detected';
$lang['auto_detected_information'] = 'Auto-detected Information';
$lang['peppol_auto_detected_help'] = 'These values were automatically detected from the PEPPOL document. You can modify them if needed.';
$lang['negative_amount_for_credit_note'] = 'negative amount for credit note';
$lang['negative_for_credit_note'] = 'negative for credit note';
$lang['expense_details'] = 'Expense Details';
$lang['tax_2'] = 'Tax 2';
$lang['expense_date'] = 'Date';
$lang['processing'] = 'Processing...';
$lang['vendor_identifier'] = 'Vendor Identifier';

// Validation Messages
$lang['peppol_response_code_required'] = 'Response code is required.';
$lang['peppol_response_code_invalid'] = 'Invalid response code selected.';
$lang['peppol_effective_date_invalid'] = 'Invalid effective date format.';
$lang['peppol_clarification_type_required'] = 'Clarification %d: Type is required.';
$lang['peppol_clarification_type_invalid'] = 'Clarification %d: Invalid type selected.';
$lang['peppol_clarification_reason_invalid'] = 'Clarification %d: Invalid reason code.';
$lang['peppol_clarification_action_invalid'] = 'Clarification %d: Invalid action code.';

// Expense Statistics
$lang['peppol_expense_statistics'] = 'Expense Statistics';
$lang['peppol_total_expenses_created'] = 'Total Expenses Created';
$lang['peppol_invoice_expenses'] = 'Invoice Expenses';
$lang['peppol_credit_note_expenses'] = 'Credit Note Expenses';
$lang['peppol_total_expense_amount'] = 'Total Expense Amount';
$lang['peppol_expense_conversion_eligible'] = 'Eligible for Expense Conversion';
$lang['peppol_already_converted_to_expense'] = 'Already Converted to Expense';
$lang['peppol_eligible_for_conversion'] = 'Eligible for Conversion';
$lang['peppol_not_eligible_for_conversion'] = 'Not Eligible for Conversion';
$lang['peppol_expense_conversion_stats'] = 'Expense Conversion Statistics';
$lang['peppol_eligible'] = 'Eligible';
$lang['peppol_converted'] = 'Converted';
$lang['peppol_not_eligible'] = 'Not Eligible';
$lang['peppol_invoice_expenses_subtitle'] = 'From paid invoices';
$lang['peppol_credit_note_expenses_subtitle'] = 'From accepted credit notes';

// Notification Settings
$lang['peppol_notifications'] = 'Notifications';
$lang['peppol_cron'] = 'Cron';
$lang['peppol_notification_lookup_time'] = 'Notification Lookup Time (Hours)';
$lang['peppol_notification_lookup_hours_help'] = 'Number of hours to look back for notifications. Use decimals for precision (e.g., 1.5 = 1 hour 30 minutes)';
$lang['peppol_cron_interval'] = 'Cron Interval (Minutes)';
$lang['peppol_cron_interval_help'] = 'How often to run the notification check process';
$lang['peppol_last_notification_check'] = 'Last Check';
$lang['peppol_next_notification_check'] = 'Next Check';
$lang['peppol_calculating'] = 'Calculating';
$lang['when_cron_runs'] = 'When cron runs next';
$lang['never'] = 'Never';

// Auto-create Expenses Settings
$lang['peppol_auto_create_invoice_expenses'] = 'Create expenses from received invoices when marked as fully paid';
$lang['peppol_auto_create_invoice_expenses_help'] = 'Automatically create expense records when received invoices are marked as fully paid';
$lang['peppol_auto_create_credit_note_expenses'] = 'Create expenses from received credit notes when accepted (negative amounts)';
$lang['peppol_auto_create_credit_note_expenses_help'] = 'Automatically create negative expense records when received credit notes are marked as accepted';

// Document viewing
$lang['back_to_documents'] = 'Back to Documents';
$lang['peppol_update_status'] = 'Update Status';
$lang['view_expense'] = 'View Expense';
$lang['peppol_download_ubl'] = 'Download UBL';
$lang['peppol_document_preview'] = 'Document Preview';
$lang['peppol_seller_information'] = 'Seller Information';
$lang['peppol_line_items'] = 'Line Items';
$lang['peppol_totals'] = 'Totals';
$lang['peppol_no_preview_available'] = 'No preview available for this document';
$lang['show_hide'] = 'Show/Hide';
$lang['peppol_status_information'] = 'Status Information';
$lang['peppol_current_status'] = 'Current Status';
$lang['peppol_direction'] = 'Direction';
$lang['peppol_inbound'] = 'Inbound';
$lang['peppol_outbound'] = 'Outbound';
$lang['peppol_cannot_update_status_outbound'] = 'Cannot update status for outbound documents';
$lang['peppol_transmission_id'] = 'Transmission ID';
$lang['peppol_updated_at'] = 'Updated At';
$lang['peppol_document_information'] = 'Document Information';
$lang['peppol_provider_document_id'] = 'Provider Doc ID';
$lang['peppol_local_reference'] = 'Local Document Ref.';
$lang['peppol_document_number'] = 'Document Number';
$lang['peppol_total_amount'] = 'Total Amount';
$lang['peppol_date'] = 'Date';
$lang['peppol_due_date'] = 'Due Date';
$lang['peppol_all_document_types'] = 'All Document Types';
$lang['peppol_all_statuses'] = 'All Statuses';
$lang['peppol_all_providers'] = 'All Providers';
$lang['peppol_apply_filters'] = 'Apply Filters';
$lang['peppol_clear_filters'] = 'Clear Filters';
$lang['peppol_loading_document_details'] = 'Loading document details';
$lang['peppol_attachments'] = 'Attachments';
$lang['peppol_no_attachments_found'] = 'No attachments found';
$lang['peppol_metadata'] = 'Metadata';
$lang['peppol_sent_at'] = 'Sent At';
$lang['peppol_received_at'] = 'Received At';
$lang['peppol_created_at'] = 'Created At';
$lang['peppol_invoice_documents'] = 'Invoice Documents';
$lang['peppol_credit_note_documents'] = 'Credit Note Documents';
$lang['peppol_total_expenses_created'] = 'Total Expenses Created';
$lang['peppol_total_expense_amount'] = 'Total Expense Amount';
$lang['peppol_invoice_expenses'] = 'Invoice Expenses';
$lang['peppol_credit_note_expenses'] = 'Credit Note Expenses';
$lang['peppol_invoice_expenses_subtitle'] = 'expenses from invoices';
$lang['peppol_credit_note_expenses_subtitle'] = 'expenses from credit notes';
$lang['peppol_amount'] = 'Amount';
$lang['peppol_number'] = 'Number';
$lang['peppol_description'] = 'Description';
$lang['peppol_quantity'] = 'Quantity';
$lang['peppol_unit_price'] = 'Unit Price';
$lang['peppol_subtotal'] = 'Subtotal';
$lang['peppol_tax'] = 'Tax';
$lang['peppol_total'] = 'Total';
$lang['peppol_notes'] = 'Notes';
$lang['peppol_view_expense'] = 'View Expense';

// Logs functionality
$lang['peppol_logs'] = 'PEPPOL Logs';
$lang['peppol_logs_menu'] = 'Logs';
$lang['peppol_clear_logs'] = 'Clear Logs';
$lang['peppol_logs_cleared_successfully'] = 'Logs cleared successfully';
$lang['peppol_logs_clear_failed'] = 'Failed to clear logs';
$lang['peppol_confirm_clear_logs'] = 'Are you sure you want to clear all PEPPOL logs? This action cannot be undone.';
$lang['peppol_action'] = 'Action';
$lang['peppol_message'] = 'Message';
$lang['peppol_date_created'] = 'Date Created';
$lang['peppol_filter_action'] = 'Filter by Action';
$lang['peppol_filter_document_type'] = 'Filter by Document Type';
$lang['peppol_filter_status'] = 'Filter by Status';
$lang['peppol_process_notifications'] = "Process Notifications";