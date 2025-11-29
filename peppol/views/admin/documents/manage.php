<?php
defined('BASEPATH') or exit('No direct script access allowed');
$card_class = 'panel_s panel-body tw-px-4 tw-py-3';
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <!-- Page Header -->
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
            <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                <?php echo _l('peppol_documents'); ?>
            </h4>
            <?php if (staff_can('delete', 'peppol_logs')) { ?>
            <a class="btn btn-secondary" href="<?= admin_url('peppol/process_notifications/manual'); ?>">
                <i class="fa fa-reload"></i>
                <?php echo _l('peppol_process_notifications'); ?>
            </a>
            <?php } ?>
        </div>

        <!-- Statistics Cards -->
        <div class="row tw-mb-3">
            <!-- Document Statistics -->
            <div class="col-md-6">
                <div class="<?= $card_class; ?>">
                    <div class="widget-drilldown">
                        <h4 class="tw-mt-0"><?php echo _l('peppol_invoice_documents'); ?></h4>
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <span class="tw-font-semibold tw-text-lg text-primary">
                                <?php echo $invoice_stats['total'] ?? 0; ?>
                            </span>
                            <i class="fa fa-file-text-o tw-text-2xl text-muted"></i>
                        </div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                            Sent:
                            <?php echo ($invoice_stats['SENT'] ?? 0) + ($invoice_stats['TECHNICAL_ACCEPTANCE'] ?? 0); ?>
                            |
                            Failed: <?php echo $invoice_stats['SEND_FAILED'] ?? 0; ?> |
                            Received: <?php echo $invoice_stats['RECEIVED'] ?? 0; ?> |
                            Paid: <?php echo $invoice_stats['FULLY_PAID'] ?? 0; ?> |
                            Rejected: <?php echo $invoice_stats['REJECTED'] ?? 0; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="<?= $card_class; ?>">
                    <div class="widget-drilldown">
                        <h4 class="tw-mt-0"><?php echo _l('peppol_credit_note_documents'); ?></h4>
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <span class="tw-font-semibold tw-text-lg text-info">
                                <?php echo $credit_note_stats['total'] ?? 0; ?>
                            </span>
                            <i class="fa fa-file-o tw-text-2xl text-muted"></i>
                        </div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                            Sent:
                            <?php echo ($credit_note_stats['SENT'] ?? 0) + ($credit_note_stats['TECHNICAL_ACCEPTANCE'] ?? 0); ?>
                            |
                            Failed: <?php echo $credit_note_stats['SEND_FAILED'] ?? 0; ?> |
                            Received: <?php echo $credit_note_stats['received'] ?? 0; ?> |
                            Accepted: <?php echo $credit_note_stats['ACCEPTED'] ?? 0; ?> |
                            Rejected: <?php echo $credit_note_stats['REJECTED'] ?? 0; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Statistics Cards -->
            <!-- Total Expenses Overview -->
            <div class="col-md-4">
                <div class="<?= $card_class; ?>">
                    <div class="widget-drilldown">
                        <h4 class="tw-mt-0"><?php echo _l('peppol_total_expenses_created'); ?></h4>
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <span class="tw-font-semibold tw-text-lg text-success">
                                <?php echo $expense_stats['total_expenses'] ?? 0; ?>
                            </span>
                            <i class="fa fa-money tw-text-2xl text-muted"></i>
                        </div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                            <?php echo _l('peppol_total_expense_amount'); ?>:
                            <span class="tw-font-medium">
                                <?php echo app_format_money($expense_stats['total_amount'] ?? 0, get_base_currency()); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Expenses -->
            <div class="col-md-4">
                <div class="<?= $card_class; ?>">
                    <div class="widget-drilldown">
                        <h4 class="tw-mt-0"><?php echo _l('peppol_invoice_expenses'); ?></h4>
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <span class="tw-font-semibold tw-text-lg text-primary">
                                <?php echo app_format_money($expense_stats['invoice_amount'] ?? 0, get_base_currency()); ?>
                            </span>
                            <i class="fa fa-file-text-o tw-text-2xl text-muted"></i>
                        </div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                            <?php echo $expense_stats['invoice_expenses'] ?? 0; ?>
                            <?php echo _l('peppol_invoice_expenses_subtitle'); ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Credit Note Expenses -->
            <div class="col-md-4">
                <div class="<?= $card_class; ?>">
                    <div class="widget-drilldown">
                        <h4 class="tw-mt-0"><?php echo _l('peppol_credit_note_expenses'); ?></h4>
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <span class="tw-font-semibold tw-text-lg text-warning">
                                <?php echo app_format_money($expense_stats['credit_note_amount'] ?? 0, get_base_currency()); ?>
                            </span>
                            <i class="fa fa-file-o tw-text-2xl text-muted"></i>
                        </div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                            <?php echo $expense_stats['credit_note_expenses'] ?? 0; ?>
                            <?php echo _l('peppol_credit_note_expenses_subtitle'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-12">
                <!-- Documents Table with Filters -->
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Filters -->
                        <div class="tw-mb-6">
                            <div class="row">
                                <div class="col-md-3">
                                    <?php echo render_select('filter_document_type', [
                                        ['id' => '', 'name' => _l('peppol_all_document_types')],
                                        ['id' => 'invoice', 'name' => _l('invoice')],
                                        ['id' => 'credit_note', 'name' => _l('credit_note')]
                                    ], ['id', 'name'], _l('peppol_document_type'), ''); ?>
                                </div>

                                <div class="col-md-3">
                                    <?php echo render_select('filter_status', [
                                        ['id' => '', 'name' => _l('peppol_all_statuses')],
                                        ['id' => 'pending', 'name' => _l('peppol_status_pending')],
                                        ['id' => 'sent', 'name' => _l('peppol_status_sent')],
                                        ['id' => 'delivered', 'name' => _l('peppol_status_delivered')],
                                        ['id' => 'failed', 'name' => _l('peppol_status_failed')],
                                        ['id' => 'received', 'name' => _l('peppol_status_received')],
                                        ['id' => 'rejected', 'name' => _l('peppol_status_rejected')],
                                        ['id' => 'rejected_inbound', 'name' => _l('peppol_status_rejected_inbound')]
                                    ], ['id', 'name'], _l('peppol_status'), ''); ?>
                                </div>

                                <div class="col-md-3">
                                    <?php
                                    $provider_options = [['id' => '', 'name' => _l('peppol_all_providers')]];
                                    foreach ($providers as $provider_id => $provider_instance) {
                                        try {
                                            $info = $provider_instance->get_provider_info();
                                            $provider_options[] = ['id' => $provider_id, 'name' => $info['name']];
                                        } catch (Exception $e) {
                                            // Skip invalid providers
                                        }
                                    }
                                    echo render_select('filter_provider', $provider_options, ['id', 'name'], _l('peppol_provider'), '');
                                    ?>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group tw-mt-6 text-right">
                                        <button type="button" id="apply-filters" class="btn btn-primary">
                                            <i class="fa fa-filter"></i>
                                            <?php echo _l('peppol_apply_filters'); ?>
                                        </button>
                                        <button type="button" id="clear-filters" class="btn btn-default"
                                            data-toggle="tooltip" title="<?php echo _l('peppol_clear_filters'); ?>">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <hr class="tw-my-4">
                        </div>

                        <!-- Documents Table -->
                        <div class="panel-table-full">
                            <div class="clearfix"></div>
                            <?php render_datatable([
                                _l('peppol_document_type'),
                                _l('peppol_provider_document_id'),
                                _l('peppol_local_reference'),
                                _l('client'),
                                _l('peppol_total_amount'),
                                _l('peppol_status'),
                                _l('peppol_expense_reference'),
                                _l('peppol_provider'),
                                _l('peppol_date'),
                                _l('options')
                            ], 'peppol-documents'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document Details Modal -->
<div class="modal fade" id="document-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('peppol_document_details'); ?></h4>
            </div>
            <div class="modal-body" id="document-details-content">
                <!-- Content loaded via AJAX -->
                <div id="document-details-preloader" class="text-center" style="padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <span class="sr-only"><?php echo _l('loading'); ?></span>
                    </div>
                    <div class="tw-mt-3">
                        <p class="text-muted"><?php echo _l('peppol_loading_document_details'); ?>...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo _l('close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<?php init_tail(); ?>

<script>
$(function() {
    // Initialize DataTable with Perfex pattern
    var peppolTable = initDataTable('.table-peppol-documents', admin_url + 'peppol/documents', undefined,
        undefined, undefined, [8, 'desc']);

    // Use preXhr event to modify data before sending
    peppolTable.on('preXhr.dt', function(e, settings, data) {
        data.filter_document_type = $('#filter_document_type').val() || '';
        data.filter_status = $('#filter_status').val() || '';
        data.filter_provider = $('#filter_provider').val() || '';

        // Debug logging
        console.log('Sending filter values:', {
            document_type: data.filter_document_type,
            status: data.filter_status,
            provider: data.filter_provider
        });
        console.log('Form element values:', {
            document_type: $('#filter_document_type').length,
            status: $('#filter_status').length,
            provider: $('#filter_provider').length
        });
    });

    // Filter functionality  
    $('#apply-filters').on('click', function(e) {
        e.preventDefault();
        console.log('Apply filters clicked');
        peppolTable.ajax.reload();
    });

    $('#clear-filters').on('click', function(e) {
        e.preventDefault();
        console.log('Clear filters clicked');
        $('#filter_document_type').val('').trigger('change');
        $('#filter_status').val('').trigger('change');
        $('#filter_provider').val('').trigger('change');
        peppolTable.ajax.reload();
    });

    // Auto-apply filters on change
    $('#filter_document_type, #filter_status, #filter_provider').on('change', function() {
        console.log('Filter changed:', $(this).attr('id'), $(this).val());
        peppolTable.ajax.reload();
    });
});

// Global clarifications cache
var peppolClarificationsCache = null;

/**
 * View PEPPOL document details
 */
function viewPeppolDocument(documentId) {
    // Show modal and reset content with fresh preloader
    $('#document-details-modal').modal('show');

    // Show preloader
    $('#document-details-content').html('<div class="text-center tw-p-8">' +
        '<i class="fa fa-spinner fa-spin fa-2x text-primary"></i>' +
        '<div class="tw-mt-3"><p class="text-muted"><?php echo _l("peppol_loading_document_details"); ?>...</p></div>' +
        '</div>');

    $.ajax({
        url: admin_url + 'peppol/view_document/' + documentId,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Cache clarifications data globally for reuse
                if (response.clarifications && !peppolClarificationsCache) {
                    peppolClarificationsCache = response.clarifications;
                }

                // Simply load the pre-rendered content from backend
                $('#document-details-content').html(response.content);
            } else {
                $('#document-details-modal').modal('hide');
                alert_float('danger', response.message);
            }
        },
        error: function() {
            $('#document-details-modal').modal('hide');
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        }
    });
}


/**
 * Download UBL from provider
 */
function downloadProviderUbl(documentId) {
    // Show loading indicator
    var button = $('a[onclick="downloadProviderUbl(' + documentId + ')"]');
    var originalHtml = button.html();
    button.html('<i class="fa fa-spinner fa-spin"></i>');
    button.prop('disabled', true);

    // Create a temporary form to trigger download
    var form = $('<form>').attr({
        method: 'POST',
        action: admin_url + 'peppol/download_provider_ubl/' + documentId,
        target: '_blank'
    });

    // Add CSRF token if available
    if (typeof csrfData !== 'undefined') {
        form.append($('<input>').attr({
            type: 'hidden',
            name: csrfData.token_name,
            value: csrfData.hash
        }));
    }

    // Append form to body and submit
    form.appendTo('body').submit().remove();

    // Reset button after a short delay
    setTimeout(function() {
        button.html(originalHtml);
        button.prop('disabled', false);
    }, 2000);
}
</script>