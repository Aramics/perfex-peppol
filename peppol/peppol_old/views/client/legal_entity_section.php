<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- PEPPOL Legal Entity Button Section (Hidden, will be moved by JS) -->
<div id="peppol-legal-entity-section-source" style="display: none;">
    <div class="peppol-legal-entity-section" style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #007cba; border-radius: 4px;">
        <div class="row">
            <div class="col-md-8">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <i class="fa fa-plug" style="margin-right: 8px; color: #007cba;"></i>
                    <strong><?php echo _l('peppol_legal_entity'); ?></strong>
                    <span style="margin-left: 15px;">Provider: <span class="text-info"><?php echo $provider_name; ?></span></span>
                    <span style="margin-left: 15px;">Status: 
                        <span class="label label-<?php echo $status['registered'] ? 'success' : 'warning'; ?>">
                            <?php echo $status['registered'] ? _l('peppol_legal_entity_status_registered') : _l('peppol_legal_entity_status_none'); ?>
                        </span>
                        <?php if ($status['registered'] && $status['entity_id']): ?>
                            <small style="margin-left: 8px; color: #777;">(ID: <?php echo $status['entity_id']; ?>)</small>
                        <?php endif; ?>
                    </span>
                    <?php if (!$has_required_fields): ?>
                        <br><small style="color: #d9534f; margin-top: 5px; display: block;">
                            <i class="fa fa-exclamation-triangle"></i> 
                            <?php echo _l('peppol_missing_required_fields'); ?>: 
                            <?php if (empty($peppol_identifier)): ?>PEPPOL Identifier<?php endif; ?>
                            <?php if (empty($peppol_identifier) && empty($peppol_scheme)): ?>, <?php endif; ?>
                            <?php if (empty($peppol_scheme)): ?>PEPPOL Scheme<?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <?php if (staff_can('create', 'peppol')): ?>
                <button type="button" 
                        class="btn btn-<?php echo $status['registered'] ? 'info' : 'primary'; ?> btn-sm peppol-register-legal-entity"
                        data-client-id="<?php echo $client->userid; ?>"
                        data-registered="<?php echo $status['registered'] ? 'true' : 'false'; ?>"
                        <?php echo !$has_required_fields ? 'disabled title="PEPPOL Identifier and Scheme are required"' : ''; ?>>
                    <i class="fa fa-<?php echo $status['registered'] ? 'refresh' : 'plus'; ?>"></i> 
                    <?php echo $status['registered'] ? _l('peppol_sync_legal_entity') : _l('peppol_register_legal_entity'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="peppol-legal-entity-messages" style="margin-top: 10px; display: none;">
            <div class="alert" id="peppol-legal-entity-result"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for DOM to be fully loaded
    setTimeout(function() {
        // Find the source section and the target location
        var sourceSection = document.querySelector('#peppol-legal-entity-section-source .peppol-legal-entity-section');
        var targetLocation = document.querySelector('.customer-profile-group-heading');
        
        if (sourceSection && targetLocation) {
            // Clone and teleport the section after the first customer-profile-group-heading
            var clonedSection = sourceSection.cloneNode(true);
            targetLocation.parentNode.insertBefore(clonedSection, targetLocation.nextSibling);
            
            // Remove the original hidden source
            document.getElementById('peppol-legal-entity-section-source').remove();
        }
    }, 100);
    
    // Handle button click (using event delegation since element is moved)
    $(document).on('click', '.peppol-register-legal-entity', function() {
        var clientId = $(this).data('client-id');
        var isRegistered = $(this).data('registered') === 'true';
        var button = $(this);
        
        var confirmMessage = isRegistered ? 
            '<?php echo _l('peppol_confirm_legal_entity_sync'); ?>' : 
            '<?php echo _l('peppol_confirm_legal_entity_registration'); ?>'.replace('%s', '<?php echo $provider_name; ?>');
        
        if (confirm(confirmMessage)) {
            peppolHandleLegalEntityAction(clientId, isRegistered, button);
        }
    });
});

function peppolHandleLegalEntityAction(clientId, isRegistered, button) {
    var originalText = button.html();
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('peppol_processing'); ?>...');
    
    var url = isRegistered ? 
        admin_url + 'peppol/sync_legal_entity/' + clientId :
        admin_url + 'peppol/register_legal_entity';
    
    var data = isRegistered ? 
        { provider: '<?php echo $active_provider; ?>' } :
        { client_id: clientId, provider: '<?php echo $active_provider; ?>' };
    
    $.post(url, data, function(response) {
        peppolShowLegalEntityResult(response);
        
        if (response.success) {
            // Reload page to update status
            setTimeout(function() {
                location.reload();
            }, 2000);
        }
    }, 'json')
    .fail(function() {
        peppolShowLegalEntityResult({
            success: false,
            message: '<?php echo _l('peppol_legal_entity_registration_failed'); ?>'
        });
    })
    .always(function() {
        button.prop('disabled', false).html(originalText);
    });
}

function peppolShowLegalEntityResult(response) {
    var messagesDiv = $('.peppol-legal-entity-messages');
    var resultDiv = $('#peppol-legal-entity-result');
    
    resultDiv.removeClass('alert-success alert-danger alert-warning alert-info');
    resultDiv.addClass('alert-' + (response.success ? 'success' : 'danger'));
    resultDiv.html('<i class="fa fa-' + (response.success ? 'check' : 'times') + '"></i> ' + response.message);
    
    messagesDiv.show();
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        messagesDiv.fadeOut();
    }, 5000);
}
</script>