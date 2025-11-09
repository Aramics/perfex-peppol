<?php

# Version 1.0.0
# PEPPOL Module Language File - English
# Comprehensive translations for the PEPPOL integration module

// ========================================
// CORE MODULE LABELS
// ========================================

$lang['peppol'] = 'PEPPOL';
$lang['peppol_invoices'] = 'PEPPOL Invoices';
$lang['peppol_logs'] = 'PEPPOL Activity Logs';
$lang['peppol_settings'] = 'PEPPOL Configuration';
$lang['peppol_transaction_logs'] = 'Transaction Logs';
$lang['peppol_received_documents'] = 'Received PEPPOL Documents';
$lang['peppol_module_name'] = 'PEPPOL Electronic Invoicing';
$lang['peppol_module_description'] = 'Send and receive electronic invoices through the PEPPOL network';

// ========================================
// PERMISSIONS & CAPABILITIES
// ========================================

$lang['peppol_permission_send'] = 'Send PEPPOL Invoices';
$lang['peppol_permission_receive'] = 'Receive PEPPOL Documents';
$lang['peppol_permission_view'] = 'View PEPPOL Data';
$lang['peppol_permission_edit'] = 'Edit PEPPOL Settings';
$lang['peppol_permission_delete'] = 'Delete PEPPOL Records';
$lang['peppol_permission_manage'] = 'Manage PEPPOL Configuration';

// ========================================
// GENERAL INTERFACE ELEMENTS
// ========================================

$lang['peppol_send_invoice'] = 'Send via PEPPOL Network';
$lang['peppol_status'] = 'PEPPOL Status';
$lang['peppol_document_id'] = 'Document ID';
$lang['peppol_provider'] = 'Access Point Provider';
$lang['peppol_environment'] = 'Environment Mode';
$lang['peppol_sent_at'] = 'Sent Date & Time';
$lang['peppol_received_at'] = 'Received Date & Time';
$lang['peppol_processed_at'] = 'Processed Date & Time';
$lang['peppol_created_at'] = 'Created Date & Time';
$lang['peppol_updated_at'] = 'Last Updated';
$lang['peppol_invoice_number'] = 'Invoice Number';
$lang['peppol_reference'] = 'Reference';
$lang['peppol_sender'] = 'Sender';
$lang['peppol_receiver'] = 'Receiver';
$lang['peppol_network'] = 'PEPPOL Network';
$lang['peppol_format'] = 'Document Format';
$lang['peppol_version'] = 'UBL Version';

// ========================================
// CONFIGURATION & SETTINGS
// ========================================

$lang['peppol_active_provider'] = 'Active Access Point Provider';
$lang['peppol_environment_live'] = 'Live (Production)';
$lang['peppol_environment_sandbox'] = 'Sandbox (Testing)';
$lang['peppol_environment_help'] = 'Choose Sandbox for testing or Live for production. Always test thoroughly in sandbox before switching to live mode.';
$lang['peppol_auto_send_enabled'] = 'Automatic Invoice Sending';
$lang['peppol_auto_send_help'] = 'Automatically transmit invoices via PEPPOL when they are marked as sent in Perfex CRM';
$lang['peppol_auto_process_received'] = 'Auto-process Incoming Documents';
$lang['peppol_auto_process_help'] = 'Automatically convert received PEPPOL documents into invoices or credit notes in your system';
$lang['peppol_retry_failed_documents'] = 'Retry Failed Transmissions';
$lang['peppol_retry_failed_help'] = 'Automatically retry sending documents that failed due to temporary network issues';
$lang['peppol_notification_enabled'] = 'Email Notifications';
$lang['peppol_notification_help'] = 'Send email notifications when documents are successfully sent or delivery fails';

// ========================================
// COMPANY PEPPOL CONFIGURATION
// ========================================

$lang['peppol_company_identifier'] = 'Company PEPPOL ID';
$lang['peppol_company_identifier_help'] = 'Your organization\'s unique PEPPOL participant identifier. This must be registered with your access point provider and match your business registration.';
$lang['peppol_company_scheme'] = 'Identifier Scheme Code';
$lang['peppol_company_scheme_help'] = 'The international scheme code for your identifier type:<br>• 0088: Global Location Number (GLN)<br>• 0184: Danish CVR<br>• 0192: Norwegian Organization Number<br>• 9915: D-U-N-S Number<br>• 9925: Business registration number';
$lang['peppol_company_country'] = 'Country Code (ISO)';
$lang['peppol_company_country_help'] = 'Two-letter ISO country code where your company is registered';
$lang['peppol_company_endpoint'] = 'Company Endpoint ID';
$lang['peppol_company_certificate'] = 'PEPPOL Certificate';
$lang['peppol_company_certificate_help'] = 'Your PEPPOL certificate for secure document transmission';

