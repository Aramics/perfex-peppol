<?php

/**
 * Example: How to use the reusable PEPPOL scheme:identifier input in settings
 * 
 * This file demonstrates how to integrate the component into PEPPOL settings
 */

// Load the helper
require_once(__DIR__ . '/../helpers/peppol_input_helper.php');

// Example 1: Basic settings usage
function example_peppol_settings_basic()
{
    // Get current values from options
    $current_scheme = get_option('peppol_company_scheme') ?: '0208';
    $current_identifier = get_option('peppol_company_identifier') ?: '';
    
    echo render_peppol_settings_input($current_scheme, $current_identifier, false);
}

// Example 2: Advanced settings usage with custom configuration
function example_peppol_settings_advanced()
{
    $current_scheme = get_option('peppol_company_scheme') ?: '0208';
    $current_identifier = get_option('peppol_company_identifier') ?: '';
    
    echo render_peppol_scheme_identifier_input([
        'scheme_name' => 'settings[peppol_company_scheme]',
        'identifier_name' => 'settings[peppol_company_identifier]',
        'scheme_value' => $current_scheme,
        'identifier_value' => $current_identifier,
        'label' => 'Company PEPPOL Identifier',
        'help_text' => 'Enter your company\'s PEPPOL participant identifier for electronic document exchange.',
        'required' => true,
        'wrapper_class' => 'form-group col-md-6',
        'container_id' => 'company_peppol_identifier'
    ]);
}

// Example 3: Client portal usage
function example_client_portal_usage()
{
    // This would be used in client portal where clients enter their own PEPPOL data
    echo render_peppol_scheme_identifier_input([
        'scheme_name' => 'client[peppol_scheme]',
        'identifier_name' => 'client[peppol_identifier]',
        'scheme_value' => '',
        'identifier_value' => '',
        'label' => 'Your PEPPOL Identifier',
        'help_text' => 'Enter your PEPPOL participant identifier to enable electronic invoicing.',
        'required' => false,
        'wrapper_class' => 'form-group',
        'container_id' => 'client_peppol_identifier'
    ]);
}

// Example 4: Multiple PEPPOL identifiers (for companies with multiple entities)
function example_multiple_identifiers()
{
    $entities = [
        ['name' => 'Main Company', 'scheme' => '0208', 'identifier' => '0123456789'],
        ['name' => 'Subsidiary', 'scheme' => '0208', 'identifier' => '9876543210']
    ];
    
    foreach ($entities as $index => $entity) {
        echo '<h4>' . $entity['name'] . '</h4>';
        echo render_peppol_scheme_identifier_input([
            'scheme_name' => 'entity[' . $index . '][peppol_scheme]',
            'identifier_name' => 'entity[' . $index . '][peppol_identifier]',
            'scheme_value' => $entity['scheme'],
            'identifier_value' => $entity['identifier'],
            'label' => 'PEPPOL Identifier for ' . $entity['name'],
            'help_text' => 'Enter the PEPPOL identifier for this entity.',
            'container_id' => 'entity_' . $index . '_peppol'
        ]);
    }
}

// Example 5: With preview functionality
function example_with_preview()
{
    echo render_peppol_scheme_identifier_input([
        'scheme_name' => 'settings[peppol_company_scheme]',
        'identifier_name' => 'settings[peppol_company_identifier]',
        'scheme_value' => '0208',
        'identifier_value' => '',
        'label' => 'Company PEPPOL Identifier',
        'help_text' => 'Enter your company\'s PEPPOL identifier. A preview will be shown below.',
        'container_id' => 'company_peppol_with_preview'
    ]);
    
    // Add preview element
    echo '<div class="form-group">
            <label>Preview:</label>
            <div id="company_peppol_with_preview_preview" style="font-family: monospace; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">-</div>
          </div>';
}

/**
 * Integration example for settings page
 */
function integrate_into_settings_page()
{
    // This would replace existing PEPPOL settings fields in your settings view
    
    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo render_peppol_settings_input(
        get_option('peppol_company_scheme'),
        get_option('peppol_company_identifier'),
        true // required
    );
    echo '</div>';
    echo '</div>';
}

/**
 * How to handle form submission
 */
function handle_peppol_settings_submission()
{
    // In your settings controller, the form data will be submitted as:
    // $_POST['settings']['peppol_company_scheme'] = '0208'
    // $_POST['settings']['peppol_company_identifier'] = '0123456789'
    
    // Update options
    if (isset($_POST['settings']['peppol_company_scheme'])) {
        update_option('peppol_company_scheme', $_POST['settings']['peppol_company_scheme']);
    }
    
    if (isset($_POST['settings']['peppol_company_identifier'])) {
        update_option('peppol_company_identifier', $_POST['settings']['peppol_company_identifier']);
    }
}