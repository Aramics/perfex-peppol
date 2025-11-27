<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration to add provider_document_transmission_id column to peppol_documents table
 * This column stores the transmission ID from notifications for easy lookup
 */
class Migration_Add_transmission_id_to_peppol_documents
{
    /**
     * Run the migration
     */
    public function up()
    {
        $CI = &get_instance();
        
        // Check if provider_document_transmission_id column already exists
        if (!$CI->db->field_exists('provider_document_transmission_id', db_prefix() . 'peppol_documents')) {
            // Add provider_document_transmission_id column
            $CI->db->query('
                ALTER TABLE `' . db_prefix() . 'peppol_documents` 
                ADD COLUMN `provider_document_transmission_id` varchar(150) DEFAULT NULL 
                COMMENT "Transmission ID from notification for easy lookup"
                AFTER `provider_document_id`
            ');
            
            log_activity('PEPPOL: Added provider_document_transmission_id column to peppol_documents table');
        }
    }

    /**
     * Rollback the migration
     */
    public function down()
    {
        $CI = &get_instance();
        
        // Check if provider_document_transmission_id column exists before dropping
        if ($CI->db->field_exists('provider_document_transmission_id', db_prefix() . 'peppol_documents')) {
            // Remove provider_document_transmission_id column
            $CI->db->query('
                ALTER TABLE `' . db_prefix() . 'peppol_documents` 
                DROP COLUMN `provider_document_transmission_id`
            ');
            
            log_activity('PEPPOL: Removed provider_document_transmission_id column from peppol_documents table');
        }
    }
}