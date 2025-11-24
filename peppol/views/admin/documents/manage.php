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
                            <div class="col-md-6">
                                <div class="panel_s">
                                    <div class="panel-body padding-10-20">
                                        <div class="widget-drilldown">
                                            <h4 class="tw-mt-0"><?php echo _l('peppol_invoice_documents'); ?></h4>
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <span class="tw-font-semibold tw-text-lg text-primary">
                                                    <?php echo $invoice_stats['total'] ?? 0; ?>
                                                </span>
                                                <i class="fa fa-file-text-o tw-text-2xl text-muted"></i>
                                            </div>
                                            <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                                                <?php echo _l('peppol_sent'); ?>:
                                                <?php echo $invoice_stats['sent'] ?? 0; ?> |
                                                <?php echo _l('peppol_failed'); ?>:
                                                <?php echo $invoice_stats['failed'] ?? 0; ?> |
                                                <?php echo _l('peppol_status_received'); ?>:
                                                <?php echo $invoice_stats['received'] ?? 0; ?> |
                                                <?php echo _l('peppol_status_rejected'); ?>:
                                                <?php echo $invoice_stats['rejected'] ?? 0; ?> |
                                                <?php echo _l('peppol_status_rejected_inbound'); ?>:
                                                <?php echo $invoice_stats['rejected_inbound'] ?? 0; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="panel_s">
                                    <div class="panel-body padding-10-20">
                                        <div class="widget-drilldown">
                                            <h4 class="tw-mt-0"><?php echo _l('peppol_credit_note_documents'); ?></h4>
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <span class="tw-font-semibold tw-text-lg text-info">
                                                    <?php echo $credit_note_stats['total'] ?? 0; ?>
                                                </span>
                                                <i class="fa fa-file-o tw-text-2xl text-muted"></i>
                                            </div>
                                            <div class="tw-text-xs tw-text-neutral-500 tw-mt-2">
                                                <?php echo _l('peppol_sent'); ?>:
                                                <?php echo $credit_note_stats['sent'] ?? 0; ?> |
                                                <?php echo _l('peppol_failed'); ?>:
                                                <?php echo $credit_note_stats['failed'] ?? 0; ?> |
                                                <?php echo _l('peppol_status_received'); ?>:
                                                <?php echo $credit_note_stats['received'] ?? 0; ?> |
                                                <?php echo _l('peppol_status_rejected'); ?>:
                                                <?php echo $credit_note_stats['rejected'] ?? 0; ?> |
                                                <?php echo _l('peppol_status_rejected_inbound'); ?>:
                                                <?php echo $credit_note_stats['rejected_inbound'] ?? 0; ?>
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

<!-- Include Document Details Modal Template -->
<?php $this->load->view('peppol/templates/document_details_modal'); ?>

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
    // Show modal and reset content with fresh preloader
    $('#document-details-modal').modal('show');

    // Show preloader using template
    var preloaderTemplate = $('#document-details-preloader-template').html();
    $('#document-details-content').html(preloaderTemplate);

    $.ajax({
        url: admin_url + 'peppol/view_document/' + documentId,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var docData = response.document;

                // Prepare template data
                var templateData = {
                    typeClass: getTypeClass(docData.original_type || docData.type),
                    type: docData.type, // Raw type (credit_note, invoice)
                    typeFormatted: docData.type_formatted,
                    documentNumber: docData.document_number || '#' + docData.local_reference_id,
                    localReferenceId: docData.local_reference_id,
                    localReferenceLink: docData.local_reference_link,
                    hasLocalReference: docData.local_reference_id?.length > 0,
                    clientName: docData.client_name || '<span class="text-muted">-</span>',
                    statusBadge: getStatusBadge(docData.status),
                    provider: docData.provider,
                    providerDocumentId: docData.provider_document_id ?
                        '<code>' + docData.provider_document_id + '</code>' :
                        '<span class="text-muted">-</span>',
                    sentAt: docData.sent_at ?
                        '<span class="tw-text-sm">' + docData.sent_at + '</span>' :
                        '<span class="text-muted">-</span>',
                    receivedAt: docData.received_at ?
                        '<span class="tw-text-sm">' + docData.received_at + '</span>' :
                        '<span class="text-muted">-</span>',
                    createdAt: docData.created_at,
                    hasAttachments: docData.attachments && docData.attachments.length > 0,
                    attachmentsList: formatAttachmentsList(docData.attachments),
                    hasMetadata: docData.metadata && Object.keys(docData.metadata).length > 0,
                    metadata: docData.metadata ? JSON.stringify(docData.metadata, null, 2) : ''
                };

                // Render template with data
                var content = renderTemplate('document-details-template', templateData);
                $('#document-details-content').html(content);
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
 * Simple template renderer
 */
function renderTemplate(templateId, data) {
    var template = $('#' + templateId).html();

    // Replace simple variables {{variable}}
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            var regex = new RegExp('\{\{' + key + '\}\}', 'g');
            template = template.replace(regex, data[key]);
        }
    }

    // Handle simple conditionals {{#if condition}}...{{/if}}
    template = template.replace(/\{\{#if\s+(\w+)\}\}([\s\S]*?)\{\{\/if\}\}/g, function(match, condition, content) {
        return data[condition] ? content : '';
    });

    // Handle negative conditionals {{#unless condition}}...{{/unless}}
    template = template.replace(/\{\{#unless\s+(\w+)\}\}([\s\S]*?)\{\{\/unless\}\}/g, function(match, condition,
        content) {
        return !data[condition] ? content : '';
    });

    return template;
}

/**
 * Get CSS class for document type
 */
function getTypeClass(type) {
    return type.toLowerCase().includes('invoice') ? 'label-primary' : 'label-info';
}

/**
 * Format attachments list for display
 */
function formatAttachmentsList(attachments) {
    if (!attachments || attachments.length === 0) {
        return '';
    }

    var html = '';
    for (var i = 0; i < attachments.length; i++) {
        var attachment = attachments[i];
        var fileName = attachment.file_name || attachment.filename || 'Unknown File';
        var fileSize = attachment.file_size ? ' (' + formatFileSize(attachment.file_size) + ')' : '';
        var description = attachment.description || fileName;

        html += '<div class="list-group-item tw-flex tw-items-center tw-justify-between">';
        html += '<div>';
        html += '<i class="fa fa-file-o fa-fw text-muted"></i> ';

        if (attachment.external_link) {
            html += '<a href="' + attachment.external_link + '" target="_blank" class="text-primary">';
            html += '<strong>' + fileName + '</strong>' + fileSize;
            html += ' <i class="fa fa-external-link fa-xs"></i>';
            html += '</a>';
        } else {
            html += '<strong>' + fileName + '</strong>' + fileSize;
        }

        if (description !== fileName) {
            html += '<br><small class="text-muted">' + description + '</small>';
        }

        html += '</div>';
        html += '</div>';
    }

    return html;
}

/**
 * Format file size in human readable format
 */
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';

    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    var badgeClass = '';
    var displayStatus = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');

    switch (status.toLowerCase()) {
        case 'sent':
        case 'delivered':
            badgeClass = 'label-success';
            break;
        case 'pending':
        case 'queued':
            badgeClass = 'label-warning';
            break;
        case 'failed':
        case 'rejected':
        case 'rejected_inbound':
            badgeClass = 'label-danger';
            break;
        case 'received':
            badgeClass = 'label-info';
            break;
        default:
            badgeClass = 'label-default';
    }

    return '<span class="label ' + badgeClass + '">' + displayStatus + '</span>';
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