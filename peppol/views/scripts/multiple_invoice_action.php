<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (staff_can('create', 'peppol') && is_peppol_configured()) { ?>

<div class="btn-group mleft5" id="peppol_bulk_actions_dropdown" data-toggle="tooltip"
    data-title="PEPPOL Bulk Actions">
    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
        aria-expanded="false">
        PEPPOL <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
        <li><a href="javascript:;" onclick="peppolBulkActionByStatus('send_unsent')">
            <?php echo _l('peppol_send_all_unsent'); ?>
        </a></li>
        <li><a href="javascript:;" onclick="peppolBulkActionByStatus('download_sent')">
            <?php echo _l('peppol_download_all_sent'); ?>
        </a></li>
        <li><a href="javascript:;" onclick="peppolBulkActionByStatus('retry_failed')">
            <?php echo _l('peppol_retry_all_failed'); ?>
        </a></li>
    </ul>
</div>
<?php } ?>

<script id="peppol_status_column_script">
'use strict';
document.addEventListener('DOMContentLoaded', function() {
    var invoicesTableSelector = 'table#invoices';
    var isInvoiceView = window.location.href.includes('admin/invoices') && $(invoicesTableSelector).length > 0;
    
    if (isInvoiceView) {
        // Add bulk actions dropdown
        $('#peppol_bulk_actions_dropdown').insertBefore('._buttons .pull-right');
    }
});

// Global function for bulk actions by status
window.peppolBulkActionByStatus = function(action) {
    let operationType = '';
    
    switch(action) {
        case 'send_unsent':
        case 'retry_failed':
            operationType = 'send';
            break;
        case 'download_sent':
            operationType = 'download';
            break;
        default:
            return;
    }
    
    // First get statistics for the action
    $.ajax({
        url: admin_url + 'peppol/bulk_action_stats',
        type: 'POST',
        data: { action: action },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.stats) {
                const stats = response.stats;
                
                if (stats.count === 0) {
                    alert_float('warning', 'No invoices found matching the selected criteria.');
                    return;
                }
                
                // Show confirmation with actual count
                const confirmMessage = `${stats.description}\n\nThis will affect ${stats.count} invoice(s). Continue?`;
                
                if (confirm(confirmMessage)) {
                    if (operationType === 'send') {
                        handleBulkPeppolSend(action);
                    } else if (operationType === 'download') {
                        handleBulkUblDownload(action);
                    }
                }
            } else {
                alert_float('danger', 'Failed to get statistics for this action.');
            }
        },
        error: function() {
            alert_float('danger', 'Failed to get statistics for this action.');
        }
    });
};

const handleBulkPeppolSend = (action) => {
    // First get the count for progress tracking
    $.ajax({
        url: admin_url + 'peppol/bulk_action_stats',
        type: 'POST',
        data: { action: action },
        dataType: 'json',
        success: function(statsResponse) {
            if (statsResponse.success && statsResponse.stats && statsResponse.stats.count > 0) {
                startBulkSendWithProgress(action, statsResponse.stats.count);
            } else {
                alert_float('warning', 'No invoices found to process.');
            }
        },
        error: function() {
            alert_float('danger', 'Failed to get statistics for bulk operation.');
        }
    });
}

