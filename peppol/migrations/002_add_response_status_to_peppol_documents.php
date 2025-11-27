<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration to add response_status column to peppol_documents table
 * This column stores the PEPPOL response status codes (PD, AP, AB, RE, etc.)
 */
class Migration_Add_response_status_to_peppol_documents
{
    /**
     * Run the migration
     */
    public function up()
    {
        $CI = &get_instance();
        
        // Check if response_status column already exists
        if (!$CI->db->field_exists('response_status', db_prefix() . 'peppol_documents')) {
            // Add response_status column
            $CI->db->query('
                ALTER TABLE `' . db_prefix() . 'peppol_documents` 
                ADD COLUMN `response_status` varchar(10) DEFAULT NULL 
                COMMENT "PEPPOL response status codes (PD=Paid, AP=Accepted, AB=Acknowledged, RE=Rejected, etc.)"
                AFTER `status`
            ');
            
            log_activity('PEPPOL: Added response_status column to peppol_documents table');
        }
    }

    /**
     * Rollback the migration
     */
    public function down()
    {
        $CI = &get_instance();
        
        // Check if response_status column exists before dropping
        if ($CI->db->field_exists('response_status', db_prefix() . 'peppol_documents')) {
            // Remove response_status column
            $CI->db->query('
                ALTER TABLE `' . db_prefix() . 'peppol_documents` 
                DROP COLUMN `response_status`
            ');
            
            log_activity('PEPPOL: Removed response_status column from peppol_documents table');
        }
    }
}