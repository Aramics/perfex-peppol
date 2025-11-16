<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Input Helper - Reusable scheme:identifier input component
 */

/**
 * Get PEPPOL scheme options for autocomplete/dropdown
 * 
 * @return array Array of scheme options
 */
function get_peppol_scheme_options()
{
    return [
        '0002' => '0002 - SIRENE',
        '0007' => '0007 - ODETTE',
        '0009' => '0009 - EAN Location Code',
        '0037' => '0037 - LY (Norwegian)',
        '0060' => '0060 - DUNS (Data Universal Numbering System)',
        '0088' => '0088 - GLN (Global Location Number)',
        '0096' => '0096 - GTIN (Global Trade Item Number)',
        '0135' => '0135 - SIA Object Identifiers',
        '0142' => '0142 - SECETI Object Identifiers',
        '0151' => '0151 - Australian Business Number',
        '0183' => '0183 - Swiss Enterprise Identification Number',
        '0184' => '0184 - Danish CVR / DIGSTØ Identifier',
        '0190' => '0190 - Dutch Originator\'s Identification Number',
        '0191' => '0191 - Centre for Research Libraries',
        '0192' => '0192 - Belgian Enterprise Number / CBE',
        '0193' => '0193 - UBL.BE Party Identifier',
        '0195' => '0195 - Singapore UEN',
        '0196' => '0196 - Kennitala (Iceland)',
        '0198' => '0198 - ERSTORG',
        '0199' => '0199 - Legal Entity Identifier (LEI)',
        '0200' => '0200 - Legal entity code (Lithuania)',
        '0201' => '0201 - Codice Univoco Unità Organizzativa iPA',
        '0202' => '0202 - Indirizzo Telematico Ente',
        '0203' => '0203 - Codice Fiscale',
        '0204' => '0204 - Partita IVA',
        '0208' => '0208 - Leitweg-ID (Germany)',
        '0209' => '0209 - Entreprise ID',
        '0210' => '0210 - CODICE IPA',
        '0211' => '0211 - CODICE DESTINATARIO',
        '0212' => '0212 - Leitweg-ID (Austria)',
        '0213' => '0213 - Belgian KBO Number / CBE Number',
        '9901' => '9901 - Danish Ministry ID',
        '9902' => '9902 - Norwegian ID',
        '9904' => '9904 - Hungarian VAT',
        '9905' => '9905 - PEPPOL Contract Party ID',
        '9906' => '9906 - Andes',
        '9907' => '9907 - Andorra VAT',
        '9910' => '9910 - Hungary VAT',
        '9912' => '9912 - European Medicines Agency',
        '9913' => '9913 - Business Registers Network',
        '9914' => '9914 - Austrian Organisation Code',
        '9915' => '9915 - Austrian Ersatzverfahren ID',
        '9918' => '9918 - SWIFT BIC Code',
        '9919' => '9919 - German Company Register',
        '9920' => '9920 - Spanish VAT',
        '9922' => '9922 - Andorra Registration',
        '9923' => '9923 - French Enterprise Number',
        '9924' => '9924 - RETGS (Galicia)',
        '9925' => '9925 - VAT number (Generic)',
        '9926' => '9926 - Belgian VAT Number',
        '9955' => '9955 - Norwegian Organization Number',
        '9956' => '9956 - Swedish Organization Number',
        '9957' => '9957 - French VAT',
        '9958' => '9958 - German VAT'
    ];
}

/**
 * Render PEPPOL scheme:identifier input component
 * 
 * @param array $config Configuration options
 * @return string HTML output
 */