const startBulkSendWithProgress = (action, totalCount) => {
    // Initialize progress widget
    showProgressWidget('Preparing to send invoices...', {
        total: totalCount,
        showProgress: true,
        allowCancel: true
    });
    
    // Start the bulk operation
    const operationId = 'bulk_' + Date.now();
    window.peppolBulkOperation = {
        id: operationId,
        cancelled: false,
        cancel: function() {
            this.cancelled = true;
            updateProgressWidget({
                message: 'Cancelling operation...',
                finished: false
            });
            // Note: Backend doesn't support cancellation yet, this is UI-only
        }
    };
    
    // Start the actual sending process
    $.ajax({
        url: admin_url + 'peppol/bulk_send_with_progress',
        type: 'POST',
        data: { 
            action: action,
            operation_id: operationId
        },
        dataType: 'json',
        timeout: 300000, // 5 minutes timeout
        success: function(response) {
            if (response.success) {
                updateProgressWidget({
                    completed: response.progress.total,
                    total: response.progress.total,
                    success: response.progress.success,
                    errors: response.progress.errors,
                    message: 'Operation completed!',
                    finished: true
                });
                
                // Reload the table after a short delay
                setTimeout(() => {
                    if ($('.table-invoices').length) {
                        $('.table-invoices').DataTable().ajax.reload();
                    }
                }, 1000);
                
                alert_float('success', response.message);
            } else {
                hideProgressWidget();
                alert_float('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideProgressWidget();
            if (status === 'timeout') {
                alert_float('warning', 'Operation timed out. Some invoices may have been processed.');
            } else {
                alert_float('danger', '<?php echo _l('peppol_bulk_operation_failed'); ?>');
            }
        }
    });
    
    // Start progress polling
    startProgressPolling(operationId, totalCount);
}

const startProgressPolling = (operationId, totalCount) => {
    let isPolling = false;
    let shouldStop = false;
    
    const poll = () => {
        // Stop polling if operation was cancelled
        if (window.peppolBulkOperation && window.peppolBulkOperation.cancelled) {
            shouldStop = true;
            hideProgressWidget();
            return;
        }
        
        // Stop polling if widget is gone
        if (!$('#peppol-progress-widget').length) {
            shouldStop = true;
            return;
        }
        
        // Don't start new request if one is already in progress
        if (isPolling) {
            return;
        }
        
        isPolling = true;
        
        $.ajax({
            url: admin_url + 'peppol/bulk_progress',
            type: 'POST',
            data: { operation_id: operationId },
            dataType: 'json',
            timeout: 5000, // 5 second timeout for poll requests
            success: function(response) {
                if (response.success && response.progress) {
                    const progress = response.progress;
                    
                    updateProgressWidget({
                        completed: progress.completed,
                        total: totalCount,
                        success: progress.success,
                        errors: progress.errors,
                        message: `Processing invoices... (${progress.completed}/${totalCount})`,
                        finished: false
                    });
                    
                    // Stop polling if completed
                    if (progress.completed >= totalCount) {
                        shouldStop = true;
                        return;
                    }
                }
            },
            error: function(xhr, status, error) {
                // Continue polling on error, but don't spam alerts
                // Only log errors for debugging
                if (console && console.warn) {
                    console.warn('Peppol progress polling error:', status, error);
                }
            },
            complete: function() {
                isPolling = false;
                
                // Schedule next poll only if not stopped
                if (!shouldStop) {
                    setTimeout(poll, 1000); // Wait 1 second before next poll
                }
            }
        });
    };
    
    // Start the first poll
    poll();
}

const handleBulkUblDownload = (action) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = admin_url + 'peppol/bulk_download_ubl';
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
    
    // Open in new window
    form.target = '_blank';
    form.submit();
    document.body.removeChild(form);

    alert_float('info', '<?php echo _l('peppol_preparing_download'); ?>');
}

// Advanced progress widget for bulk operations
const showProgressWidget = (message, options = {}) => {
    const {
        total = 0,
        showProgress = false,
        allowCancel = false
    } = options;
    
    const progressBarHtml = showProgress ? `
        <div class="progress" style="height: 6px; margin-top: 10px; margin-bottom: 5px;">
            <div id="peppol-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
        <div id="peppol-progress-details" style="font-size: 11px; color: #666;">
            <span id="peppol-progress-text">Starting...</span>
            <span class="pull-right" id="peppol-progress-counter">0 / ${total}</span>
        </div>
    ` : '';
    
    const cancelButtonHtml = allowCancel ? `
        <button type="button" id="peppol-cancel-btn" class="btn btn-xs btn-danger pull-right" style="margin-left: 10px;">
            Cancel
        </button>
    ` : '';
    
    const progressWidgetHtml = `
        <div id="peppol-progress-widget" class="panel panel-default" style="position: fixed; top: 80px; right: 20px; z-index: 9999; width: 350px; margin: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
            <div class="panel-body" style="padding: 15px;">
                <div style="display: flex; align-items: center; margin-bottom: ${showProgress ? '5px' : '0'};">
                    <i class="fa fa-spinner fa-spin" style="margin-right: 10px; color: #337ab7;"></i>
                    <span id="peppol-progress-message" style="flex: 1; font-weight: 500;">${message}</span>
                    ${cancelButtonHtml}
                </div>
                ${progressBarHtml}
            </div>
        </div>
    `;
    
    $('#peppol-progress-widget').remove();
    $('body').append(progressWidgetHtml);
    
    // Initialize cancel functionality
    if (allowCancel) {
        $('#peppol-cancel-btn').on('click', function() {
            if (window.peppolBulkOperation && window.peppolBulkOperation.cancel) {
                window.peppolBulkOperation.cancel();
            }
        });
    }
}

const updateProgressWidget = (progress) => {
    const widget = $('#peppol-progress-widget');
    if (!widget.length) return;
    
    const {
        completed = 0,
        total = 0,
        success = 0,
        errors = 0,
        message = '',
        finished = false
    } = progress;
    
    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    
    // Update progress bar
    $('#peppol-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
    
    // Update text
    if (message) {
        $('#peppol-progress-message').text(message);
    }
    
    // Update counter
    $('#peppol-progress-counter').text(`${completed} / ${total}`);
    
    // Update details
    const statusText = finished 
        ? `Complete: ${success} successful, ${errors} failed`
        : `Processing... (${success} successful, ${errors} failed)`;
    $('#peppol-progress-text').text(statusText);
    
    // Change bar color based on status
    const progressBar = $('#peppol-progress-bar');
    progressBar.removeClass('progress-bar-danger progress-bar-warning progress-bar-success');
    
    if (finished) {
        if (errors === 0) {
            progressBar.addClass('progress-bar-success');
        } else if (success === 0) {
            progressBar.addClass('progress-bar-danger');
        } else {
            progressBar.addClass('progress-bar-warning');
        }
        progressBar.removeClass('progress-bar-striped progress-bar-animated');
        
        // Auto-hide after success
        if (errors === 0) {
            setTimeout(() => hideProgressWidget(), 2000);
        }
    }
}

const hideProgressWidget = () => {
    $('#peppol-progress-widget').fadeOut(300, function() {
        $(this).remove();
    });
}
</script>