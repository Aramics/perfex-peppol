<?php
/**
 * Simple migration runner for PEPPOL module
 * Access this file via browser to run pending migrations
 */

// Basic security check
if (!defined('BASEPATH')) {
    define('BASEPATH', 1);
}

// Include CodeIgniter framework files
$system_path = '../../system';
$application_folder = '../../application';

require_once('../../index.php');

$CI = &get_instance();

echo "<h2>PEPPOL Migration Runner</h2>\n";
echo "<pre>\n";

try {
    // Check and run expense_id migration
    if (!$CI->db->field_exists('expense_id', db_prefix() . 'peppol_documents')) {
        echo "Running migration: Add expense_id column...\n";
        require_once __DIR__ . '/migrations/001_add_expense_id_to_peppol_documents.php';
        
        $migration = new Migration_Add_expense_id_to_peppol_documents();
        $migration->up();
        echo "✓ Successfully added expense_id column\n\n";
    } else {
        echo "✓ expense_id column already exists\n\n";
    }


    echo "All migrations completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href=\"" . admin_url('peppol/documents') . "\">← Back to PEPPOL Documents</a></p>\n";
?>