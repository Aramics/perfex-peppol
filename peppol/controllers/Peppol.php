<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load trait classes
require_once __DIR__ . '/traits/Peppol_local_document_bulk_trait.php';
require_once __DIR__ . '/traits/Peppol_local_document_single_trait.php';
require_once __DIR__ . '/traits/Peppol_provider_management_trait.php';
require_once __DIR__ . '/traits/Peppol_document_management_trait.php';

class Peppol extends AdminController
{
    // Use traits for organized functionality
    use Peppol_local_document_bulk_trait;
    use Peppol_local_document_single_trait;
    use Peppol_provider_management_trait;
    use Peppol_document_management_trait;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(PEPPOL_MODULE_NAME . '/peppol_model');
        $this->load->library(PEPPOL_MODULE_NAME . '/peppol_service');
    }


}