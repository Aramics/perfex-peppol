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
}