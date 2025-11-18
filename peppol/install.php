<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Module Installation
 */

if (!$CI->db->table_exists(db_prefix() . 'peppol_documents')) {
    $CI->db->query('
        CREATE TABLE `' . db_prefix() . 'peppol_documents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `document_type` varchar(20) NOT NULL DEFAULT "invoice",
            `document_id` int(11) NOT NULL,
            `status` varchar(50) NOT NULL DEFAULT "pending",
            `peppol_document_id` varchar(255) DEFAULT NULL,
            `ubl_content` longtext DEFAULT NULL,
            `sent_at` datetime DEFAULT NULL,
            `received_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `document_type` (`document_type`),
            KEY `document_id` (`document_id`),
            KEY `status` (`status`),
            UNIQUE KEY `unique_document` (`document_type`, `document_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ');
}

if (!$CI->db->table_exists(db_prefix() . 'peppol_logs')) {
    $CI->db->query('
        CREATE TABLE `' . db_prefix() . 'peppol_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `type` varchar(50) NOT NULL,
            `document_type` varchar(20) DEFAULT NULL,
            `document_id` int(11) DEFAULT NULL,
            `message` text NOT NULL,
            `data` text DEFAULT NULL,
            `staff_id` int(11) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `type` (`type`),
            KEY `document_type` (`document_type`),
            KEY `document_id` (`document_id`),
            KEY `staff_id` (`staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ');
}

// Create PEPPOL custom fields for clients (preferred approach)
peppol_create_custom_fields();

// Add default options
add_option('peppol_active_provider', 'manual');
add_option('peppol_environment', 'sandbox');
add_option('peppol_company_identifier', '');
add_option('peppol_company_scheme', '0208');
add_option('peppol_test_mode', '1');
add_option('peppol_auto_send', '0');
add_option('peppol_webhook_url', '');

/**
 * Create PEPPOL custom fields for clients
 */
function peppol_create_custom_fields()
{
    $CI = &get_instance();

    // Customer PEPPOL fields
    $CI->db->where('fieldto', 'customers');
    $CI->db->where('slug', 'customers_peppol_identifier');
    $existing_identifier = $CI->db->get(db_prefix() . 'customfields')->row();

    if (!$existing_identifier) {
        // Add PEPPOL Identifier field
        $CI->db->query("INSERT INTO `" . db_prefix() . "customfields` (`fieldto`,`name`, `type`, `options`, `default_value`, `field_order`, `bs_column`, `slug`) VALUES ('customers', 'PEPPOL Identifier', 'input','','','1','12','customers_peppol_identifier');");
    }

    $CI->db->where('fieldto', 'customers');
    $CI->db->where('slug', 'customers_peppol_scheme');
    $existing_scheme = $CI->db->get(db_prefix() . 'customfields')->row();

    if (!$existing_scheme) {
        // Add PEPPOL Scheme field
        $CI->db->query("INSERT INTO `" . db_prefix() . "customfields` (`fieldto`,`name`, `type`, `options`, `default_value`, `field_order`, `bs_column`, `slug`) VALUES ('customers', 'PEPPOL Scheme', 'input','','0208','2','12','customers_peppol_scheme');");
    }

    // Credit Note PEPPOL Status field (for display only)
    $CI->db->where('fieldto', 'credit_notes');
    $CI->db->where('slug', 'credit_notes_peppol_status');
    $existing_status = $CI->db->get(db_prefix() . 'customfields')->row();

    if (!$existing_status) {
        $CI->db->query("INSERT INTO `" . db_prefix() . "customfields` 
        (`fieldto`,`name`, `type`, `options`, `default_value`, `field_order`, `bs_column`, `slug`, `show_on_table`, `show_on_client_portal`) VALUES 
        ('credit_note', 'PEPPOL Status', 'input','','Not Sent','2','12','credit_notes_peppol_status','1','1');");
    }
}