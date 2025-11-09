<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'peppol_invoices')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'peppol_invoices` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `invoice_id` int(11) NOT NULL,
        `peppol_document_id` varchar(191) DEFAULT NULL,
        `provider` varchar(50) NOT NULL,
        `status` varchar(50) NOT NULL DEFAULT "pending",
        `ubl_content` longtext DEFAULT NULL,
        `response_data` text DEFAULT NULL,
        `error_message` text DEFAULT NULL,
        `sent_at` datetime DEFAULT NULL,
        `received_at` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `invoice_id` (`invoice_id`),
        KEY `status` (`status`),
        UNIQUE KEY `unique_invoice_provider` (`invoice_id`, `provider`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'peppol_received_documents')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'peppol_received_documents` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `document_id` varchar(191) NOT NULL,
        `provider` varchar(50) NOT NULL,
        `document_type` varchar(50) NOT NULL DEFAULT "invoice",
        `sender_identifier` varchar(191) DEFAULT NULL,
        `receiver_identifier` varchar(191) DEFAULT NULL,
        `document_content` longtext NOT NULL,
        `processed` tinyint(1) NOT NULL DEFAULT 0,
        `invoice_id` int(11) DEFAULT NULL,
        `error_message` text DEFAULT NULL,
        `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `processed_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `document_id` (`document_id`),
        KEY `processed` (`processed`),
        KEY `invoice_id` (`invoice_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'peppol_logs')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'peppol_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `invoice_id` int(11) DEFAULT NULL,
        `client_id` int(11) DEFAULT NULL,
        `document_id` varchar(191) DEFAULT NULL,
        `provider` varchar(50) NOT NULL,
        `action` varchar(100) NOT NULL,
        `status` varchar(50) NOT NULL,
        `message` text DEFAULT NULL,
        `request_data` text DEFAULT NULL,
        `response_data` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `invoice_id` (`invoice_id`),
        KEY `client_id` (`client_id`),
        KEY `action` (`action`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Add client_id column to existing peppol_logs table if it doesn't exist
if (!$CI->db->field_exists('client_id', db_prefix() . 'peppol_logs')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'peppol_logs` ADD `client_id` int(11) DEFAULT NULL AFTER `invoice_id`');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'peppol_logs` ADD KEY `client_id` (`client_id`)');
}

// Add default options
add_option('peppol_active_provider', 'ademico');
add_option('peppol_environment', 'sandbox');
add_option('peppol_auto_send_enabled', '0');
add_option('peppol_auto_process_received', '1');
add_option('peppol_auto_register_legal_entities', '0');
add_option('peppol_auto_sync_legal_entities', '0');

// Ademico provider default settings - OAuth2 only
add_option('peppol_ademico_oauth2_client_identifier', '');
add_option('peppol_ademico_oauth2_client_secret', '');
add_option('peppol_ademico_oauth2_client_identifier_test', '');
add_option('peppol_ademico_oauth2_client_secret_test', '');
// Removed company_id as it's not required for Ademico OAuth2 provider

// Unit4 provider default settings
add_option('peppol_unit4_username', '');
add_option('peppol_unit4_password', '');
add_option('peppol_unit4_username_test', '');
add_option('peppol_unit4_password_test', '');
add_option('peppol_unit4_endpoint_url', 'https://ap.unit4.com');
add_option('peppol_unit4_sandbox_endpoint', 'https://test-ap.unit4.com');

// Recommand provider default settings
add_option('peppol_recommand_api_key', '');
add_option('peppol_recommand_company_id', '');
add_option('peppol_recommand_api_key_test', '');
add_option('peppol_recommand_company_id_test', '');
add_option('peppol_recommand_endpoint_url', 'https://peppol.recommand.eu/api');
add_option('peppol_recommand_sandbox_endpoint', 'https://sandbox-peppol.recommand.eu/api');

// Company PEPPOL identifier settings
add_option('peppol_company_identifier', '');
add_option('peppol_company_scheme', '0208'); // Default CBE scheme
add_option('peppol_company_country', get_option('company_country'));

// Create PEPPOL custom fields using the service method
$CI->load->library('peppol/peppol_service');
$CI->peppol_service->ensure_peppol_custom_fields_exist();