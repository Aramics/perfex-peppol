<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load the PEPPOL input helper
require_once(__DIR__ . '/../helpers/peppol_input_helper.php');

// Hook to replace PEPPOL custom fields with reusable scheme:identifier format
hooks()->add_action('app_admin_head', 'peppol_replace_custom_fields');

/**
 * Replace PEPPOL custom fields with reusable scheme:identifier input component
 */
function peppol_replace_custom_fields()
{
    // Only load on client pages
    $CI = &get_instance();
    if ($CI->router->fetch_class() !== 'clients') {
        return;
    }

    // Get the replacement JavaScript from helper
    $replacementJs = get_peppol_custom_field_replacement_js();

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            ' . $replacementJs . '
            replacePeppolCustomFields();
        }, 500);
    });
    </script>';
}