// ========================================
// ACCESS POINT PROVIDER CONFIGURATION
// ========================================

$lang['peppol_provider_settings'] = 'Access Point Provider Configuration';
$lang['peppol_provider_selection'] = 'Provider Selection';
$lang['peppol_provider_selection_help'] = 'Choose your PEPPOL access point provider. Each provider offers different features and pricing models.';
$lang['peppol_provider_features'] = 'Provider Features';
$lang['peppol_provider_documentation'] = 'Provider Documentation';

// Test credentials configuration
$lang['peppol_test_credentials'] = 'Test/Sandbox Credentials';
$lang['peppol_test_credentials_note'] = 'Important Security Notice';
$lang['peppol_test_credentials_help'] = 'These test credentials are used when running automated tests or validating your provider configuration. They should be different from your production credentials and only work in sandbox environments.';
$lang['peppol_production_credentials'] = 'Production Credentials';
$lang['peppol_endpoint_configuration'] = 'Endpoint Configuration';

// Ademico Provider
$lang['peppol_ademico_settings'] = 'Ademico Software Configuration';
$lang['peppol_ademico_description'] = 'Ademico provides comprehensive PEPPOL services with JSON API integration';

// Unit4 Provider
$lang['peppol_unit4_settings'] = 'Unit4 Access Point Configuration';
$lang['peppol_unit4_description'] = 'Unit4 offers enterprise-grade PEPPOL connectivity with multipart document uploads';

// Recommand Provider
$lang['peppol_recommand_settings'] = 'Recommand PEPPOL Configuration';
$lang['peppol_recommand_description'] = 'Recommand provides simplified JSON-based PEPPOL integration with automatic UBL conversion';

// ========================================
// ADEMICO PROVIDER SETTINGS
// ========================================

$lang['peppol_ademico_client_id'] = 'OAuth2 Client Identifier';
$lang['peppol_ademico_client_id_help'] = 'OAuth2 client identifier provided by Ademico for JWT token authentication. Keep this secure.';
$lang['peppol_ademico_client_secret'] = 'OAuth2 Client Secret';
$lang['peppol_ademico_client_secret_help'] = 'OAuth2 client secret provided by Ademico. This is used to generate secure JWT tokens for API access.';

// Ademico test credentials
$lang['peppol_ademico_client_id_test'] = 'Test OAuth2 Client Identifier';
$lang['peppol_ademico_client_id_test_help'] = 'OAuth2 client identifier for testing/sandbox environment provided by Ademico. Used for automated tests and validation.';
$lang['peppol_ademico_client_secret_test'] = 'Test OAuth2 Client Secret';
$lang['peppol_ademico_client_secret_test_help'] = 'OAuth2 client secret for testing/sandbox environment. This should be different from your production credentials.';

// ========================================
// UNIT4 PROVIDER SETTINGS
// ========================================

$lang['peppol_unit4_username'] = 'Unit4 Username';
$lang['peppol_unit4_username_help'] = 'Your Unit4 access point username for authentication';
$lang['peppol_unit4_password'] = 'Unit4 Password';
$lang['peppol_unit4_password_help'] = 'Your secure password for Unit4 access point access';
$lang['peppol_unit4_endpoint_url'] = 'Production Endpoint';
$lang['peppol_unit4_endpoint_url_help'] = 'Unit4 production API endpoint for live document transmission';
$lang['peppol_unit4_sandbox_endpoint'] = 'Testing Endpoint';
$lang['peppol_unit4_sandbox_endpoint_help'] = 'Unit4 sandbox endpoint for development and testing';
$lang['peppol_unit4_certificate'] = 'Client Certificate';
$lang['peppol_unit4_certificate_help'] = 'Optional client certificate for enhanced security';

// Unit4 test credentials
$lang['peppol_unit4_username_test'] = 'Test Username';
$lang['peppol_unit4_username_test_help'] = 'Unit4 username for testing/sandbox environment. Used for automated tests and validation.';
$lang['peppol_unit4_password_test'] = 'Test Password';
$lang['peppol_unit4_password_test_help'] = 'Unit4 password for testing/sandbox environment. This should be different from your production credentials.';

// ========================================
// RECOMMAND PROVIDER SETTINGS
// ========================================

$lang['peppol_recommand_api_key'] = 'Recommand API Key';
$lang['peppol_recommand_api_key_help'] = 'Your Recommand API authentication key for secure access';
$lang['peppol_recommand_company_id'] = 'Recommand Company ID';
$lang['peppol_recommand_company_id_help'] = 'Your unique company identifier in the Recommand platform';
$lang['peppol_recommand_endpoint_url'] = 'Production API URL';
$lang['peppol_recommand_endpoint_url_help'] = 'Recommand production API endpoint for live operations';
$lang['peppol_recommand_sandbox_endpoint'] = 'Sandbox API URL';
$lang['peppol_recommand_sandbox_endpoint_help'] = 'Recommand testing environment for safe development';
$lang['peppol_recommand_webhook_token'] = 'Webhook Verification Token';
$lang['peppol_recommand_webhook_token_help'] = 'Token used to verify incoming webhook authenticity';

