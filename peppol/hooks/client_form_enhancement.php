<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Hook to replace PEPPOL custom fields with scheme:identifier format
hooks()->add_action('app_admin_head', 'peppol_replace_custom_fields');

/**
 * Replace PEPPOL custom fields with combined scheme:identifier input
 */
function peppol_replace_custom_fields()
{
    // Only load on client pages
    $CI = &get_instance();
    if ($CI->router->fetch_class() !== 'clients') {
        return;
    }

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            peppol_replace_fields();
        }, 500);
    });
    
    function peppol_replace_fields() {
        // Find PEPPOL custom field containers
        var identifierContainer = $(\'div[app-field-wrapper="custom_fields[customers][25]"]\').parent();
        var schemeContainer = $(\'div[app-field-wrapper="custom_fields[customers][26]"]\').parent();
        
        if (!identifierContainer.length || !schemeContainer.length) {
            console.log("PEPPOL fields not found, trying fallback selector");
            // Fallback: search by label text
            identifierContainer = $(\'.form-group\').filter(function() {
                return $(this).find(\'label\').text().includes(\'PEPPOL Identifier\');
            }).parent();
            
            schemeContainer = $(\'.form-group\').filter(function() {
                return $(this).find(\'label\').text().includes(\'PEPPOL Scheme\');
            }).parent();
        }
        
        if (identifierContainer.length && schemeContainer.length) {
            // Get current values and edit links
            var currentIdentifier = $(\'input[name="custom_fields[customers][25]"]\').val() || "";
            var currentScheme = $(\'input[name="custom_fields[customers][26]"]\').val() || "0208";
            
            // Extract edit links from original containers
            var schemeEditLink = schemeContainer.find(\'.custom-field-inline-edit-link\').get(0).outerHTML || "";
            var identifierEditLink = identifierContainer.find(\'.custom-field-inline-edit-link\').get(0).outerHTML || "";
            
            // Create combined PEPPOL identifier input
            var peppolHtml = `
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="peppol_combined_identifier" class="control-label">
                            ' . _l('peppol_client_identifier') . '
                        </label>
                        <div class="tw-flex tw-items-center tw-gap-2">
                            ${schemeEditLink}
                            <div class="input-group tw-flex-1">
                                <input type="text" 
                                       id="peppol_scheme_input" 
                                       class="form-control" 
                                       placeholder="0208" 
                                       value="${currentScheme}"
                                       style="max-width: 100px; border-right: 0;">
                                <div class="input-group-addon" style="border-left: 0; border-right: 0; background: #f1f1f1;">:</div>
                                
                                <!-- Hidden fields for form submission -->
                                <input type="hidden" name="custom_fields[customers][25]" id="hidden_identifier" value="${currentIdentifier}">
                                <input type="hidden" name="custom_fields[customers][26]" id="hidden_scheme" value="${currentScheme}">
                                
                                <input type="text" 
                                       id="peppol_identifier_input" 
                                       class="form-control" 
                                       placeholder="0123456789" 
                                       value="${currentIdentifier}"
                                       style="border-left: 0;">
                            </div>
                            ${identifierEditLink}
                        </div>
                        <small class="help-block text-muted">
                            ' . _l('peppol_client_identifier_help') . ' Format: scheme:identifier (e.g., 0208:0123456789)
                        </small>
                    </div>
                </div>
            `;
            
            // Replace both containers with our combined field
            identifierContainer.replaceWith(peppolHtml);
            schemeContainer.remove();
            
            // Add event handlers to sync hidden fields
            $(document).on("input", "#peppol_scheme_input", function() {
                $("#hidden_scheme").val($(this).val());
            });
            
            $(document).on("input", "#peppol_identifier_input", function() {
                $("#hidden_identifier").val($(this).val());
            });
            
            console.log("PEPPOL fields successfully replaced with scheme:identifier format");
        } else {
            console.log("PEPPOL fields not found for replacement");
        }
    }
    </script>';
}