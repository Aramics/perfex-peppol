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

// Add PEPPOL identifier fields to clients table if not exists
if (!$CI->db->field_exists('peppol_identifier', db_prefix() . 'clients')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `peppol_identifier` varchar(100) DEFAULT NULL');
}

if (!$CI->db->field_exists('peppol_scheme', db_prefix() . 'clients')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `peppol_scheme` varchar(20) DEFAULT "0208"');
}

// Add default options
add_option('peppol_active_provider', 'manual');
add_option('peppol_environment', 'sandbox');
add_option('peppol_company_identifier', '');
add_option('peppol_company_scheme', '0208');
add_option('peppol_test_mode', '1');
add_option('peppol_auto_send', '0');
add_option('peppol_webhook_url', '');