// Recommand test credentials
$lang['peppol_recommand_api_key_test'] = 'Test API Key';
$lang['peppol_recommand_api_key_test_help'] = 'Recommand API key for testing/sandbox environment. Used for automated tests and validation.';
$lang['peppol_recommand_company_id_test'] = 'Test Company ID';
$lang['peppol_recommand_company_id_test_help'] = 'Recommand company identifier for testing/sandbox environment. This should be different from your production company ID.';

// ========================================
// CLIENT/CUSTOMER PEPPOL SETTINGS
// ========================================

$lang['peppol_client_identifier'] = 'Customer PEPPOL ID';
$lang['peppol_client_scheme'] = 'Customer ID Scheme';
$lang['peppol_client_identifier_help'] = 'The customer\'s PEPPOL participant identifier for electronic invoice delivery. Required for PEPPOL transmission.';
$lang['peppol_client_scheme_help'] = 'The scheme code used for this customer\'s PEPPOL identifier';
$lang['peppol_client_endpoint'] = 'Customer Endpoint';
$lang['peppol_client_validation'] = 'Validate PEPPOL ID';
$lang['peppol_client_validation_help'] = 'Verify that this customer can receive PEPPOL documents';
$lang['peppol_client_preferred_format'] = 'Preferred Document Format';
$lang['peppol_client_language'] = 'Document Language';

// ========================================
// DOCUMENT TRANSMISSION STATUSES
// ========================================

$lang['peppol_status_pending'] = 'Pending Transmission';
$lang['peppol_status_queued'] = 'Queued for Sending';
$lang['peppol_status_sending'] = 'Transmitting';
$lang['peppol_status_sent'] = 'Successfully Sent';
$lang['peppol_status_delivered'] = 'Delivered to Recipient';
$lang['peppol_status_acknowledged'] = 'Receipt Acknowledged';
$lang['peppol_status_failed'] = 'Transmission Failed';
$lang['peppol_status_rejected'] = 'Rejected by Recipient';
$lang['peppol_status_received'] = 'Document Received';
$lang['peppol_status_processed'] = 'Successfully Processed';
$lang['peppol_status_error'] = 'Processing Error';
$lang['peppol_status_cancelled'] = 'Transmission Cancelled';
$lang['peppol_status_timeout'] = 'Connection Timeout';
$lang['peppol_status_unknown'] = 'Status Unknown';

// Status descriptions for tooltips
$lang['peppol_status_pending_desc'] = 'Document is prepared and waiting to be sent';
$lang['peppol_status_queued_desc'] = 'Document is in the transmission queue';
$lang['peppol_status_sending_desc'] = 'Document is currently being transmitted';
$lang['peppol_status_sent_desc'] = 'Document was successfully sent to the PEPPOL network';
$lang['peppol_status_delivered_desc'] = 'Document was delivered to the recipient\'s access point';
$lang['peppol_status_failed_desc'] = 'Document transmission failed - check error details';
$lang['peppol_status_processed_desc'] = 'Document was successfully processed by the recipient';

// ========================================
// ACTION BUTTONS & COMMANDS
// ========================================

$lang['peppol_send_now'] = 'Send Now via PEPPOL';
$lang['peppol_resend'] = 'Resend Document';
$lang['peppol_retry'] = 'Retry Transmission';
$lang['peppol_cancel'] = 'Cancel Transmission';
$lang['peppol_view_ubl'] = 'View UBL Document';
$lang['peppol_download_ubl'] = 'Download UBL XML';
$lang['peppol_download_original'] = 'Download Original';
$lang['peppol_test_connection'] = 'Test Provider Connection';
$lang['peppol_validate_document'] = 'Validate Document';
$lang['peppol_check_recipient'] = 'Verify Recipient';
$lang['peppol_view_logs'] = 'View Transaction Logs';
$lang['peppol_export_logs'] = 'Export Activity Logs';
$lang['peppol_clear_logs'] = 'Clear Old Logs';
$lang['peppol_refresh_status'] = 'Refresh Status';
$lang['peppol_bulk_send'] = 'Bulk Send Invoices';
$lang['peppol_bulk_resend'] = 'Bulk Resend Failed';
$lang['peppol_force_send'] = 'Force Send (Override Checks)';