function render_peppol_scheme_identifier_input($config = [])
{
    // Default configuration
    $defaults = [
        'scheme_name' => 'peppol_scheme',
        'identifier_name' => 'peppol_identifier',
        'scheme_value' => '0208',
        'identifier_value' => '',
        'label' => 'PEPPOL Identifier',
        'help_text' => 'Enter the PEPPOL participant identifier. Format: scheme:identifier (e.g., 0208:0123456789)',
        'scheme_placeholder' => '0208',
        'identifier_placeholder' => '0123456789',
        'required' => false,
        'wrapper_class' => 'form-group',
        'show_edit_links' => false,
        'edit_links' => [],
        'enable_autocomplete' => true,
        'container_id' => 'peppol_scheme_identifier_' . uniqid()
    ];
    
    // Merge with provided config
    $config = array_merge($defaults, $config);
    
    // Extract values for easier access
    extract($config);
    
    // Generate unique IDs for inputs
    $scheme_id = $container_id . '_scheme';
    $identifier_id = $container_id . '_identifier';
    $datalist_id = $container_id . '_datalist';
    
    // Build edit links HTML if provided
    $edit_links_html = '';
    if ($show_edit_links && !empty($edit_links)) {
        $links_parts = [];
        foreach ($edit_links as $link) {
            $links_parts[] = $link;
        }
        $edit_links_html = '<div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">' . 
                          implode('<span style="color: #666; font-weight: bold;">:</span>', $links_parts) . 
                          '</div>';
    }
    
    // Required attribute
    $required_attr = $required ? 'required' : '';
    $required_asterisk = $required ? ' <span style="color: red;">*</span>' : '';
    
    // Build the HTML
    $html = '
    <div class="' . $wrapper_class . '" id="' . $container_id . '">
        ' . $edit_links_html . '
        <label for="' . $identifier_id . '" class="control-label">' . $label . $required_asterisk . '</label>
        <div class="input-group" style="display: flex; align-items: stretch;">
            <input type="text" 
                   id="' . $scheme_id . '" 
                   name="' . $scheme_name . '"
                   class="form-control" 
                   placeholder="' . $scheme_placeholder . '" 
                   value="' . htmlspecialchars($scheme_value) . '"
                   list="' . $datalist_id . '"
                   style="max-width: 100px; min-width: 100px; flex: 0 0 100px; border-right: 0; border-radius: 4px 0 0 4px;"
                   ' . $required_attr . '>
            <span class="input-group-addon" style="border-left: 0; border-right: 0; background: #f1f1f1; padding: 6px 8px; border-radius: 0; flex: 0 0 auto;">:</span>
            <input type="text" 
                   id="' . $identifier_id . '" 
                   name="' . $identifier_name . '"
                   class="form-control" 
                   placeholder="' . $identifier_placeholder . '" 
                   value="' . htmlspecialchars($identifier_value) . '"
                   style="border-left: 0; flex: 1; border-radius: 0 4px 4px 0;"
                   ' . $required_attr . '>
        </div>
        
        <!-- PEPPOL scheme datalist -->
        <datalist id="' . $datalist_id . '">';
    
    // Add all scheme options
    foreach (get_peppol_scheme_options() as $code => $description) {
        $html .= '<option value="' . $code . '">' . $description . '</option>';
    }
    
    $html .= '</datalist>
        <small class="help-block text-muted">' . $help_text . '</small>
    </div>
    
    <script>
    // Add live preview and autocomplete functionality
    document.addEventListener("DOMContentLoaded", function() {
        var schemeInput = document.getElementById("' . $scheme_id . '");
        var identifierInput = document.getElementById("' . $identifier_id . '");
        var previewElement = document.getElementById("' . $container_id . '_preview");
        
        // PEPPOL scheme options for autocomplete
        var peppolSchemes = ' . json_encode(get_peppol_scheme_options()) . ';
        
        // Add live preview functionality if preview element exists
        if (previewElement) {
            function updatePreview() {
                var scheme = schemeInput.value.trim();
                var identifier = identifierInput.value.trim();
                var preview = "-";
                
                if (scheme && identifier) {
                    preview = scheme + ":" + identifier;
                } else if (identifier) {
                    preview = "[scheme]:" + identifier;
                } else if (scheme) {
                    preview = scheme + ":[identifier]";
                }
                
                previewElement.textContent = preview;
            }
            
            schemeInput.addEventListener("input", updatePreview);
            identifierInput.addEventListener("input", updatePreview);
            updatePreview(); // Initial update
        }
    });
    
    </script>';
    
    return $html;
}

/**
 * Render PEPPOL scheme:identifier input for settings page
 * 
 * @param string $scheme_value Current scheme value
 * @param string $identifier_value Current identifier value
 * @param bool $required Whether fields are required
 * @return string HTML output
 */
