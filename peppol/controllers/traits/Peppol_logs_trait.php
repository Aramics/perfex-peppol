<?php

defined('BASEPATH') or exit('No direct script access allowed');

trait Peppol_logs_trait
{
    /**
     * Display PEPPOL logs page
     */
    public function logs()
    {
        if (!staff_can('view', 'peppol_logs')) {
            access_denied('peppol_logs');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path(PEPPOL_MODULE_NAME, 'admin/tables/peppol_logs'));
        }

        $data['title'] = _l('peppol_logs');
        $this->load->view('admin/peppol_logs', $data);
    }

    /**
     * Clear all PEPPOL logs
     */
    public function clear_logs()
    {
        if (!staff_can('delete', 'peppol_logs')) {
            ajax_access_denied();
        }

        try {
            $this->db->empty_table(db_prefix() . 'peppol_logs');

            $this->json_output([
                'success' => true,
                'message' => _l('peppol_logs_cleared_successfully')
            ]);
        } catch (Exception $e) {
            $this->json_output([
                'success' => false,
                'message' => _l('peppol_logs_clear_failed') . ': ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create a test log entry (for debugging)
     */
    public function test_log()
    {
        if (!staff_can('create', 'peppol')) {
            access_denied();
        }

        // Create a test log entry
        $test_data = [
            'type' => 'test',
            'document_type' => 'invoice',
            'local_reference_id' => 1,
            'message' => 'Test log entry created for debugging purposes',
            'staff_id' => get_staff_user_id()
        ];

        $log_id = $this->peppol_model->log_activity($test_data);

        if ($log_id) {
            set_alert('success', 'Test log entry created successfully with ID: ' . $log_id);
        } else {
            set_alert('danger', 'Failed to create test log entry');
        }

        redirect(admin_url('peppol/logs'));
    }
}