<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load trait classes
require_once __DIR__ . '/traits/Peppol_local_document_bulk_trait.php';
require_once __DIR__ . '/traits/Peppol_local_document_single_trait.php';
require_once __DIR__ . '/traits/Peppol_provider_management_trait.php';
require_once __DIR__ . '/traits/Peppol_document_management_trait.php';
require_once __DIR__ . '/traits/Peppol_logs_trait.php';

class Peppol extends AdminController
{
    // Use traits for organized functionality
    use Peppol_local_document_bulk_trait;
    use Peppol_local_document_single_trait;
    use Peppol_provider_management_trait;
    use Peppol_document_management_trait;
    use Peppol_logs_trait;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(PEPPOL_MODULE_NAME . '/peppol_model');
        $this->load->library(PEPPOL_MODULE_NAME . '/peppol_service');
    }

    /**
     * Centralized JSON response output
     * 
     * Provides consistent JSON response formatting across all PEPPOL endpoints.
     * Sets appropriate headers and handles response encoding.
     * 
     * @param array $data Response data to encode
     * @param bool $exit Whether to exit after output (default: true)
     * @return void Outputs JSON response and optionally exits
     */
    protected function json_output($data,  $exit = true)
    {
        // Set JSON headers
        header('Content-Type: application/json; charset=utf-8');

        // Output JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($exit) {
            exit;
        }
    }
}