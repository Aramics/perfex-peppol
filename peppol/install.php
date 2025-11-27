<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Module Installation
 */

$peppol_docs_table = db_prefix() . 'peppol_documents';

if (!$CI->db->table_exists($peppol_docs_table)) {
    $CI->db->query('
        CREATE TABLE `' . db_prefix() . 'peppol_documents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `document_type` varchar(20) NOT NULL DEFAULT "invoice",
            `local_reference_id` int(11) DEFAULT NULL,
            `status` varchar(50) NOT NULL DEFAULT "QUEUED" COMMENT "API status codes: QUEUED,SENT,SEND_FAILED,TECHNICAL_ACCEPTANCE,FULLY_PAID,ACCEPTED,REJECTED,received. Direction: local_reference_id NULL=inbound, NOT NULL=outbound",
            `provider` varchar(100) DEFAULT NULL,
            `provider_document_id` varchar(150) DEFAULT NULL,
            `provider_document_transmission_id` varchar(150) DEFAULT NULL COMMENT "Transmission ID from notification for easy lookup",
            `provider_metadata` text DEFAULT NULL,
            `expense_id` int(11) DEFAULT NULL,
            `sent_at` datetime DEFAULT NULL,
            `received_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `document_type` (`document_type`),
            KEY `local_reference_id` (`local_reference_id`),
            KEY `expense_id` (`expense_id`),
            KEY `status` (`status`),
            KEY `sent_received` (`sent_at`, `received_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ');
}


if (!$CI->db->table_exists(db_prefix() . 'peppol_logs')) {
    $CI->db->query('
        CREATE TABLE `' . db_prefix() . 'peppol_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `type` varchar(50) NOT NULL,
            `document_type` varchar(20) DEFAULT NULL,
            `local_reference_id` int(11) DEFAULT NULL,
            `message` text NOT NULL,
            `data` text DEFAULT NULL,
            `staff_id` int(11) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ');
} else {
    // Table exists, check if we need to run migrations
    peppol_run_migrations();
}

// Create PEPPOL custom fields for clients (preferred approach)
peppol_create_custom_fields();

// Add default options
add_option('peppol_company_identifier', '');
add_option('peppol_company_scheme', '0208');

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

/**
 * Run PEPPOL migrations for existing installations
 */
function peppol_run_migrations()
{
    $CI = &get_instance();

    // Check if expense_id column exists, if not run migration
    if (!$CI->db->field_exists('expense_id', db_prefix() . 'peppol_documents')) {
        // Load the migration class
        require_once __DIR__ . '/migrations/001_add_expense_id_to_peppol_documents.php';

        $migration = new Migration_Add_expense_id_to_peppol_documents();

        try {
            $migration->up();
            log_activity('PEPPOL: Successfully ran migration to add expense_id column');
        } catch (Exception $e) {
            log_activity('PEPPOL: Migration failed - ' . $e->getMessage());
        }
    }

    // Check if provider_document_transmission_id column exists, if not run migration
    if (!$CI->db->field_exists('provider_document_transmission_id', db_prefix() . 'peppol_documents')) {
        // Load the migration class
        require_once __DIR__ . '/migrations/003_add_transmission_id_to_peppol_documents.php';

        $migration = new Migration_Add_transmission_id_to_peppol_documents();

        try {
            $migration->up();
            log_activity('PEPPOL: Successfully ran migration to add provider_document_transmission_id column');
        } catch (Exception $e) {
            log_activity('PEPPOL: Migration failed - ' . $e->getMessage());
        }
    }
}