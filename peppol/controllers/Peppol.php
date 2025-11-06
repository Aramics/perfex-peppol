<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('peppol_model');
        $this->load->library('peppol_service');
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