// Test Suite Runner
$lang['peppol_run_tests'] = 'Run Tests';
$lang['peppol_stop_tests'] = 'Stop Tests';
$lang['peppol_tests'] = 'Tests';
$lang['peppol_test_suite'] = 'Automated Test Suite';
$lang['peppol_test_suite_help'] = 'Run automated tests to validate your PEPPOL provider configuration and credentials. Tests will use the configured test credentials and run against sandbox environments.';
$lang['peppol_test_configuration'] = 'Test Configuration';
$lang['peppol_test_provider'] = 'Test Provider';
$lang['peppol_test_provider_help'] = 'Select which PEPPOL provider to run tests against. The active provider is pre-selected.';
$lang['peppol_test_suite_type'] = 'Test Type';
$lang['peppol_test_suite_type_help'] = 'Choose which tests to run. "All Tests" runs comprehensive validation including legal entities and documents.';
$lang['peppol_test_legal_entities'] = 'Legal Entity Tests';
$lang['peppol_test_documents'] = 'Document Processing Tests';
$lang['peppol_test_all'] = 'All Tests';
$lang['peppol_test_results'] = 'Test Results';
$lang['peppol_test_completed'] = 'Test execution completed';
$lang['peppol_test_failed'] = 'Some tests failed';
$lang['peppol_test_passed'] = 'All tests passed successfully';
$lang['peppol_running_tests'] = 'Running Tests...';
$lang['peppol_test_error'] = 'Error executing tests';
$lang['peppol_test_stopped'] = 'Test execution stopped';

// ========================================
// SUCCESS & INFORMATION MESSAGES
// ========================================

$lang['peppol_invoice_sent_successfully'] = 'Invoice has been successfully transmitted via the PEPPOL network';
$lang['peppol_invoice_queued_successfully'] = 'Invoice has been queued for PEPPOL transmission';
$lang['peppol_bulk_send_completed'] = 'Bulk sending operation completed. %d invoices sent, %d failed.';
$lang['peppol_connection_test_success'] = 'Provider connection test successful - API is responding correctly';
$lang['peppol_settings_saved'] = 'PEPPOL configuration has been saved successfully';
$lang['peppol_document_received'] = 'New PEPPOL document received from %s';
$lang['peppol_document_processed'] = 'PEPPOL document has been successfully processed and imported';
$lang['peppol_status_updated'] = 'Document transmission status has been updated';
$lang['peppol_provider_switched'] = 'Successfully switched to %s provider';
$lang['peppol_webhook_configured'] = 'Webhook endpoints have been configured for status updates';
$lang['peppol_logs_cleared'] = 'Transaction logs older than %d days have been cleared';
$lang['peppol_validation_passed'] = 'Document validation passed - ready for transmission';
$lang['peppol_recipient_verified'] = 'Recipient PEPPOL ID verified and can receive documents';

// ========================================
// ERROR MESSAGES
// ========================================

$lang['peppol_invoice_send_failed'] = 'Failed to transmit invoice via PEPPOL network';
$lang['peppol_connection_test_failed'] = 'Provider connection test failed - check your configuration';
$lang['peppol_missing_required_fields'] = 'Missing required fields for PEPPOL registration';
$lang['peppol_identifier_required_error'] = 'PEPPOL Identifier is required for legal entity registration. Please set the PEPPOL Identifier for this client.';
$lang['peppol_scheme_required_error'] = 'PEPPOL Scheme is required for legal entity registration. Please set the PEPPOL Scheme for this client.';
$lang['peppol_not_configured'] = 'PEPPOL module is not properly configured. Please complete the setup in settings.';
$lang['peppol_client_no_identifier'] = 'Customer does not have a PEPPOL identifier configured. Electronic transmission is not possible.';
$lang['peppol_document_processing_failed'] = 'Failed to process the received PEPPOL document';
$lang['peppol_invalid_configuration'] = 'PEPPOL configuration is invalid or incomplete';
$lang['peppol_provider_unavailable'] = 'Selected PEPPOL provider is currently unavailable';
$lang['peppol_transmission_timeout'] = 'Document transmission timed out - the provider may be experiencing issues';
$lang['peppol_invalid_recipient'] = 'Recipient PEPPOL ID is invalid or not reachable';
$lang['peppol_document_too_large'] = 'Document exceeds the maximum size limit for PEPPOL transmission';
$lang['peppol_quota_exceeded'] = 'Monthly transmission quota has been exceeded for your provider account';
$lang['peppol_authentication_failed'] = 'Authentication with PEPPOL provider failed - check your credentials';
$lang['peppol_network_error'] = 'Network connectivity issue prevented PEPPOL transmission';
$lang['peppol_validation_failed'] = 'Document validation failed - cannot transmit invalid UBL document';
$lang['peppol_unsupported_document'] = 'Document type is not supported for PEPPOL transmission';
$lang['peppol_duplicate_transmission'] = 'This document has already been transmitted via PEPPOL';

