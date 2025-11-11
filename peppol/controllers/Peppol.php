<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(PEPPOL_MODULE_NAME . '/peppol_model');
        $this->load->library(PEPPOL_MODULE_NAME . '/peppol_service');
    }

    /**
     * Main PEPPOL invoices view
     */
    public function index()
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $data['title'] = _l('peppol_invoices');
        $this->load->view('manage', $data);
    }

    /**
     * PEPPOL invoices table data
     */
    public function table()
    {
        if (!staff_can('view', 'peppol')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path(PEPPOL_MODULE_NAME, 'tables/invoices'));
    }

    /**
     * Send invoice via PEPPOL
     */
    public function send($invoice_id)
    {
        if (!staff_can('create', 'peppol')) {
            access_denied('peppol');
        }

        $response = $this->peppol_service->send_invoice($invoice_id);

        if ($response['success']) {
            set_alert('success', _l('peppol_invoice_sent_successfully'));
        } else {
            set_alert('danger', _l('peppol_invoice_send_failed') . ': ' . $response['message']);
        }

        redirect(admin_url('invoices/list_invoices/' . $invoice_id));
    }

    /**
     * Resend failed invoice
     */
    public function resend($peppol_invoice_id)
    {
        if (!staff_can('create', 'peppol')) {
            access_denied('peppol');
        }

        $peppol_invoice = $this->peppol_model->get_peppol_invoice($peppol_invoice_id);

        if (!$peppol_invoice) {
            show_404();
        }

        $response = $this->peppol_service->send_invoice($peppol_invoice->invoice_id);

        if ($response['success']) {
            set_alert('success', _l('peppol_invoice_sent_successfully'));
        } else {
            set_alert('danger', _l('peppol_invoice_send_failed') . ': ' . $response['message']);
        }

        redirect(admin_url('peppol'));
    }

    /**
     * View UBL content
     */
    public function view_ubl($peppol_invoice_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $peppol_invoice = $this->peppol_model->get_peppol_invoice($peppol_invoice_id);

        if (!$peppol_invoice || empty($peppol_invoice->ubl_content)) {
            show_404();
        }

        header('Content-Type: application/xml');
        echo $peppol_invoice->ubl_content;
    }

    /**
     * Download UBL file
     */
    public function download_ubl($peppol_invoice_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $peppol_invoice = $this->peppol_model->get_peppol_invoice($peppol_invoice_id);

        if (!$peppol_invoice || empty($peppol_invoice->ubl_content)) {
            show_404();
        }

        $filename = 'invoice_' . $peppol_invoice->invoice_id . '_ubl.xml';

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $peppol_invoice->ubl_content;
    }

    /**
     * PEPPOL logs view
     */
    public function logs()
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $data['title'] = _l('peppol_logs');
        $this->load->view('logs', $data);
    }

    /**
     * PEPPOL logs table data
     */
    public function logs_table()
    {
        if (!staff_can('view', 'peppol')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path(PEPPOL_MODULE_NAME, 'tables/logs'));
    }

    /**
     * Test provider connection
     */
    public function test_connection()
    {
        if (!staff_can('view', 'peppol')) {
            ajax_access_denied();
        }

        $provider = $this->input->post('provider');
        $environment = $this->input->post('environment');

        $result = $this->peppol_service->test_connection($provider, $environment);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Run automated tests via UI
     */
    public function run_tests()
    {
        if (!staff_can('view', 'peppol')) {
            ajax_access_denied();
        }

        // Get parameters
        $provider = $this->input->get_post('provider') ?: get_option('peppol_active_provider', 'ademico');
        $test_type = $this->input->get_post('test_type') ?: 'legal_entity';
        $action = $this->input->get_post('action') ?: 'start';

        // Set content type for streaming output
        header('Content-Type: text/plain');
        header('Cache-Control: no-cache');
        
        // Start output buffering to send data as it's generated
        ob_implicit_flush(true);
        ob_end_flush();

        try {
            switch ($action) {
                case 'start':
                    $this->execute_test_suite($provider, $test_type);
                    break;
                case 'status':
                    $this->get_test_status();
                    break;
                default:
                    echo "❌ Invalid action: {$action}\n";
                    break;
            }
        } catch (Exception $e) {
            echo "❌ Error running tests: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Execute the test suite
     */
    private function execute_test_suite($provider, $test_type)
    {
        echo "🧪 Starting PEPPOL Test Suite\n";
        echo "Provider: {$provider}\n";
        echo "Test Type: {$test_type}\n";
        echo str_repeat("=", 50) . "\n";
        
        // Include test classes
        $test_base_path = FCPATH . 'modules/' . PEPPOL_MODULE_NAME . '/tests/providers/';
        
        if (!file_exists($test_base_path . 'BaseProviderTest.php')) {
            echo "❌ Test framework not found\n";
            return;
        }
        
        require_once($test_base_path . 'BaseProviderTest.php');
        
        try {
            switch ($test_type) {
                case 'legal_entity':
                    $this->run_legal_entity_tests($provider, $test_base_path);
                    break;
                case 'document':
                    $this->run_document_tests($provider, $test_base_path);
                    break;
                case 'all':
                    $this->run_all_tests($provider, $test_base_path);
                    break;
                default:
                    echo "❌ Unknown test type: {$test_type}\n";
                    return;
            }
            
            echo "\n🎉 Test execution completed!\n";
            
        } catch (Exception $e) {
            echo "\n💥 Test execution failed: " . $e->getMessage() . "\n";
            
            // Log the error for debugging
            log_message('error', 'PEPPOL Test Suite Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run legal entity tests
     */
    private function run_legal_entity_tests($provider, $test_base_path)
    {
        $test_file = $test_base_path . 'LegalEntityTest.php';
        
        if (!file_exists($test_file)) {
            echo "❌ LegalEntityTest.php not found\n";
            return;
        }
        
        require_once($test_file);
        
        echo "🔍 Running Legal Entity Tests for {$provider}...\n";
        
        $test = new LegalEntityTest($provider);
        $test->run_all_tests();
    }
    
    /**
     * Run document tests
     */
    private function run_document_tests($provider, $test_base_path)
    {
        $test_file = $test_base_path . 'DocumentTest.php';
        
        if (!file_exists($test_file)) {
            echo "❌ DocumentTest.php not found\n";
            return;
        }
        
        require_once($test_file);
        
        echo "📄 Running Document Tests for {$provider}...\n";
        
        $test = new DocumentTest($provider);
        $test->run_all_tests();
    }
    
    /**
     * Run all tests
     */
    private function run_all_tests($provider, $test_base_path)
    {
        echo "🚀 Running All Tests for {$provider}...\n\n";
        
        // Run legal entity tests
        $this->run_legal_entity_tests($provider, $test_base_path);
        
        echo "\n" . str_repeat("-", 50) . "\n";
        
        // Run document tests
        $this->run_document_tests($provider, $test_base_path);
    }
    
    /**
     * Get test execution status (for future use with async execution)
     */
    private function get_test_status()
    {
        // This could be expanded for async test execution
        echo "Status: No running tests\n";
    }



    /**
     * Received documents view
     */
    public function received()
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $data['title'] = _l('peppol_received_documents');
        $this->load->view('received', $data);
    }

    /**
     * Received documents table data
     */
    public function received_table()
    {
        if (!staff_can('view', 'peppol')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path(PEPPOL_MODULE_NAME, 'tables/received'));
    }

    /**
     * Process received document
     */
    public function process_received($document_id)
    {
        if (!staff_can('create', 'peppol')) {
            access_denied('peppol');
        }

        $result = $this->peppol_service->process_received_document($document_id);

        if ($result['success']) {
            set_alert('success', _l('peppol_document_processed'));
        } else {
            set_alert('danger', _l('peppol_document_processing_failed') . ': ' . $result['message']);
        }

        redirect(admin_url('peppol/received'));
    }

    /**
     * View received document content
     */
    public function view_received($document_id)
    {
        if (!staff_can('view', 'peppol')) {
            access_denied('peppol');
        }

        $document = $this->peppol_model->get_received_document($document_id);

        if (!$document) {
            show_404();
        }

        header('Content-Type: application/xml');
        echo $document->document_content;
    }

    // ========================================
    // LEGAL ENTITY MANAGEMENT
    // ========================================

    /**
     * Register client as legal entity with PEPPOL provider
     */
    public function register_legal_entity($client_id = null)
    {
        if (!staff_can('create', 'peppol')) {
            ajax_access_denied();
        }

        if (!$client_id) {
            $client_id = $this->input->post('client_id');
        }

        $provider = $this->input->post('provider') ?: null;

        $result = $this->peppol_service->create_or_update_client_legal_entity($client_id, $provider);

        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }

        if ($result['success']) {
            set_alert('success', _l('peppol_legal_entity_registered'));
        } else {
            set_alert('danger', _l('peppol_legal_entity_registration_failed') . ': ' . $result['message']);
        }

        redirect(admin_url('clients/client/' . $client_id));
    }

    /**
     * Get legal entity registration status for a client
     */
    public function get_legal_entity_status($client_id)
    {
        if (!staff_can('view', 'peppol')) {
            ajax_access_denied();
        }

        $provider = $this->input->get('provider');
        $status = $this->peppol_service->get_client_legal_entity_status($client_id, $provider);

        echo json_encode($status);
    }

    /**
     * Sync legal entity data with provider
     */
    public function sync_legal_entity($client_id)
    {
        if (!staff_can('edit', 'peppol')) {
            ajax_access_denied();
        }

        $provider = $this->input->post('provider');
        if (!$provider) {
            echo json_encode(['success' => false, 'message' => 'Provider not specified']);
            return;
        }

        $result = $this->peppol_service->sync_client_legal_entity($client_id, $provider);

        echo json_encode($result);
    }

    /**
     * Bulk register clients as legal entities
     */
    public function bulk_register_legal_entities()
    {
        if (!staff_can('create', 'peppol')) {
            ajax_access_denied();
        }

        $client_ids = $this->input->post('client_ids');
        $provider = $this->input->post('provider');

        if (!$client_ids || !is_array($client_ids)) {
            echo json_encode(['success' => false, 'message' => 'No clients selected']);
            return;
        }

        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($client_ids as $client_id) {
            $result = $this->peppol_service->create_or_update_client_legal_entity($client_id, $provider);
            $results[$client_id] = $result;
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        echo json_encode([
            'success' => $error_count === 0,
            'message' => sprintf(
                _l('peppol_bulk_registration_complete'),
                $success_count,
                count($client_ids),
                $error_count
            ),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results
        ]);
    }

    /**
     * Run database migrations for PEPPOL module
     */
    public function migrate_database()
    {
        if (!is_admin()) {
            access_denied('admin');
        }

        $this->load->dbforge();
        
        try {
            // Add client_id column to existing peppol_logs table if it doesn't exist
            if (!$this->db->field_exists('client_id', db_prefix() . 'peppol_logs')) {
                $this->db->query('ALTER TABLE `' . db_prefix() . 'peppol_logs` ADD `client_id` int(11) DEFAULT NULL AFTER `invoice_id`');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'peppol_logs` ADD KEY `client_id` (`client_id`)');
                echo "✅ Added client_id column to peppol_logs table<br>";
            } else {
                echo "✅ client_id column already exists in peppol_logs table<br>";
            }
            
            // Add missing test credential options
            $test_options = [
                'peppol_ademico_oauth2_client_identifier_test',
                'peppol_ademico_oauth2_client_secret_test',
                'peppol_unit4_username_test',
                'peppol_unit4_password_test',
                'peppol_recommand_api_key_test',
                'peppol_recommand_company_id_test',
                'peppol_auto_register_legal_entities',
                'peppol_auto_sync_legal_entities'
            ];
            
            foreach ($test_options as $option) {
                if (!get_option($option)) {
                    add_option($option, '');
                    echo "✅ Added option: $option<br>";
                } else {
                    echo "✅ Option already exists: $option<br>";
                }
            }
            
            echo "<br>🎉 Database migration completed successfully!";
            
        } catch (Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage();
        }
    }

    /**
     * Bulk send invoices via PEPPOL
     */
    public function bulk_send()
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $invoice_ids = $this->input->post('invoice_ids');
        
        if (empty($invoice_ids) || !is_array($invoice_ids)) {
            echo json_encode([
                'success' => false,
                'message' => _l('peppol_no_invoices_selected')
            ]);
            return;
        }

        // Initialize progress tracking
        $total = count($invoice_ids);
        $success = 0;
        $errors = 0;
        $error_messages = [];

        // Process each invoice
        foreach ($invoice_ids as $invoice_id) {
            try {
                $result = $this->peppol_service->send_invoice($invoice_id);
                
                if ($result['success']) {
                    $success++;
                } else {
                    $errors++;
                    $error_messages[] = "Invoice #{$invoice_id}: " . $result['message'];
                }
            } catch (Exception $e) {
                $errors++;
                $error_messages[] = "Invoice #{$invoice_id}: " . $e->getMessage();
            }
        }

        // Prepare response
        $response = [
            'success' => $success > 0,
            'progress' => [
                'total' => $total,
                'completed' => $total,
                'success' => $success,
                'errors' => $errors
            ]
        ];

        if ($errors === 0) {
            $response['message'] = _l('peppol_bulk_send_completed');
        } elseif ($success > 0) {
            $response['message'] = _l('peppol_bulk_send_partial');
        } else {
            $response['message'] = _l('peppol_bulk_operation_failed');
            $response['success'] = false;
        }

        if (!empty($error_messages)) {
            $response['errors'] = $error_messages;
        }

        echo json_encode($response);
    }

    /**
     * Bulk download UBL files
     */
    public function bulk_download_ubl()
    {
        if (!staff_can('view', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $invoice_ids = $this->input->post('invoice_ids');
        
        if (empty($invoice_ids) || !is_array($invoice_ids)) {
            set_alert('danger', _l('peppol_no_invoices_selected'));
            redirect(admin_url('invoices'));
            return;
        }

        $this->load->library('zip');
        $zip_filename = 'peppol_ubl_files_' . date('Y-m-d_H-i-s') . '.zip';
        
        $temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'peppol_bulk_' . time();
        if (!mkdir($temp_dir, 0755, true)) {
            set_alert('danger', _l('peppol_bulk_operation_failed'));
            redirect(admin_url('invoices'));
            return;
        }

        $files_added = 0;
        
        try {
            foreach ($invoice_ids as $invoice_id) {
                // Get invoice details
                $this->load->model('invoices_model');
                $invoice = $this->invoices_model->get($invoice_id);
                
                if (!$invoice) {
                    continue;
                }

                // Generate UBL content
                try {
                    $this->load->library(PEPPOL_MODULE_NAME . '/ubl_generator');
                    $ubl_content = $this->ubl_generator->generate_invoice_ubl($invoice);
                    
                    // Create filename
                    $filename = sprintf('invoice_%s_%s.xml', 
                        $invoice->number, 
                        date('Y-m-d', strtotime($invoice->date))
                    );
                    
                    // Save UBL to temp file
                    $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $filename;
                    if (file_put_contents($temp_file, $ubl_content)) {
                        $this->zip->add_data($filename, $ubl_content);
                        $files_added++;
                    }
                    
                } catch (Exception $e) {
                    // Log error but continue with other files
                    log_message('error', 'PEPPOL UBL generation failed for invoice ' . $invoice_id . ': ' . $e->getMessage());
                }
            }

            if ($files_added === 0) {
                set_alert('danger', _l('peppol_bulk_operation_failed'));
                redirect(admin_url('invoices'));
                return;
            }

            // Create and download ZIP file
            $zip_data = $this->zip->get_zip();
            
            if (empty($zip_data)) {
                set_alert('danger', _l('peppol_bulk_operation_failed'));
                redirect(admin_url('invoices'));
                return;
            }

            // Set headers for download
            $this->output
                ->set_content_type('application/zip')
                ->set_header('Content-Disposition: attachment; filename="' . $zip_filename . '"')
                ->set_header('Content-Length: ' . strlen($zip_data))
                ->set_output($zip_data);

        } finally {
            // Clean up temp directory
            if (is_dir($temp_dir)) {
                array_map('unlink', glob($temp_dir . DIRECTORY_SEPARATOR . '*'));
                rmdir($temp_dir);
            }
        }
    }
}