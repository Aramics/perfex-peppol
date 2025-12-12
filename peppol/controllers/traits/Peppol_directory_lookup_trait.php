<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Peppol Directory Lookup Controller Trait
 * Used for search local customers in Peppol Directory and update their Peppol details
 */
trait Peppol_directory_lookup_trait
{
    /**
     * Batch lookup with progress tracking
     */
    public function ajax_batch_lookup_progress()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) {
            show_404();
        }

        $customer_ids = $this->input->post('customer_ids');
        $batch_size = 5; // Process 5 at a time
        $offset = (int) $this->input->post('offset', 0);

        if (empty($customer_ids)) {
            // Get all customers
            $this->db->select('userid');
            $this->db->from(db_prefix() . 'clients');
            $this->db->where('company IS NOT NULL');
            $this->db->where('company !=', '');
            $this->db->where('active', 1);
            $customer_ids = array_column($this->db->get()->result_array(), 'userid');
        } elseif (is_string($customer_ids)) {
            $customer_ids = explode(',', $customer_ids);
        }

        $total_customers = count($customer_ids);
        $batch_customer_ids = array_slice($customer_ids, $offset, $batch_size);

        if (empty($batch_customer_ids)) {
            echo json_encode([
                'success' => true,
                'completed' => true,
                'total' => $total_customers,
                'processed' => $offset
            ]);
            return;
        }

        $this->load->library('peppol/peppol_directory_lookup');
        $this->load->model('clients_model');

        $results = [
            'success' => true,
            'completed' => false,
            'total' => $total_customers,
            'processed' => $offset,
            'batch_results' => [],
            'next_offset' => $offset + $batch_size
        ];

        foreach ($batch_customer_ids as $customer_id) {
            $customer = $this->clients_model->get($customer_id);
            if (!$customer) {
                continue;
            }

            $result = $this->peppol_directory_lookup->auto_lookup_customer($customer_id);

            $batch_result = [
                'customer_id' => $customer_id,
                'company' => $customer->company,
                'success' => $result['success'],
                'message' => $result['message']
            ];

            if ($result['success'] && isset($result['participant'])) {
                $batch_result['participant'] = $result['participant'];
            } elseif (isset($result['multiple_results']) && $result['multiple_results']) {
                // Multiple results case - include data for frontend selection
                $batch_result['multiple_results'] = $result['participants'];
                $batch_result['customer_data'] = $result['customer_data'];
            }

            $results['batch_results'][] = $batch_result;
            $results['processed']++;

            // Small delay to be respectful to the API
            usleep(200000); // 0.2 seconds
        }

        if ($results['processed'] >= $total_customers) {
            $results['completed'] = true;
        }

        echo json_encode($results);
    }

    /**
     * Apply user selection from multiple results
     */
    public function ajax_apply_lookup_result()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) {
            show_404();
        }

        $customer_id = (int) $this->input->post('customer_id');
        $scheme = $this->input->post('scheme');
        $identifier = $this->input->post('identifier');
        $method = $this->input->post('method', _l('peppol_user_selected'));

        if (empty($customer_id) || empty($scheme) || empty($identifier)) {
            $this->json_output([
                'success' => false,
                'message' => _l('peppol_missing_required_parameters')
            ]);
        }

        $this->load->library('peppol/peppol_directory_lookup');

        // Create participant array for the update method
        $participant = [
            'scheme' => $scheme,
            'identifier' => $identifier,
            'name' => $this->input->post('name', 'Selected Participant'),
            'country' => $this->input->post('country', ''),
            'method' => $method
        ];

        $result = $this->peppol_directory_lookup->update_customer_fields($customer_id, $participant);

        if ($result['success']) {
            $result['method'] = $method;
            $result['participant'] = $participant;
        }

        $this->json_output($result);
    }

    /**
     * Apply multiple user selections in a single batch request
     */
    public function ajax_apply_batch_selections()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) {
            show_404();
        }

        $selections = $this->input->post('selections');
        if (empty($selections) || !is_array($selections)) {
            $this->json_output([
                'success' => false,
                'message' => _l('peppol_no_selections_provided')
            ]);
        }

        $this->load->library('peppol/peppol_directory_lookup');
        $this->load->model('clients_model');

        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($selections as $selection) {
            $customer_id = (int) $selection['customer_id'];
            $customer = $this->clients_model->get($customer_id);

            if (!$customer) {
                $results[] = [
                    'customer_id' => $customer_id,
                    'success' => false,
                    'message' => _l('peppol_customer_not_found')
                ];
                $failed++;
                continue;
            }

            if ($selection['type'] === 'none') {
                // User selected "none of these"
                $results[] = [
                    'customer_id' => $customer_id,
                    'company' => $customer->company,
                    'success' => true,
                    'message' => _l('peppol_no_matching_participant_user_none'),
                    'type' => 'none'
                ];
            } elseif ($selection['type'] === 'participant') {
                // User selected a specific participant
                $participant = [
                    'scheme' => $selection['scheme'],
                    'identifier' => $selection['identifier'],
                    'name' => $selection['name'] ?? _l('peppol_selected_participant'),
                    'country' => $selection['country'] ?? '',
                    'method' => _l('peppol_user_selected')
                ];

                $result = $this->peppol_directory_lookup->update_customer_fields($customer_id, $participant);

                $results[] = [
                    'customer_id' => $customer_id,
                    'company' => $customer->company,
                    'success' => $result['success'],
                    'message' => $result['success'] ?
                        sprintf(_l('peppol_applied_user_selected'), $participant['name']) :
                        sprintf(_l('peppol_failed_to_apply'), $result['message'] ?? _l('peppol_unknown_error')),
                    'type' => 'participant',
                    'participant' => $participant
                ];

                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                }
            }
        }

        $this->json_output([
            'success' => true,
            'results' => $results,
            'summary' => [
                'total' => count($selections),
                'successful' => $successful,
                'failed' => $failed
            ]
        ]);
    }

    /**
     * Auto-lookup single customer (AJAX endpoint)
     */
    public function ajax_auto_lookup_customer()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) {
            show_404();
        }

        $customer_id = (int) $this->input->post('customer_id');
        if (!$customer_id) {
            $this->json_output([
                'success' => false,
                'message' => _l('peppol_invalid_customer_id_simple')
            ]);
        }

        $this->load->library('peppol/peppol_directory_lookup');
        $result = $this->peppol_directory_lookup->auto_lookup_customer($customer_id);

        $this->json_output($result);
    }

    /**
     * Peppol Directory page
     */
    public function directory()
    {
        if (!staff_can('view', 'peppol')) {
            access_denied();
        }

        // Return the table data for ajax request
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path(PEPPOL_MODULE_NAME, 'admin/tables/peppol_directory'));
        }

        $data['title'] = _l('peppol_directory_menu');
        $this->load->view('admin/directory', $data);
    }
}