<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
/**
 * Unified PEPPOL document bulk actions
 * 
 * Required variables:
 * - $document_type: 'invoice' or 'credit_note'
 */

// Locker
if (isset($GLOBALS['peppol_progress_widget_added'])) return;
$GLOBALS['peppol_progress_widget_added'] = true;

// Document type configuration
$config = [
    'invoice' => [
        'dropdown_id' => 'peppol-bulk-actions-dropdown',
        'function_name' => 'peppolBulkAction',
        'stats_url' => 'peppol/bulk_action_stats',
        'bulk_send_url' => 'peppol/bulk_send',
        'bulk_download_url' => 'peppol/bulk_download_ubl',
        'table_selector' => '.table-invoices',
        'insert_before' => '._buttons .pull-right:first',
        'send_unsent_lang' => 'peppol_send_all_unsent',
        'retry_failed_lang' => 'peppol_retry_all_failed',
        'download_sent_lang' => 'peppol_download_all_sent',
        'item_name_lang' => 'peppol_invoices',
        'preparing_lang' => 'peppol_preparing_invoices'
    ],
    'credit_note' => [
        'dropdown_id' => 'peppol-credit-note-bulk-actions-dropdown',
        'function_name' => 'peppolCreditNoteBulkAction',
        'stats_url' => 'peppol/credit_note_bulk_action_stats',
        'bulk_send_url' => 'peppol/credit_note_bulk_send',
        'bulk_download_url' => 'peppol/credit_note_bulk_download_ubl',
        'table_selector' => '.table-credit-notes',
        'insert_before' => '._buttons .pull-right:first',
        'send_unsent_lang' => 'peppol_send_all_unsent_credit_notes',
        'retry_failed_lang' => 'peppol_retry_all_failed_credit_notes',
        'download_sent_lang' => 'peppol_download_all_sent_credit_note_ubl',
        'item_name_lang' => 'peppol_credit_notes',
        'preparing_lang' => 'peppol_preparing_credit_notes'
    ]
];

$cfg = $config[$document_type];
?>

<!-- PEPPOL <?php echo ucfirst(str_replace('_', ' ', $document_type)); ?> Bulk Actions Dropdown -->
<div class="btn-group mleft5" id="<?php echo $cfg['dropdown_id']; ?>" style="display: none;">
    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
        aria-expanded="false">
        <i class="fa fa-paper-plane"></i> <?php echo _l('peppol_bulk_actions'); ?> <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
        <li><a href="#" onclick="<?php echo $cfg['function_name']; ?>('send_unsent'); return false;">
                <?php echo _l($cfg['send_unsent_lang']); ?>
            </a></li>
        <li><a href="#" onclick="<?php echo $cfg['function_name']; ?>('retry_failed'); return false;">
                <?php echo _l($cfg['retry_failed_lang']); ?>
            </a></li>
        <li role="separator" class="divider"></li>
        <li><a href="#" onclick="<?php echo $cfg['function_name']; ?>('download_sent'); return false;">
                <?php echo _l($cfg['download_sent_lang']); ?>
            </a></li>
    </ul>
</div>

<!-- Progress Widget (shared across all document types) -->
<div id="peppol-progress-widget" class="panel panel-default"
    style="display: none; position: fixed; top: 80px; right: 20px; z-index: 9999; width: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
    <div class="panel-body" style="padding: 15px;">
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
            <i class="fa fa-spinner fa-spin" style="margin-right: 10px; color: #337ab7;"></i>
            <span id="peppol-progress-message"
                style="flex: 1; font-weight: 500;"><?php echo _l('peppol_processing'); ?></span>
            <button type="button" id="peppol-cancel-btn" class="btn btn-xs btn-danger"
                style="display: none;"><?php echo _l('cancel'); ?></button>
        </div>
        <div class="progress" style="height: 6px; margin-bottom: 5px;">
            <div id="peppol-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                role="progressbar" style="width: 0%"></div>
        </div>
        <div style="font-size: 11px; color: #666;">
            <span id="peppol-progress-text"><?php echo _l('peppol_starting'); ?></span>
            <span class="pull-right" id="peppol-progress-counter">0 / 0</span>
        </div>
    </div>
</div>