// ========================================
// TECHNICAL ERROR MESSAGES
// ========================================

$lang['peppol_error_invalid_provider'] = 'The specified PEPPOL access point provider is not supported';
$lang['peppol_error_missing_config'] = 'Required provider configuration parameters are missing';
$lang['peppol_error_api_connection'] = 'Unable to establish connection with PEPPOL provider API';
$lang['peppol_error_invalid_ubl'] = 'Generated UBL document does not conform to PEPPOL standards';
$lang['peppol_error_delivery_failed'] = 'Document delivery to recipient failed through PEPPOL network';
$lang['peppol_error_webhook_verification'] = 'Webhook signature verification failed - potential security issue';
$lang['peppol_error_database'] = 'Database error occurred while processing PEPPOL transaction';
$lang['peppol_error_file_access'] = 'Unable to access required files for PEPPOL processing';
$lang['peppol_error_memory_limit'] = 'Memory limit exceeded during document processing';
$lang['peppol_error_ssl_certificate'] = 'SSL certificate validation failed for provider endpoint';
$lang['peppol_error_rate_limit'] = 'API rate limit exceeded - please wait before retrying';
$lang['peppol_error_maintenance'] = 'Provider is currently under maintenance - try again later';
$lang['peppol_error_unsupported_format'] = 'Document format is not supported by the selected provider';
$lang['peppol_error_encryption'] = 'Document encryption/decryption failed';
$lang['peppol_error_signature'] = 'Digital signature validation failed';

// ========================================
// TABLE HEADERS & COLUMN LABELS
// ========================================

$lang['peppol_table_invoice'] = 'Invoice Number';
$lang['peppol_table_client'] = 'Customer';
$lang['peppol_table_status'] = 'Transmission Status';
$lang['peppol_table_provider'] = 'Access Point Provider';
$lang['peppol_table_sent_date'] = 'Date Sent';
$lang['peppol_table_received_date'] = 'Date Received';
$lang['peppol_table_document_id'] = 'PEPPOL Document ID';
$lang['peppol_table_action'] = 'Available Actions';
$lang['peppol_table_message'] = 'Status Message';
$lang['peppol_table_created'] = 'Created Date';
$lang['peppol_table_reference'] = 'Reference Number';
$lang['peppol_table_sender'] = 'Sender ID';
$lang['peppol_table_receiver'] = 'Receiver ID';
$lang['peppol_table_document_type'] = 'Document Type';
$lang['peppol_table_file_size'] = 'File Size';
$lang['peppol_table_attempts'] = 'Retry Attempts';
$lang['peppol_table_last_attempt'] = 'Last Attempt';
$lang['peppol_table_environment'] = 'Environment';
$lang['peppol_table_error_code'] = 'Error Code';
$lang['peppol_table_processing_time'] = 'Processing Time';

// ========================================
// FORM VALIDATION MESSAGES
// ========================================

$lang['peppol_validation_identifier_required'] = 'PEPPOL participant identifier is required for electronic transmission';
$lang['peppol_validation_identifier_format'] = 'PEPPOL identifier format is invalid for the selected scheme';
$lang['peppol_validation_scheme_required'] = 'Identifier scheme code is required and must be valid';
$lang['peppol_validation_api_key_required'] = 'Provider API key is required for authentication';
$lang['peppol_validation_api_key_format'] = 'API key format appears to be invalid';
$lang['peppol_validation_endpoint_required'] = 'Provider endpoint URL is required';
$lang['peppol_validation_endpoint_format'] = 'Endpoint URL must be a valid HTTPS URL';
$lang['peppol_validation_company_id_required'] = 'Company ID is required for provider integration';
$lang['peppol_validation_username_required'] = 'Username is required for provider authentication';
$lang['peppol_validation_password_required'] = 'Password is required and should be kept secure';
$lang['peppol_validation_country_code'] = 'Country code must be a valid 2-letter ISO code';
$lang['peppol_validation_duplicate_identifier'] = 'This PEPPOL identifier is already in use by another customer';
$lang['peppol_validation_unsupported_scheme'] = 'The selected identifier scheme is not supported by this provider';
$lang['peppol_validation_test_failed'] = 'Configuration test failed - please verify all settings';
$lang['peppol_validation_certificate_invalid'] = 'PEPPOL certificate is invalid or expired';
$lang['peppol_validation_environment_mismatch'] = 'Environment setting does not match provider configuration';

// ========================================
// CONFIRMATION & DIALOG MESSAGES
// ========================================

