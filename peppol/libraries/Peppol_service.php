<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Peppol_service
{
    private $CI;
    private $providers = [];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('peppol/peppol_model');
        $this->CI->load->library('peppol/ubl_generator');
        $this->CI->load->library('peppol/peppol_provider_factory');
    }

    /**
     * Get provider instance using factory
     */
    private function get_provider($provider_name = null)
    {
        return Peppol_provider_factory::get_provider($provider_name);
    }

    /**
     * Send invoice via PEPPOL
     */
    public function send_invoice($invoice_id)
    {
        try {
            // Get invoice data
            $this->CI->load->model('invoices_model');
            $invoice = $this->CI->invoices_model->get($invoice_id);
            
            if (!$invoice) {
                throw new Exception('Invoice not found');
            }

            // Check if client has PEPPOL identifier
            $this->CI->load->model('clients_model');
            $client = $this->CI->clients_model->get($invoice->clientid);
            
            if (empty($client->peppol_identifier)) {
                throw new Exception(_l('peppol_client_no_identifier'));
            }

            // Get or create PEPPOL invoice record
            $provider = get_active_peppol_provider();
            $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice_by_invoice($invoice_id, $provider);
            
            if (!$peppol_invoice) {
                $peppol_invoice_id = $this->CI->peppol_model->save_peppol_invoice([
                    'invoice_id' => $invoice_id,
                    'provider' => $provider,
                    'status' => 'sending'
                ]);
                $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice($peppol_invoice_id);
            } else {
                $this->CI->peppol_model->update_peppol_invoice_status($peppol_invoice->id, 'sending');
            }

            // Generate UBL content
            $ubl_content = $this->CI->ubl_generator->generate_invoice_ubl($invoice, $client);
            
            // Update with UBL content
            $this->CI->peppol_model->save_peppol_invoice([
                'id' => $peppol_invoice->id,
                'ubl_content' => $ubl_content
            ]);

            // Send via provider
            $provider_instance = $this->get_provider($provider);
            $result = $provider_instance->send_document($ubl_content, $invoice, $client);

            if ($result['success']) {
                $this->CI->peppol_model->update_peppol_invoice_status($peppol_invoice->id, 'sent', [
                    'peppol_document_id' => $result['document_id'] ?? null,
                    'response_data' => json_encode($result['response'] ?? []),
                    'sent_at' => date('Y-m-d H:i:s')
                ]);

                $this->CI->peppol_model->log_invoice_event(
                    $invoice_id, 
                    'sent', 
                    'Invoice sent successfully via PEPPOL', 
                    'success',
                    ['document_id' => $result['document_id'] ?? null]
                );

                return ['success' => true, 'document_id' => $result['document_id'] ?? null];
            } else {
                $this->CI->peppol_model->update_peppol_invoice_status($peppol_invoice->id, 'failed', [
                    'error_message' => $result['message'],
                    'response_data' => json_encode($result['response'] ?? [])
                ]);

                $this->CI->peppol_model->log_invoice_event(
                    $invoice_id, 
                    'send_failed', 
                    'Failed to send invoice: ' . $result['message'], 
                    'error'
                );

                return ['success' => false, 'message' => $result['message']];
            }

        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            if (isset($peppol_invoice)) {
                $this->CI->peppol_model->update_peppol_invoice_status($peppol_invoice->id, 'failed', [
                    'error_message' => $error_message
                ]);
            }

            $this->CI->peppol_model->log_invoice_event(
                $invoice_id, 
                'send_error', 
                'Error sending invoice: ' . $error_message, 
                'error'
            );

            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Test provider connection
     */
    public function test_connection($provider_name = null, $environment = null)
    {
        try {
            $provider = $this->get_provider($provider_name);
            $result = $provider->test_connection($environment);
            
            $this->CI->peppol_model->log_activity([
                'provider' => $provider_name ?: get_active_peppol_provider(),
                'action' => 'test_connection',
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle webhook from provider
     */
    public function handle_webhook($provider_name)
    {
        $provider = $this->get_provider($provider_name);
        $document_data = $provider->handle_webhook();
        
        if ($document_data) {
            // Save received document
            $document_id = $this->CI->peppol_model->save_received_document([
                'document_id' => $document_data['document_id'],
                'provider' => $provider_name,
                'document_type' => $document_data['document_type'] ?? 'invoice',
                'sender_identifier' => $document_data['sender_identifier'] ?? null,
                'receiver_identifier' => $document_data['receiver_identifier'] ?? null,
                'document_content' => $document_data['content']
            ]);

            $this->CI->peppol_model->log_activity([
                'document_id' => $document_data['document_id'],
                'provider' => $provider_name,
                'action' => 'document_received',
                'status' => 'info',
                'message' => 'Document received via webhook'
            ]);

            // Auto-process if enabled
            if (get_option('peppol_auto_process_received') == '1') {
                $this->process_received_document($document_id);
            }

            return true;
        }
        
        return false;
    }

    /**
     * Process received PEPPOL document
     */
    public function process_received_document($document_id)
    {
        try {
            $document = $this->CI->peppol_model->get_received_document($document_id);
            
            if (!$document || $document->processed) {
                throw new Exception('Document not found or already processed');
            }

            // Parse UBL content
            $this->CI->load->library('peppol/ubl_parser');
            $parsed_data = $this->CI->ubl_parser->parse_invoice($document->document_content);

            // Create invoice in CRM
            $this->CI->load->model('invoices_model');
            $this->CI->load->model('clients_model');

            // Find or create client based on PEPPOL identifier
            $client = $this->find_or_create_client($parsed_data['client_data']);
            
            $invoice_data = [
                'clientid' => $client->userid,
                'number' => $parsed_data['invoice_number'],
                'date' => $parsed_data['issue_date'],
                'duedate' => $parsed_data['due_date'],
                'currency' => $parsed_data['currency'],
                'subtotal' => $parsed_data['subtotal'],
                'total' => $parsed_data['total'],
                'terms' => $parsed_data['terms'] ?? '',
                'clientnote' => _l('peppol_document_received'),
                'adminnote' => 'Created from PEPPOL document: ' . $document->document_id,
                'newitems' => $parsed_data['items']
            ];

            $invoice_id = $this->CI->invoices_model->add($invoice_data);

            if ($invoice_id) {
                $this->CI->peppol_model->mark_document_processed($document_id, $invoice_id);
                
                $this->CI->peppol_model->log_activity([
                    'invoice_id' => $invoice_id,
                    'document_id' => $document->document_id,
                    'provider' => $document->provider,
                    'action' => 'document_processed',
                    'status' => 'success',
                    'message' => 'Document processed successfully into invoice #' . $invoice_id
                ]);

                return ['success' => true, 'invoice_id' => $invoice_id];
            } else {
                throw new Exception('Failed to create invoice');
            }

        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            $this->CI->peppol_model->mark_document_processed($document_id, null, $error_message);
            
            $this->CI->peppol_model->log_activity([
                'document_id' => $document->document_id ?? null,
                'provider' => $document->provider ?? null,
                'action' => 'process_error',
                'status' => 'error',
                'message' => 'Error processing document: ' . $error_message
            ]);

            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Find or create client based on PEPPOL identifier
     */
    private function find_or_create_client($client_data)
    {
        // Try to find existing client by PEPPOL identifier
        $this->CI->db->where('peppol_identifier', $client_data['peppol_identifier']);
        $client = $this->CI->db->get(db_prefix() . 'clients')->row();
        
        if ($client) {
            return $client;
        }

        // Create new client
        $new_client_data = [
            'company' => $client_data['company'] ?? $client_data['contact_name'],
            'firstname' => $client_data['firstname'] ?? '',
            'lastname' => $client_data['lastname'] ?? '',
            'email' => $client_data['email'] ?? '',
            'address' => $client_data['address'] ?? '',
            'city' => $client_data['city'] ?? '',
            'zip' => $client_data['zip'] ?? '',
            'country' => $client_data['country'] ?? 0,
            'peppol_identifier' => $client_data['peppol_identifier'],
            'peppol_scheme' => $client_data['peppol_scheme'] ?? '0088'
        ];

        $client_id = $this->CI->clients_model->add($new_client_data);
        
        return $this->CI->clients_model->get($client_id);
    }

    /**
     * Get invoice delivery status
     */
    public function get_delivery_status($peppol_invoice_id)
    {
        $peppol_invoice = $this->CI->peppol_model->get_peppol_invoice($peppol_invoice_id);
        
        if (!$peppol_invoice || !$peppol_invoice->peppol_document_id) {
            return ['success' => false, 'message' => 'Document not found'];
        }

        try {
            $provider = $this->get_provider($peppol_invoice->provider);
            $status = $provider->get_delivery_status($peppol_invoice->peppol_document_id);
            
            // Update status if changed
            if ($status['success'] && $status['status'] != $peppol_invoice->status) {
                $this->CI->peppol_model->update_peppol_invoice_status(
                    $peppol_invoice->id, 
                    $status['status'],
                    ['received_at' => $status['delivered_at'] ?? null]
                );
            }
            
            return $status;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process pending invoices (for cron job)
     */
    public function process_pending_invoices()
    {
        $pending_invoices = $this->CI->peppol_model->get_pending_invoices();
        $processed = 0;
        
        foreach ($pending_invoices as $peppol_invoice) {
            $result = $this->send_invoice($peppol_invoice->invoice_id);
            
            if ($result['success']) {
                $processed++;
            }
            
            // Add small delay to avoid overwhelming the API
            usleep(500000); // 0.5 seconds
        }
        
        return $processed;
    }
}