function render_peppol_settings_input($scheme_value = '0208', $identifier_value = '', $required = false)
{
    return render_peppol_scheme_identifier_input([
        'scheme_name' => 'settings[peppol_company_scheme]',
        'identifier_name' => 'settings[peppol_company_identifier]', 
        'scheme_value' => $scheme_value,
        'identifier_value' => $identifier_value,
        'label' => 'Company PEPPOL Identifier',
        'help_text' => 'Enter your company\'s PEPPOL participant identifier. Start typing in the scheme field to see suggestions. Format: scheme:identifier (e.g., 0208:0123456789)',
        'required' => $required,
        'enable_autocomplete' => true,
        'container_id' => 'peppol_company_identifier'
    ]);
}

/**
 * JavaScript function to replace existing custom fields with PEPPOL component
 * 
 * @return string JavaScript code
 */
function get_peppol_custom_field_replacement_js()
{
    return '
    function replacePeppolCustomFields() {
        // Find PEPPOL custom field containers
        var identifierContainer = $("div[app-field-wrapper*=\\"custom_fields[customers]\\"]").filter(function() {
            return $(this).find("label").text().includes("PEPPOL Identifier");
        }).parent();
        
        var schemeContainer = $("div[app-field-wrapper*=\\"custom_fields[customers]\\"]").filter(function() {
            return $(this).find("label").text().includes("PEPPOL Scheme");
        }).parent();
        
        if (identifierContainer.length && schemeContainer.length) {
            // Get current values and field names from existing inputs
            var identifierInput = identifierContainer.find("input[type=text]");
            var schemeInput = schemeContainer.find("input[type=text]");
            
            var currentIdentifier = identifierInput.val() || "";
            var currentScheme = schemeInput.val() || "0208";
            var identifierName = identifierInput.attr("name") || "";
            var schemeName = schemeInput.attr("name") || "";
            
            // Get edit links
            var schemeEditLink = schemeContainer.find(".custom-field-inline-edit-link").get(0)?.outerHTML || "";
            var identifierEditLink = identifierContainer.find(".custom-field-inline-edit-link").get(0)?.outerHTML || "";
            
            // Generate unique IDs for the replacement
            var schemeId = "peppol_scheme_" + Math.random().toString(36).substr(2, 9);
            var identifierId = "peppol_identifier_" + Math.random().toString(36).substr(2, 9);
            var datalistId = "peppol_datalist_" + Math.random().toString(36).substr(2, 9);
            
            // Create replacement HTML using the helper component structure
            var replacementHtml = `
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                            ${schemeEditLink}
                            <span style="color: #666; font-weight: bold;">:</span>
                            ${identifierEditLink}
                            <label class="control-label tw-mb-0">PEPPOL Identifier</label>
                        </div>
                        <div class="input-group" style="display: flex; align-items: stretch;">
                            <input type="text" 
                                   id="${schemeId}"
                                   name="${schemeName}"
                                   class="form-control" 
                                   placeholder="0208" 
                                   value="${currentScheme}"
                                   list="${datalistId}"
                                   style="max-width: 100px; min-width: 100px; flex: 0 0 100px; border-right: 0; border-radius: 4px 0 0 4px;">
                            <span class="input-group-addon" style="border-left: 0; border-right: 0; background: #f1f1f1; padding: 6px 8px; border-radius: 0; flex: 0 0 auto;">:</span>
                            <input type="text" 
                                   id="${identifierId}"
                                   name="${identifierName}"
                                   class="form-control" 
                                   placeholder="0123456789" 
                                   value="${currentIdentifier}"
                                   style="border-left: 0; flex: 1; border-radius: 0 4px 4px 0;">
                        </div>
                        
                        <!-- PEPPOL scheme datalist -->
                        <datalist id="${datalistId}">`;
            
            // Add all scheme options to the replacement HTML
            var schemes = ' . json_encode(get_peppol_scheme_options()) . ';
            for (var code in schemes) {
                replacementHtml += `<option value="${code}">${schemes[code]}</option>`;
            }
            
            replacementHtml += `</datalist>`;
                        <small class="help-block text-muted">
                            Enter the PEPPOL participant identifier. Start typing in the scheme field to see suggestions. Format: scheme:identifier (e.g., 0208:0123456789)
                        </small>
                    </div>
                </div>
            `;
            
            // Replace both containers
            identifierContainer.replaceWith(replacementHtml);
            schemeContainer.remove();
            
            console.log("PEPPOL custom fields replaced with reusable component using native datalist");
            
            console.log("PEPPOL custom fields replaced with reusable component including autocomplete");
        }
    }';
}