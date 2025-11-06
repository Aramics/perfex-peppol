<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        // Add missing UBL content column if not exists
        if (!$this->db->field_exists('ubl_content', db_prefix() . 'peppol_invoices')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'peppol_invoices` ADD `ubl_content` longtext DEFAULT NULL AFTER `status`');
        }

        // Add index for better performance
        if (!$this->db->query("SHOW INDEX FROM `" . db_prefix() . "peppol_invoices` WHERE Key_name = 'idx_invoice_provider'")->num_rows()) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'peppol_invoices` ADD INDEX `idx_invoice_provider` (`invoice_id`, `provider`)');
        }

        // Add index for logs table
        if (!$this->db->query("SHOW INDEX FROM `" . db_prefix() . "peppol_logs` WHERE Key_name = 'idx_created_at'")->num_rows()) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'peppol_logs` ADD INDEX `idx_created_at` (`created_at`)');
        }

        // Add index for peppol_document_id field (field already exists from install.php)
        if (!$this->db->query("SHOW INDEX FROM `" . db_prefix() . "peppol_invoices` WHERE Key_name = 'idx_peppol_document_id'")->num_rows()) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'peppol_invoices` ADD INDEX `idx_peppol_document_id` (`peppol_document_id`)');
        }
    }

    public function down()
    {
        // Remove indexes (optional, usually not needed)
    }
}