$lang['confirm'] = 'Are you sure you want to proceed?';
$lang['peppol_confirm_send'] = 'Are you sure you want to send this invoice via PEPPOL?';
$lang['peppol_confirm_resend'] = 'Are you sure you want to resend this failed document?';
$lang['peppol_confirm_cancel'] = 'Are you sure you want to cancel this transmission?';
$lang['peppol_confirm_delete'] = 'Are you sure you want to delete this PEPPOL record?';
$lang['peppol_confirm_clear_logs'] = 'Are you sure you want to clear old transaction logs?';
$lang['peppol_confirm_bulk_send'] = 'Send %d invoices via PEPPOL? This action cannot be undone.';
$lang['peppol_confirm_switch_environment'] = 'Switch to %s environment? This will affect all future transmissions.';
$lang['peppol_confirm_force_send'] = 'Force send this document? This bypasses validation checks and may result in transmission errors.';

// ========================================
// DATATABLE LOCALIZATION
// ========================================

$lang['dt_empty_table'] = 'No PEPPOL transactions available';
$lang['dt_showing_entries'] = 'Showing _START_ to _END_ of _TOTAL_ transactions';
$lang['dt_info_empty'] = 'No transactions to display';
$lang['dt_info_filtered'] = '(filtered from _MAX_ total transactions)';
$lang['dt_length_menu'] = 'Display _MENU_ transactions per page';
$lang['dt_loading_records'] = 'Loading PEPPOL data...';
$lang['dt_processing'] = 'Processing request...';
$lang['dt_search'] = 'Search transactions:';
$lang['dt_zero_records'] = 'No matching PEPPOL transactions found';
$lang['dt_paginate_first'] = 'First Page';
$lang['dt_paginate_last'] = 'Last Page';
$lang['dt_paginate_next'] = 'Next Page';
$lang['dt_paginate_previous'] = 'Previous Page';

// ========================================
// SETTINGS INTERFACE ELEMENTS
// ========================================

$lang['general'] = 'General Configuration';
$lang['company'] = 'Company Information';
$lang['provider'] = 'Provider Settings';
$lang['advanced'] = 'Advanced Options';
$lang['monitoring'] = 'Monitoring & Logs';

// Webhook Configuration
$lang['webhook_urls'] = 'Webhook Endpoint URLs';
$lang['webhook_configuration_help'] = 'Configure these webhook URLs in your PEPPOL provider dashboard to receive real-time notifications about document status changes, delivery confirmations, and incoming documents.';
$lang['peppol_webhook_dedicated'] = 'Dedicated Endpoint';
$lang['peppol_webhook_general'] = 'General Endpoint';
$lang['peppol_webhook_signature'] = 'Signature Header';
$lang['peppol_webhook_events'] = 'Supported Events';
$lang['peppol_webhook_health_help'] = 'Use this endpoint to test webhook connectivity';
$lang['peppol_no_webhook_config'] = 'No webhook configuration available for this provider';
$lang['webhook_security'] = 'Webhook Security';
$lang['webhook_security_help'] = 'All webhooks are verified using HMAC signatures to ensure authenticity and prevent spoofing';
$lang['webhook_testing'] = 'Test Webhook';
$lang['webhook_logs'] = 'Webhook Activity Logs';

// Statistics Dashboard
$lang['statistics'] = 'PEPPOL Usage Statistics';
$lang['statistics_period'] = 'Statistics Period';
$lang['statistics_last_30_days'] = 'Last 30 Days';
$lang['statistics_this_month'] = 'This Month';
$lang['statistics_last_month'] = 'Last Month';
$lang['statistics_this_year'] = 'This Year';

$lang['invoices_sent'] = 'Successfully Transmitted';
$lang['invoices_pending'] = 'Pending Transmission';
$lang['invoices_failed'] = 'Failed Transmissions';
$lang['documents_received'] = 'Documents Received';
$lang['transmission_rate'] = 'Success Rate';
$lang['average_processing_time'] = 'Avg. Processing Time';
$lang['total_data_transferred'] = 'Data Transferred';
$lang['monthly_quota_used'] = 'Monthly Quota Used';

// Health Check
$lang['health_check'] = 'System Health Check';
$lang['health_check_description'] = 'Verify that all PEPPOL components are functioning correctly';
$lang['health_provider_connection'] = 'Provider Connectivity';
$lang['health_webhook_endpoints'] = 'Webhook Endpoints';
$lang['health_certificate_validity'] = 'Certificate Status';
$lang['health_configuration'] = 'Configuration Completeness';
$lang['health_all_good'] = 'All systems operational';
$lang['health_issues_found'] = 'Issues detected - please review';

// ========================================
// HELP & DOCUMENTATION
// ========================================

$lang['peppol_help'] = 'PEPPOL Help & Documentation';
$lang['peppol_help_overview'] = 'PEPPOL Overview';
$lang['peppol_help_setup'] = 'Setup Guide';
$lang['peppol_help_troubleshooting'] = 'Troubleshooting';
$lang['peppol_help_api_reference'] = 'API Reference';
$lang['peppol_help_faq'] = 'Frequently Asked Questions';
$lang['peppol_help_support'] = 'Get Support';

