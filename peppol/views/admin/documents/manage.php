<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <!-- Page Header -->
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
                            <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                                <?php echo _l('peppol_documents'); ?>
                            </h4>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row tw-mb-6">
                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body padding-10-20">
                                        <div class="widget-drilldown">
                                            <h4 class="tw-mt-0"><?php echo _l('peppol_invoice_documents'); ?></h4>
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <span class="tw-font-semibold tw-text-lg text-primary">
                                                    <?php echo ($invoice_stats['total_processed'] ?? 0) + ($invoice_stats['unsent'] ?? 0); ?>
                                                </span>
                                                <i class="fa fa-file-text-o tw-text-2xl text-muted"></i>
                                            </div>
                                            <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                                                <?php echo _l('peppol_sent'); ?>:
                                                <?php echo $invoice_stats['sent'] ?? 0; ?> |
                                                <?php echo _l('peppol_failed'); ?>:
                                                <?php echo $invoice_stats['failed'] ?? 0; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body padding-10-20">
                                        <div class="widget-drilldown">
                                            <h4 class="tw-mt-0"><?php echo _l('peppol_credit_note_documents'); ?></h4>
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <span class="tw-font-semibold tw-text-lg text-info">
                                                    <?php echo ($credit_note_stats['total_processed'] ?? 0) + ($credit_note_stats['unsent'] ?? 0); ?>
                                                </span>
                                                <i class="fa fa-file-o tw-text-2xl text-muted"></i>
                                            </div>
                                            <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                                                <?php echo _l('peppol_sent'); ?>:
                                                <?php echo $credit_note_stats['sent'] ?? 0; ?> |
                                                <?php echo _l('peppol_failed'); ?>:
                                                <?php echo $credit_note_stats['failed'] ?? 0; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body padding-10-20">
                                        <div class="widget-drilldown">
                                            <h4 class="tw-mt-0"><?php echo _l('peppol_received_documents'); ?></h4>
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <span class="tw-font-semibold tw-text-lg text-success">
                                                    <?php echo ($invoice_stats['received'] ?? 0) + ($credit_note_stats['received'] ?? 0); ?>
                                                </span>
                                                <i class="fa fa-download tw-text-2xl text-muted"></i>
                                            </div>
                                            <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                                                <?php echo _l('peppol_documents_received_from_network'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body padding-10-20">
                                        <div class="widget-drilldown">
                                            <h4 class="tw-mt-0"><?php echo _l('peppol_active_provider'); ?></h4>
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <span class="tw-font-semibold tw-text-sm">
                                                    <?php echo $active_provider ? ucfirst($active_provider) : _l('peppol_not_configured'); ?>
                                                </span>
                                                <i class="fa fa-plug tw-text-2xl text-muted"></i>
                                            </div>
                                            <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                                                <a href="<?php echo admin_url('settings?group=peppol'); ?>"
                                                    class="text-muted">
                                                    <?php echo _l('peppol_configure'); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                                ['id' => 'received', 'name' => _l('peppol_status_received')]
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
                                                    data-toggle="tooltip"
                                                    title="<?php echo _l('peppol_clear_filters'); ?>">
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
                                        _l('peppol_document_number'),
                                        _l('client'),
                                        _l('peppol_total_amount'),
                                        _l('peppol_status'),
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
        undefined, undefined, [6, 'desc']);

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

/**
 * View PEPPOL document details
 */
function viewPeppolDocument(documentId) {
    $.ajax({
        url: admin_url + 'peppol/view_document/' + documentId,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var document = response.document;
                var content = '';

                content += '<div class="row">';
                content += '<div class="col-md-6">';
                content += '<h5><?php echo _l("peppol_document_information"); ?></h5>';
                content += '<table class="table table-bordered">';
                content += '<tr><td><strong><?php echo _l("peppol_document_type"); ?></strong></td><td>' +
                    document.type + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("peppol_document_number"); ?></strong></td><td>' +
                    (document.document_number || '#' + document.document_id) + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("client"); ?></strong></td><td>' + (document
                    .client_name || '-') + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("peppol_status"); ?></strong></td><td>' + document
                    .status + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("peppol_provider"); ?></strong></td><td>' +
                    document.provider + '</td></tr>';
                content += '</table>';
                content += '</div>';

                content += '<div class="col-md-6">';
                content += '<h5><?php echo _l("peppol_transmission_details"); ?></h5>';
                content += '<table class="table table-bordered">';
                content +=
                    '<tr><td><strong><?php echo _l("peppol_provider_document_id"); ?></strong></td><td>' + (
                        document.provider_document_id || '-') + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("peppol_sent_at"); ?></strong></td><td>' + (
                    document.sent_at || '-') + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("peppol_received_at"); ?></strong></td><td>' + (
                    document.received_at || '-') + '</td></tr>';
                content += '<tr><td><strong><?php echo _l("peppol_created_at"); ?></strong></td><td>' +
                    document.created_at + '</td></tr>';
                content += '</table>';
                content += '</div>';
                content += '</div>';

                // Show metadata if available
                if (document.metadata && Object.keys(document.metadata).length > 0) {
                    content += '<div class="row tw-mt-4">';
                    content += '<div class="col-md-12">';
                    content += '<h5><?php echo _l("peppol_metadata"); ?></h5>';
                    content += '<pre class="tw-bg-neutral-50 tw-p-3 tw-rounded tw-text-sm">' + JSON
                        .stringify(document.metadata, null, 2) + '</pre>';
                    content += '</div>';
                    content += '</div>';
                }

                $('#document-details-content').html(content);
                $('#document-details-modal').modal('show');
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function() {
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        }
    });
}
</script>