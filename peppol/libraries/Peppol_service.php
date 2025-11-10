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

    // ========================================
    // LEGAL ENTITY MANAGEMENT
    // ========================================

    /**
     * Create or update legal entity for a client by ID
     */
    public function create_or_update_client_legal_entity($client_id, $provider_name = null)
    {
        try {
            // Ensure PEPPOL custom fields exist
            $this->ensure_peppol_custom_fields_exist();

            // Load client data with PEPPOL fields
            $client = $this->get_client_with_peppol_fields($client_id);

            if (!$client) {
                throw new Exception('Client not found');
            }

            // Map client data to legal entity format
            $entity_data = $this->map_client_to_legal_entity_data($client);

            // Determine target provider(s)
            $providers = $provider_name ? [$provider_name] : [$this->get_active_peppol_provider()];
            $results = [];

            // Process each provider
            foreach ($providers as $provider) {
                $result = $this->register_with_provider($client_id, $provider, $entity_data);
                $results[$provider] = $result;

                // Update custom fields with results
                $this->update_client_legal_entity_status($client_id, $provider, $result);
            }

            return [
                'success' => true,
                'message' => 'Legal entity registration completed',
                'results' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get client legal entity registration status
     */
    public function get_client_legal_entity_status($client_id, $provider = null)
    {
        $notes = get_custom_field_value($client_id, 'peppol_registration_notes', 'customers');

        // Determine status from notes content
        $is_registered = false;
        $entity_id = '';

        if (!empty($notes)) {
            // Check if notes contain success indicators
            if (
                strpos(strtolower($notes), 'success') !== false ||
                strpos(strtolower($notes), 'registered') !== false
            ) {
                $is_registered = true;

                // Try to extract entity ID from notes
                if (preg_match('/ID[:\s]*([A-Za-z0-9-_]+)/', $notes, $matches)) {
                    $entity_id = $matches[1];
                }
            }
        }

        return [
            'entity_id' => $entity_id,
            'registered' => $is_registered,
            'notes' => $notes
        ];
    }

    /**
     * Sync existing legal entity with provider
     */
    public function sync_client_legal_entity($client_id, $provider)
    {
        try {
            // Check if client has successful registration notes
            $status = $this->get_client_legal_entity_status($client_id);

            if ($status['registered'] && !empty($status['entity_id'])) {
                // Update existing entity
                return $this->update_existing_legal_entity($client_id, $provider, $status['entity_id']);
            }

            // Create new entity if none exists
            return $this->create_or_update_client_legal_entity($client_id, $provider);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Ensure PEPPOL custom fields exist for legal entity tracking
     */
    public function ensure_peppol_custom_fields_exist()
    {
        $fields = [
            [
                'fieldto' => 'customers',
                'name' => 'PEPPOL Scheme',
                'slug' => 'peppol_scheme',
                'type' => 'input',
                'only_admin' => 0,
                'active' => 1,
                'bs_column' => 6
            ],
            [
                'fieldto' => 'customers',
                'name' => 'PEPPOL Identifier',
                'slug' => 'peppol_identifier',
                'type' => 'input',
                'only_admin' => 0,
                'active' => 1,
                'bs_column' => 6
            ],
            [
                'fieldto' => 'customers',
                'name' => 'PEPPOL Registration Notes',
                'slug' => 'peppol_registration_notes',
                'type' => 'textarea',
                'only_admin' => 1,
                'active' => 1,
                'bs_column' => 12
            ]
        ];

        foreach ($fields as $field) {
            $this->create_custom_field_if_not_exists($field);
        }
    }

    /**
     * Create custom field if it doesn't exist
     */
    private function create_custom_field_if_not_exists($field)
    {
        // Check if field already exists
        $this->CI->db->where('slug', $field['slug']);
        $this->CI->db->where('fieldto', $field['fieldto']);
        $existing = $this->CI->db->get(db_prefix() . 'customfields')->row();

        if (!$existing) {
            // Set defaults
            $field['required'] = $field['required'] ?? 0;
            $field['field_order'] = $field['field_order'] ?? 99;
            $field['display_inline'] = $field['display_inline'] ?? 0;

            $this->CI->db->insert(db_prefix() . 'customfields', $field);
        }
    }

    /**
     * Load client data with PEPPOL custom fields
     */
    private function get_client_with_peppol_fields($client_id)
    {
        $this->CI->load->model('clients_model');
        $client = $this->CI->clients_model->get($client_id);

        if (!$client) {
            return null;
        }

        // Load PEPPOL custom field values
        $peppol_fields = [
            'peppol_scheme',
            'peppol_identifier',
            'peppol_registration_notes'
        ];

        foreach ($peppol_fields as $field) {
            $client->{$field} = get_custom_field_value($client_id, $field, 'customers');
        }

        return $client;
    }

    /**
     * Map client data to legal entity format
     */
    private function map_client_to_legal_entity_data($client)
    {
        // Load country code and name
        $country_code = '';
        $country_name = '';
        if ($client->country) {
            $this->CI->load->model('countries_model');
            $country = $this->CI->countries_model->get($client->country);
            if ($country) {
                $country_code = strtoupper($country->iso2 ?? ''); // 2-letter ISO code
                $country_name = $country->short_name;
            }
        }

        // Fallback to system country, then Belgium if no country specified
        if (!$country_code) {
            $system_country_id = get_option('company_country');
            if ($system_country_id) {
                $this->CI->load->model('countries_model');
                $system_country = $this->CI->countries_model->get($system_country_id);
                if ($system_country) {
                    $country_code = strtoupper($system_country->iso2 ?? '');
                    $country_name = $system_country->short_name;
                }
            }

            // Final fallback to Belgium
            if (!$country_code) {
                $country_code = 'BE';
                $country_name = 'Belgium';
            }
        }

        // Get primary contact information
        $this->CI->load->model('clients_model');
        $this->CI->load->helper('clients');

        $primary_contact_id = get_primary_contact_user_id($client->userid);
        $primary_contact = null;
        if ($primary_contact_id) {
            $primary_contact = $this->CI->clients_model->get_contact($primary_contact_id);
        }

        $contact_name = '';
        $contact_email = '';
        $contact_phonenumber = $client->phonenumber ?: '';

        if ($primary_contact) {
            $contact_name = trim($primary_contact->firstname . ' ' . $primary_contact->lastname);
            $contact_email = $primary_contact->email;
            $contact_phonenumber = $primary_contact->phonenumber ?: $contact_phonenumber;
        }

        // Get PEPPOL custom field values - these are required for legal entity registration
        $peppol_identifier = get_custom_field_value($client->userid, 'peppol_identifier', 'customers');
        $peppol_scheme = get_custom_field_value($client->userid, 'peppol_scheme', 'customers');

        // Validate required PEPPOL fields
        if (empty($peppol_identifier)) {
            throw new Exception(_l('peppol_identifier_required_error'));
        }

        if (empty($peppol_scheme)) {
            throw new Exception(_l('peppol_scheme_required_error'));
        }

        // Build address string
        $street = $client->address ?: $client->billing_street;
        $city = $client->city ?: $client->billing_city;
        $geographical_info = trim($street . ', ' . $city, ', ');

        return [
            'legalEntityDetails' => [
                'publishInPeppolDirectory' => true,
                'name' => $client->company ?: 'Client #' . $client->userid,
                'countryCode' => $country_code,
                'geographicalInformation' => $geographical_info ?: 'Not specified',
                'websiteURL' => $client->website ?: '',
                'contacts' => [
                    [
                        'contactType' => 'primary',
                        'name' => $contact_name ?: 'Primary Contact',
                        'phoneNumber' => $contact_phonenumber,
                        'email' => $contact_email ?: ''
                    ]
                ],
                'additionalInformation' => ''
            ],
            'peppolRegistrations' => [
                [
                    'peppolIdentifier' => [
                        'scheme' => $peppol_scheme,
                        'identifier' => $peppol_identifier
                    ],
                    'supportedDocuments' => [
                        'PEPPOL_BIS_BILLING_UBL_INVOICE_V3',
                        'PEPPOL_BIS_BILLING_UBL_CREDIT_NOTE_V3',
                        'PEPPOL_MESSAGE_LEVEL_RESPONSE_TRANSACTION_3_0',
                        'PEPPOL_INVOICE_RESPONSE_TRANSACTION_3_0'
                    ],
                    'peppolRegistration' => true
                ]
            ]
        ];
    }

    /**
     * Register client with specific provider
     */
    private function register_with_provider($client_id, $provider, $entity_data)
    {
        try {
            $provider_instance = $this->get_provider($provider);

            // Check if entity already exists for this provider
            $existing_entity_id = get_custom_field_value($client_id, "peppol_{$provider}_entity_id", 'customers');

            if ($existing_entity_id) {
                // Update existing entity
                $result = $provider_instance->update_legal_entity($existing_entity_id, $entity_data);
                $result['action'] = 'updated';
            } else {
                // Create new entity
                $result = $provider_instance->create_legal_entity($entity_data);
                $result['action'] = 'created';
            }

            // Log the activity
            $this->CI->peppol_model->log_activity([
                'client_id' => $client_id,
                'provider' => $provider,
                'action' => 'legal_entity_' . $result['action'],
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'action' => 'failed'
            ];
        }
    }

    /**
     * Update client legal entity status in custom fields
     */
    private function update_client_legal_entity_status($client_id, $provider, $result)
    {
        $current_time = date('Y-m-d H:i:s');

        // Create status note based on result
        if ($result['success']) {
            $notes = "SUCCESS: Legal entity registered with {$provider}";
            if (isset($result['entity_id'])) {
                $notes .= " - Entity ID: " . $result['entity_id'];
            }
            $notes .= " (on {$current_time})";
        } else {
            $notes = "ERROR: Registration failed with {$provider}: " . $result['message'] . " (on {$current_time})";
        }

        // Update notes field with status information
        $this->update_client_custom_field($client_id, 'peppol_registration_notes', $notes);
    }

    /**
     * Update specific custom field for a client
     */
    private function update_client_custom_field($client_id, $field_slug, $value)
    {
        // Get field ID
        $this->CI->db->where('slug', $field_slug);
        $this->CI->db->where('fieldto', 'customers');
        $field = $this->CI->db->get(db_prefix() . 'customfields')->row();

        if (!$field) {
            return false;
        }

        // Check if value already exists
        $this->CI->db->where('relid', $client_id);
        $this->CI->db->where('fieldid', $field->id);
        $this->CI->db->where('fieldto', 'customers');
        $existing = $this->CI->db->get(db_prefix() . 'customfieldsvalues')->row();

        if ($existing) {
            // Update existing value
            $this->CI->db->where('id', $existing->id);
            return $this->CI->db->update(db_prefix() . 'customfieldsvalues', ['value' => $value]);
        } else {
            // Insert new value
            return $this->CI->db->insert(db_prefix() . 'customfieldsvalues', [
                'relid' => $client_id,
                'fieldid' => $field->id,
                'fieldto' => 'customers',
                'value' => $value
            ]);
        }
    }

    /**
     * Update existing legal entity
     */
    private function update_existing_legal_entity($client_id, $provider, $entity_id)
    {
        $client = $this->get_client_with_peppol_fields($client_id);
        $entity_data = $this->map_client_to_legal_entity_data($client);

        $provider_instance = $this->get_provider($provider);
        $result = $provider_instance->update_legal_entity($entity_id, $entity_data);
        $result['action'] = 'synced';

        $this->update_client_legal_entity_status($client_id, $provider, $result);

        return $result;
    }

    /**
     * Get active PEPPOL provider
     */
    private function get_active_peppol_provider()
    {
        return get_option('peppol_active_provider', 'ademico');
    }
}