<?php

/**
 * Test Credentials Configuration Example
 * 
 * Copy this file to test_credentials.php and update with your actual test credentials
 * This file should NOT be committed to version control for security
 */

defined('BASEPATH') or exit('No direct script access allowed');

return [
    'ademico' => [
        'peppol_ademico_oauth2_client_identifier' => 'your_ademico_test_client_id',
        'peppol_ademico_oauth2_client_secret' => 'your_ademico_test_client_secret'
    ],
    'unit4' => [
        'peppol_unit4_username' => 'your_unit4_test_username',
        'peppol_unit4_password' => 'your_unit4_test_password'
    ],
    'recommand' => [
        'peppol_recommand_api_key' => 'your_recommand_test_api_key',
        'peppol_recommand_company_id' => 'your_recommand_test_company_id'
    ]
];