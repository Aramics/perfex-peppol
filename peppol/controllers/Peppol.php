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
     * Get statistics for bulk actions
     */
    public function bulk_action_stats()
    {
        if (!staff_can('view', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $stats = [];

        switch ($action) {
            case 'send_unsent':
                // Count invoices that don't have PEPPOL records
                $this->db->select('COUNT(i.id) as count');
                $this->db->from(db_prefix() . 'invoices i');
                $this->db->join(db_prefix() . 'peppol_invoices pi', 'pi.invoice_id = i.id', 'left');
                $this->db->where('pi.id IS NULL');
                $this->db->where('i.status', 2); // Only sent invoices
                $result = $this->db->get()->row();
                
                $stats = [
                    'action' => 'send_unsent',
                    'count' => (int)$result->count,
                    'description' => 'Send all unsent invoices via PEPPOL',
                    'operation_type' => 'send'
                ];
                break;

            case 'download_sent':
                // Count invoices with 'sent' or 'delivered' status
                $this->db->select('COUNT(*) as count');
                $this->db->from(db_prefix() . 'peppol_invoices');
                $this->db->where_in('status', ['sent', 'delivered']);
                $result = $this->db->get()->row();
                
                $stats = [
                    'action' => 'download_sent',
                    'count' => (int)$result->count,
                    'description' => 'Download UBL files for all sent invoices',
                    'operation_type' => 'download'
                ];
                break;

            case 'retry_failed':
                // Count invoices with 'failed' status
                $this->db->select('COUNT(*) as count');
                $this->db->from(db_prefix() . 'peppol_invoices');
                $this->db->where('status', 'failed');
                $result = $this->db->get()->row();
                
                $stats = [
                    'action' => 'retry_failed',
                    'count' => (int)$result->count,
                    'description' => 'Retry sending all failed invoices',
                    'operation_type' => 'send'
                ];
                break;

            default:
                $stats = [
                    'action' => 'unknown',
                    'count' => 0,
                    'description' => 'Unknown action',
                    'operation_type' => 'unknown'
                ];
        }

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Bulk send invoices via PEPPOL
     */
    public function bulk_send()
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $invoice_ids = [];

        // Get invoice IDs based on action
        switch ($action) {
            case 'send_unsent':
                // Get invoices without PEPPOL records
                $this->db->select('i.id');
                $this->db->from(db_prefix() . 'invoices i');
                $this->db->join(db_prefix() . 'peppol_invoices pi', 'pi.invoice_id = i.id', 'left');
                $this->db->where('pi.id IS NULL');
                $this->db->where('i.status', 2); // Only sent invoices
                $results = $this->db->get()->result();
                $invoice_ids = array_column($results, 'id');
                break;

            case 'retry_failed':
                // Get failed PEPPOL invoices
                $this->db->select('invoice_id');
                $this->db->from(db_prefix() . 'peppol_invoices');
                $this->db->where('status', 'failed');
                $results = $this->db->get()->result();
                $invoice_ids = array_column($results, 'invoice_id');
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action specified'
                ]);
                return;
        }

        if (empty($invoice_ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'No invoices found to process'
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

        $action = $this->input->post('action');
        $invoice_ids = [];

        // Get invoice IDs based on action
        switch ($action) {
            case 'download_sent':
                // Get invoices with 'sent' or 'delivered' status
                $this->db->select('pi.invoice_id');
                $this->db->from(db_prefix() . 'peppol_invoices pi');
                $this->db->where_in('pi.status', ['sent', 'delivered']);
                $results = $this->db->get()->result();
                $invoice_ids = array_column($results, 'invoice_id');
                break;

            default:
                set_alert('danger', _l('peppol_invalid_action'));
                redirect(admin_url('invoices'));
                return;
        }
        
        if (empty($invoice_ids)) {
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

    /**
     * Bulk send with real-time progress tracking
     */
    public function bulk_send_with_progress()
    {
        if (!staff_can('create', 'peppol') || !$this->input->post()) {
            access_denied('peppol');
        }

        $action = $this->input->post('action');
        $operation_id = $this->input->post('operation_id');
        
        if (!$operation_id) {
            $operation_id = 'bulk_' . time();
        }

        $invoice_ids = [];

        // Get invoice IDs based on action
        switch ($action) {
            case 'send_unsent':
                $this->db->select('i.id');
                $this->db->from(db_prefix() . 'invoices i');
                $this->db->join(db_prefix() . 'peppol_invoices pi', 'pi.invoice_id = i.id', 'left');
                $this->db->where('pi.id IS NULL');
                $this->db->where('i.status', 2);
                $results = $this->db->get()->result();
                $invoice_ids = array_column($results, 'id');
                break;

            case 'retry_failed':
                $this->db->select('invoice_id');
                $this->db->from(db_prefix() . 'peppol_invoices');
                $this->db->where('status', 'failed');
                $results = $this->db->get()->result();
                $invoice_ids = array_column($results, 'invoice_id');
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action specified'
                ]);
                return;
        }

        if (empty($invoice_ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'No invoices found to process'
            ]);
            return;
        }

        // Initialize progress tracking in cache/session
        $total = count($invoice_ids);
        $progress_data = [
            'total' => $total,
            'completed' => 0,
            'success' => 0,
            'errors' => 0,
            'started' => time(),
            'error_messages' => []
        ];

        $this->set_bulk_operation_progress($operation_id, $progress_data);

        // Process invoices with progress updates
        $batch_size = 5; // Process in smaller batches for progress updates
        $current_batch = 0;
        $total_batches = ceil($total / $batch_size);

        for ($i = 0; $i < $total; $i += $batch_size) {
            $batch_invoices = array_slice($invoice_ids, $i, $batch_size);
            
            foreach ($batch_invoices as $invoice_id) {
                try {
                    $result = $this->peppol_service->send_invoice($invoice_id);
                    
                    if ($result['success']) {
                        $progress_data['success']++;
                    } else {
                        $progress_data['errors']++;
                        $progress_data['error_messages'][] = "Invoice #{$invoice_id}: " . $result['message'];
                    }
                    
                    $progress_data['completed']++;
                    
                } catch (Exception $e) {
                    $progress_data['errors']++;
                    $progress_data['error_messages'][] = "Invoice #{$invoice_id}: " . $e->getMessage();
                    $progress_data['completed']++;
                }
                
                // Update progress after each invoice
                $this->set_bulk_operation_progress($operation_id, $progress_data);
            }
            
            $current_batch++;
            
            // Small delay between batches to prevent overwhelming the system
            if ($current_batch < $total_batches) {
                usleep(500000); // 0.5 second delay
            }
        }

        // Final response
        $response = [
            'success' => $progress_data['success'] > 0,
            'progress' => [
                'total' => $total,
                'completed' => $progress_data['completed'],
                'success' => $progress_data['success'],
                'errors' => $progress_data['errors']
            ]
        ];

        if ($progress_data['errors'] === 0) {
            $response['message'] = _l('peppol_bulk_send_completed');
        } elseif ($progress_data['success'] > 0) {
            $response['message'] = _l('peppol_bulk_send_partial');
        } else {
            $response['message'] = _l('peppol_bulk_operation_failed');
            $response['success'] = false;
        }

        if (!empty($progress_data['error_messages'])) {
            $response['errors'] = array_slice($progress_data['error_messages'], 0, 10); // Limit to 10 errors
        }

        // Clean up progress data
        $this->clear_bulk_operation_progress($operation_id);

        echo json_encode($response);
    }

    /**
     * Get bulk operation progress
     */
    public function bulk_progress()
    {
        if (!staff_can('view', 'peppol') || !$this->input->post()) {
            ajax_access_denied();
        }

        $operation_id = $this->input->post('operation_id');
        
        if (!$operation_id) {
            echo json_encode([
                'success' => false,
                'message' => 'No operation ID provided'
            ]);
            return;
        }

        $progress = $this->get_bulk_operation_progress($operation_id);

        if ($progress === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Operation not found or completed'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Store bulk operation progress
     */
    private function set_bulk_operation_progress($operation_id, $progress_data)
    {
        // Store in cache with 10 minute expiry
        $cache_key = 'peppol_bulk_progress_' . $operation_id;
        
        // For now, we'll use session as a simple cache mechanism
        // In production, you might want to use Redis or database
        if (!isset($_SESSION['peppol_bulk_operations'])) {
            $_SESSION['peppol_bulk_operations'] = [];
        }
        
        $_SESSION['peppol_bulk_operations'][$operation_id] = $progress_data;
        $_SESSION['peppol_bulk_operations'][$operation_id]['updated'] = time();
    }

    /**
     * Get bulk operation progress
     */
    private function get_bulk_operation_progress($operation_id)
    {
        if (!isset($_SESSION['peppol_bulk_operations'][$operation_id])) {
            return false;
        }

        $progress = $_SESSION['peppol_bulk_operations'][$operation_id];
        
        // Clean up old operations (older than 10 minutes)
        if (time() - $progress['updated'] > 600) {
            unset($_SESSION['peppol_bulk_operations'][$operation_id]);
            return false;
        }

        return $progress;
    }

    /**
     * Clear bulk operation progress
     */
    private function clear_bulk_operation_progress($operation_id)
    {
        if (isset($_SESSION['peppol_bulk_operations'][$operation_id])) {
            unset($_SESSION['peppol_bulk_operations'][$operation_id]);
        }
    }
}