<script>
// Shared progress widget functions (only added once)
window.showProgressWidget = function(message, total) {
    $('#peppol-progress-message').text(message);
    $('#peppol-progress-counter').text('0 / ' + total);
    $('#peppol-progress-text').text('<?php echo _l('peppol_starting'); ?>');
    $('#peppol-progress-bar').css('width', '0%');
    $('#peppol-progress-widget').fadeIn(300);
};

window.updateProgressWidget = function(progress, message) {
    const percentage = progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0;

    $('#peppol-progress-bar').css('width', percentage + '%');
    $('#peppol-progress-counter').text(progress.completed + ' / ' + progress.total);

    if (message) {
        $('#peppol-progress-message').text(message);
    }

    let statusText = '<?php echo _l('peppol_success'); ?>: ' + progress.success;
    if (progress.errors > 0) {
        statusText += ', <?php echo _l('peppol_failed'); ?>: ' + progress.errors;
    }
    $('#peppol-progress-text').text(statusText);

    // Change progress bar color based on results
    const progressBar = $('#peppol-progress-bar');
    progressBar.removeClass('progress-bar-success progress-bar-warning progress-bar-danger');

    if (progress.errors === 0) {
        progressBar.addClass('progress-bar-success');
    } else if (progress.success === 0) {
        progressBar.addClass('progress-bar-danger');
    } else {
        progressBar.addClass('progress-bar-warning');
    }
};

window.hideProgressWidget = function() {
    $('#peppol-progress-widget').fadeOut(300);
};
</script>

<script>
$(document).ready(function() {
    // Show PEPPOL bulk actions dropdown on appropriate page
    if ($('<?php echo $cfg['table_selector']; ?>').length > 0) {
        $('#<?php echo $cfg['dropdown_id']; ?>').insertBefore('<?php echo $cfg['insert_before']; ?>').show();
    }
});

// Document-specific bulk action function
window.<?php echo $cfg['function_name']; ?> = function(action) {
    // Get stats first
    $.ajax({
        url: admin_url + '<?php echo $cfg['stats_url']; ?>',
        type: 'POST',
        data: {
            action: action
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.stats && response.stats.count > 0) {
                const confirmMessage =
                    `${response.stats.description}\n\n<?php echo _l('peppol_will_affect'); ?> ${response.stats.count} <?php echo _l($cfg['item_name_lang']); ?>. <?php echo _l('continue'); ?>?`;

                if (confirm(confirmMessage)) {
                    if (action === 'download_sent') {
                        peppolBulkDownload<?php echo ucfirst(str_replace('_', '', $document_type)); ?>(
                            action);
                    } else {
                        peppolBulkSend<?php echo ucfirst(str_replace('_', '', $document_type)); ?>(
                            action, response.stats.count);
                    }
                }
            } else {
                alert('<?php echo _l('peppol_no_invoices_found'); ?>');
            }
        },
        error: function() {
            alert('<?php echo _l('peppol_error_getting_stats'); ?>');
        }
    });
};

window.peppolBulkSend<?php echo ucfirst(str_replace('_', '', $document_type)); ?> = function(action, totalCount) {
    showProgressWidget('<?php echo _l($cfg['preparing_lang']); ?>', totalCount);

    $.ajax({
        url: admin_url + '<?php echo $cfg['bulk_send_url']; ?>',
        type: 'POST',
        data: {
            action: action
        },
        dataType: 'json',
        timeout: 300000, // 5 minutes
        success: function(response) {
            if (response.success || (response.progress && response.progress.success > 0)) {
                updateProgressWidget(response.progress, '<?php echo _l('peppol_completed'); ?>');
                setTimeout(function() {
                    hideProgressWidget();
                    // Reload table
                    if ($('<?php echo $cfg['table_selector']; ?>').length && $(
                            '<?php echo $cfg['table_selector']; ?>').DataTable()) {
                        $('<?php echo $cfg['table_selector']; ?>').DataTable().ajax.reload();
                    }

                    // Show detailed results if there are errors
                    if (response.errors && response.errors.length > 0) {
                        showBulkOperationResults(response.progress, response.errors, response
                            .message, '<?php echo $document_type; ?>');
                    } else {
                        alert_float('success', response.message ||
                            '<?php echo _l('peppol_operation_completed'); ?>');
                    }
                }, 2000);
            } else {
                hideProgressWidget();

                if (response.errors && response.errors.length > 0) {
                    showBulkOperationResults(response.progress, response.errors, response.message,
                        '<?php echo $document_type; ?>');
                } else {
                    alert_float('danger', response.message ||
                        '<?php echo _l('peppol_operation_failed'); ?>');
                }
            }
        },
        error: function(xhr, status) {
            hideProgressWidget();
            if (status === 'timeout') {
                alert_float('warning', '<?php echo _l('peppol_operation_timeout'); ?>');
            } else {
                alert_float('danger', '<?php echo _l('peppol_operation_failed'); ?>');
            }
        }
    });
};

