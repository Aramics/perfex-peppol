<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Simplified Peppol Directory Lookup Controller Trait
 */
trait Peppol_directory_lookup_trait
{
    /**
     * Auto-lookup single customer via AJAX
     */
    public function ajax_auto_lookup_customer()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $customer_id = (int) $this->input->post('customer_id');
        if (!$customer_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
            return;
        }

        $this->load->library('peppol/peppol_directory_lookup');
        $result = $this->peppol_directory_lookup->auto_lookup_customer($customer_id);

        echo json_encode($result);
    }

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
     * Batch process customers (for cron or admin UI)
     */
    public function batch_lookup_customers()
    {
        // Admin only for manual runs
        if (!$this->input->is_cli_request() && !is_admin()) {
            access_denied();
        }

        $limit = (int) ($this->input->get('limit') ?? 50);

        $this->load->library('peppol/peppol_directory_lookup');
        $results = $this->peppol_directory_lookup->batch_lookup_customers($limit);

        if ($this->input->is_ajax_request()) {
            echo json_encode($results);
        } elseif ($this->input->is_cli_request()) {
            // CLI output for cron
            echo "Peppol Directory Lookup: Processed {$results['processed']}, Updated {$results['updated']}\n";
            if (!empty($results['errors'])) {
                echo "Errors: " . count($results['errors']) . "\n";
            }
        } else {
            // Manual admin run - show simple results
            echo "<h3>Peppol Directory Batch Lookup Results</h3>";
            echo "<p>Processed: {$results['processed']} customers</p>";
            echo "<p>Updated: {$results['updated']} customers</p>";
            if (!empty($results['errors'])) {
                echo "<p>Errors: " . count($results['errors']) . "</p>";
                echo "<details><summary>Error Details</summary><pre>" . implode("\n", $results['errors']) . "</pre></details>";
            }
            echo "<br><a href='" . admin_url('peppol') . "' class='btn btn-default'>Back to Peppol</a>";
        }
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