$lang['peppol_documentation_link'] = 'View Full Documentation';
$lang['peppol_provider_documentation'] = 'Provider Documentation';
$lang['peppol_compliance_info'] = 'Compliance Information';
$lang['peppol_best_practices'] = 'Best Practices Guide';

// ========================================
// NOTIFICATION MESSAGES
// ========================================

$lang['peppol_notification_document_sent'] = 'Document Successfully Sent';
$lang['peppol_notification_document_delivered'] = 'Document Delivered to Recipient';
$lang['peppol_notification_document_failed'] = 'Document Transmission Failed';
$lang['peppol_notification_document_received'] = 'New Document Received';
$lang['peppol_notification_system_maintenance'] = 'System Maintenance Scheduled';
$lang['peppol_notification_quota_warning'] = 'Monthly Quota Nearly Exceeded';
$lang['peppol_notification_certificate_expiring'] = 'PEPPOL Certificate Expiring Soon';
$lang['peppol_notification_provider_issue'] = 'Provider Service Issue Detected';

// Email notification subjects
$lang['peppol_email_subject_sent'] = 'PEPPOL Invoice Sent: %s';
$lang['peppol_email_subject_delivered'] = 'PEPPOL Invoice Delivered: %s';
$lang['peppol_email_subject_failed'] = 'PEPPOL Transmission Failed: %s';
$lang['peppol_email_subject_received'] = 'New PEPPOL Document Received';

// ========================================
// MISCELLANEOUS LABELS
// ========================================

$lang['peppol_enabled'] = 'PEPPOL Enabled';
$lang['peppol_disabled'] = 'PEPPOL Disabled';
$lang['peppol_required'] = 'Required for PEPPOL';
$lang['peppol_optional'] = 'Optional';
$lang['peppol_recommended'] = 'Recommended';
$lang['peppol_not_applicable'] = 'Not Applicable';
$lang['peppol_unknown'] = 'Unknown';
$lang['peppol_loading'] = 'Loading PEPPOL data...';
$lang['peppol_saving'] = 'Saving configuration...';
$lang['peppol_testing'] = 'Testing connection...';
$lang['peppol_processing'] = 'Processing document...';
$lang['peppol_validating'] = 'Validating settings...';

// Document types
$lang['peppol_document_type_invoice'] = 'Commercial Invoice';
$lang['peppol_document_type_credit_note'] = 'Credit Note';
$lang['peppol_document_type_debit_note'] = 'Debit Note';
$lang['peppol_document_type_application_response'] = 'Application Response';
$lang['peppol_document_type_unknown'] = 'Unknown Document Type';

// File formats
$lang['peppol_format_ubl'] = 'UBL XML';
$lang['peppol_format_json'] = 'JSON';
$lang['peppol_format_pdf'] = 'PDF Attachment';
$lang['peppol_format_edifact'] = 'UN/EDIFACT';

// ========================================
// SYSTEM & STATUS MESSAGES
// ========================================

// Connection and validation messages
$lang['peppol_settings_saved_with_test_success'] = 'PEPPOL settings saved successfully and connection test passed';
$lang['peppol_settings_saved_with_test_failed'] = 'PEPPOL settings saved but connection test failed';
$lang['peppol_provider_test_success'] = '%s provider connection test successful';
$lang['peppol_provider_test_failed'] = '%s provider connection test failed';

// Bulk operation messages
$lang['peppol_bulk_operation_started'] = 'Bulk PEPPOL operation started for %d documents';
$lang['peppol_bulk_operation_completed'] = 'Bulk operation completed: %d successful, %d failed';
$lang['peppol_no_documents_selected'] = 'No documents selected for bulk operation';

// System maintenance messages
$lang['peppol_maintenance_mode'] = 'PEPPOL module is temporarily in maintenance mode';
$lang['peppol_provider_maintenance'] = 'The selected provider is currently under maintenance';
$lang['peppol_service_unavailable'] = 'PEPPOL service is temporarily unavailable';

// Help and guidance messages
$lang['peppol_setup_incomplete'] = 'PEPPOL setup is incomplete. Please configure all required settings.';
$lang['peppol_test_document_sent'] = 'Test document sent successfully via PEPPOL';
$lang['peppol_production_warning'] = 'Warning: You are in production mode. Documents will be sent to real recipients.';
$lang['peppol_sandbox_notice'] = 'Notice: Running in sandbox mode. No real documents will be transmitted.';

// Integration messages
$lang['peppol_integration_active'] = 'PEPPOL integration is active and ready';
$lang['peppol_integration_disabled'] = 'PEPPOL integration is currently disabled';
$lang['peppol_webhook_health_ok'] = 'All webhook endpoints are responding correctly';
$lang['peppol_webhook_health_issues'] = 'Some webhook endpoints are not responding';

