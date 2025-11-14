<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Public webhook controller for PEPPOL document reception
 * This controller is publicly accessible and doesn't require authentication
 */
class Peppol_webhook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('peppol/peppol_model');
        $this->load->library('peppol/peppol_service');
    }

    /**
     * Main webhook endpoint for receiving PEPPOL documents
     * URL: https://your-domain.com/peppol/webhook?provider=ademico
     */
    public function index()
    {
        // Set JSON header
        header('Content-Type: application/json');
        
        try {
            $provider = $this->input->get('provider');
            
            if (!$provider) {
                $this->_respond_error('Provider parameter is required', 400);
                return;
            }
            
            // Validate provider exists
            $providers = get_peppol_providers();
            if (!isset($providers[$provider])) {
                $this->_respond_error('Invalid provider', 400);
                return;
            }
            
            // Log webhook request
            log_message('info', 'PEPPOL webhook received from provider: ' . $provider);
            
            // Process webhook
            $result = $this->peppol_service->handle_webhook($provider);
            
            if ($result) {
                $this->_respond_success('Document received and processed');
            } else {
                $this->_respond_success('Webhook processed');
            }
            
        } catch (Exception $e) {
            log_message('error', 'PEPPOL webhook error: ' . $e->getMessage());
            $this->_respond_error('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Ademico specific webhook endpoint
     */
    public function ademico()
    {
        $_GET['provider'] = 'ademico';
        $this->index();
    }

    /**
     * Unit4 specific webhook endpoint
     */
    public function unit4()
    {
        $_GET['provider'] = 'unit4';
        $this->index();
    }

    /**
     * Recommand specific webhook endpoint
     */
    public function recommand()
    {
        $_GET['provider'] = 'recommand';
        $this->index();
    }

    /**
     * Health check endpoint
     */
    public function health()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'module' => 'peppol',
            'version' => '1.0.0'
        ]);
    }

    /**
     * Respond with success
     */
    private function _respond_success($message, $data = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
    }

    /**
     * Respond with error
     */
    private function _respond_error($message, $status_code = 400)
    {
        http_response_code($status_code);
        
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}