window.peppolBulkDownload<?php echo ucfirst(str_replace('_', '', $document_type)); ?> = function(action) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = admin_url + '<?php echo $cfg['bulk_download_url']; ?>';
    form.target = '_blank';
    form.style.display = 'none';

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = csrfData.token_name;
    csrfInput.value = csrfData.hash;
    form.appendChild(csrfInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    alert_float('info', '<?php echo _l('peppol_preparing_download'); ?>');
};

// Function to show detailed bulk operation results
function showBulkOperationResults(progress, errors, message, documentType) {
    var modalContent = '<div class="modal fade" id="peppolBulkResultsModal" tabindex="-1">' +
        '<div class="modal-dialog modal-lg">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<button type="button" class="close" data-dismiss="modal" aria-label="<?php echo _l('close'); ?>"><span aria-hidden="true">&times;</span></button>' +
        '<h4 class="modal-title">' +
        '<i class="fa fa-list-alt"></i> ' +
        '<?php echo _l('peppol_bulk_operation_results'); ?> (' + documentType + ')' +
        '</h4>' +
        '</div>' +
        '<div class="modal-body">';

    // Summary section
    modalContent += '<div class="row mb-3">' +
        '<div class="col-sm-3 text-center">' +
        '<div class="bg-info text-white p-3 tw-rounded-md">' +
        '<span class="tw-text-3xl tw-font-bold tw-block">' + (progress ? progress.total : 0) + '</span>' +
        '<p><?php echo _l('peppol_total_processed'); ?></p>' +
        '</div>' +
        '</div>' +
        '<div class="col-sm-3 text-center">' +
        '<div class="bg-success text-white p-3 tw-rounded-md">' +
        '<span class="tw-text-3xl tw-font-bold tw-block">' + (progress ? progress.success : 0) + '</span>' +
        '<p><?php echo _l('peppol_successful'); ?></p>' +
        '</div>' +
        '</div>' +
        '<div class="col-sm-3 text-center">' +
        '<div class="bg-danger text-white p-3 tw-rounded-md">' +
        '<span class="tw-text-3xl tw-font-bold tw-block">' + (progress ? progress.errors : 0) + '</span>' +
        '<p><?php echo _l('peppol_failed'); ?></p>' +
        '</div>' +
        '</div>' +
        '<div class="col-sm-3 text-center">' +
        '<div class="bg-warning text-white p-3 tw-rounded-md">' +
        '<span class="tw-text-3xl tw-font-bold tw-block">' + Math.round((progress && progress.total > 0) ? (progress
            .success / progress.total) * 100 : 0) + '%</span>' +
        '<p><?php echo _l('peppol_success_rate'); ?></p>' +
        '</div>' +
        '</div>' +
        '</div>';

    if (message) {
        modalContent += '<div class="alert alert-info tw-mt-4 tw-mb-8">' +
            '<i class="fa fa-info-circle"></i> ' + escapeHtml(message) +
            '</div>';
    }

    // Errors section
    if (errors && errors.length > 0) {
        modalContent += '<div class="row mt-3">' +
            '<div class="col-md-12">' +
            '<div class="panel panel-danger">' +
            '<div class="panel-heading">' +
            '<h4><?php echo _l('peppol_error_details'); ?> (' + errors.length +
            ' <?php echo _l('peppol_errors_shown'); ?>)</h4>' +
            '</div>' +
            '<div class="panel-body" style="max-height: 300px; overflow-y: auto;">';

        errors.forEach(function(error, index) {
            modalContent += '<div class="alert alert-danger">' +
                '<small class="text-muted">#' + (index + 1) + '</small><br>' +
                escapeHtml(error) +
                '</div>';
        });

        modalContent += '</div></div></div></div>';
    }

    modalContent += '</div>' +
        '</div>' +
        '</div>' +
        '</div>';

    // Remove existing modal and add new one
    $('#peppolBulkResultsModal').remove();
    $('body').append(modalContent);
    $('#peppolBulkResultsModal').modal('show');
}

// Utility function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>