// Performance and monitoring
$lang['peppol_performance_good'] = 'PEPPOL performance is within normal parameters';
$lang['peppol_performance_slow'] = 'PEPPOL response times are slower than usual';
$lang['peppol_quota_status'] = 'Monthly quota: %d of %d documents used (%d%% remaining)';
$lang['peppol_last_successful_transmission'] = 'Last successful transmission: %s';

// ========================================
// LEGAL ENTITY MANAGEMENT
// ========================================

// Legal entity registration
$lang['peppol_legal_entity'] = 'PEPPOL Legal Entity';
$lang['peppol_legal_entity_management'] = 'Legal Entity Management';
$lang['peppol_legal_entity_registration'] = 'Legal Entity Registration';
$lang['peppol_legal_entity_registered'] = 'Legal entity registered successfully with PEPPOL provider';
$lang['peppol_legal_entity_registration_failed'] = 'Failed to register legal entity';
$lang['peppol_legal_entity_updated'] = 'Legal entity information updated successfully';
$lang['peppol_legal_entity_sync_failed'] = 'Failed to synchronize legal entity with provider';

// Legal entity status
$lang['peppol_legal_entity_status'] = 'Registration Status';
$lang['peppol_legal_entity_status_none'] = 'Not Registered';
$lang['peppol_legal_entity_status_pending'] = 'Registration Pending';
$lang['peppol_legal_entity_status_registered'] = 'Registered';
$lang['peppol_legal_entity_status_failed'] = 'Registration Failed';
$lang['peppol_legal_entity_last_sync'] = 'Last Synchronized';

// Legal entity actions
$lang['peppol_register_legal_entity'] = 'Register with PEPPOL';
$lang['peppol_sync_legal_entity'] = 'Sync Entity Data';
$lang['peppol_update_legal_entity'] = 'Update Registration';
$lang['peppol_view_legal_entity'] = 'View Entity Details';

// Provider-specific entity IDs
$lang['peppol_ademico_entity_id'] = 'Ademico Entity ID';
$lang['peppol_unit4_entity_id'] = 'Unit4 Entity ID';
$lang['peppol_recommand_entity_id'] = 'Recommand Entity ID';

// Custom field labels
$lang['peppol_registration_notes'] = 'Registration Notes';
$lang['peppol_last_sync_date'] = 'Last Sync Date';

// Bulk operations
$lang['peppol_bulk_register_legal_entities'] = 'Bulk Register Legal Entities';
$lang['peppol_bulk_registration_complete'] = 'Bulk registration completed: %d successful, %d total, %d errors';
$lang['peppol_select_clients_for_registration'] = 'Select clients to register as legal entities';

// Registration dialog
$lang['peppol_legal_entity_registration_dialog'] = 'Register Legal Entity';
$lang['peppol_select_provider_for_registration'] = 'Select PEPPOL provider for registration';
$lang['peppol_confirm_legal_entity_registration'] = 'Register this client as a legal entity with %s?';
$lang['peppol_legal_entity_data_will_be_mapped'] = 'Client company information will be automatically mapped to PEPPOL legal entity format';

// Validation and errors
$lang['peppol_legal_entity_missing_company_info'] = 'Client must have company name and address for PEPPOL registration';
$lang['peppol_legal_entity_missing_vat'] = 'VAT number is required for legal entity registration';
$lang['peppol_legal_entity_invalid_address'] = 'Complete address information is required for registration';
$lang['peppol_legal_entity_provider_not_configured'] = 'PEPPOL provider is not properly configured';

// Help text
$lang['peppol_legal_entity_help'] = 'Register your clients as legal entities with PEPPOL providers to enable electronic document exchange';
$lang['peppol_legal_entity_sync_help'] = 'Synchronize client information with registered PEPPOL legal entities to keep data up-to-date';

// Auto registration settings
$lang['peppol_auto_register_legal_entities'] = 'Auto-register Legal Entities';
$lang['peppol_auto_register_legal_entities_help'] = 'Automatically register new clients as legal entities with the active PEPPOL provider when they are created';
$lang['peppol_auto_sync_legal_entities'] = 'Auto-sync Legal Entities';
$lang['peppol_auto_sync_legal_entities_help'] = 'Automatically synchronize legal entity data when client information is updated (only for clients already registered as legal entities)';

// Client profile UI elements
$lang['peppol_provider_registration_status'] = 'Provider Registration Status';
$lang['peppol_active_provider'] = 'Active Provider';
$lang['peppol_confirm_legal_entity_sync'] = 'Synchronize legal entity data with PEPPOL providers? This will update their records with current client information.';

// End of PEPPOL language file