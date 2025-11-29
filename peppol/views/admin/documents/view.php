<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <!-- Document Header -->
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                    <div class="tw-flex tw-items-center tw-space-x-4">
                        <a href="<?php echo admin_url('peppol/documents'); ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_documents'); ?>
                        </a>
                        <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                            <span
                                class="label <?php echo $document->document_type === 'invoice' ? 'label-primary' : 'label-info'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $document->document_type)); ?>
                            </span>
                        </h4>
                    </div>
                    <div class="tw-flex tw-items-center tw-space-x-2">
                        <!-- Status Update Button -->
                        <?php if (!empty($document->received_at) && !empty($document->provider_document_transmission_id)) : ?>
                        <button type="button" class="btn btn-primary"
                            onclick="openStatusUpdateModal(<?php echo $document->id; ?>)">
                            <i class="fa fa-edit"></i> <?php echo _l('peppol_update_status'); ?>
                        </button>
                        <?php endif; ?>

                        <!-- Expense Button -->
                        <?php
                        $expense_id = $document->expense_id ?? null;
                        if ($expense_id) : ?>
                        <a href="<?php echo admin_url('expenses/expense/' . $expense_id); ?>" target="_blank"
                            class="btn btn-success" data-toggle="tooltip"
                            title="<?php echo _l('peppol_view_expense_record'); ?>">
                            <i class="fa fa-external-link"></i> <?php echo _l('peppol_view_expense'); ?>
                        </a>
                        <?php elseif (!empty($document->received_at)) : ?>
                        <button type="button" class="btn btn-warning"
                            onclick="createExpenseFromDocument(<?php echo $document->id; ?>)" data-toggle="tooltip"
                            title="<?php echo _l('peppol_create_expense'); ?>">
                            <i class="fa fa-plus"></i> <?php echo _l('peppol_create_expense'); ?>
                        </button>
                        <?php endif; ?>

                        <!-- Download UBL -->
                        <?php if (!empty($document->provider_document_id)) : ?>
                        <a href="javascript:void(0)" onclick="downloadProviderUbl(<?php echo $document->id; ?>)"
                            class="btn btn-info" data-toggle="tooltip" title="<?php echo _l('peppol_download_ubl'); ?>">
                            <i class="fa fa-download"></i> UBL
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row">
                    <!-- Left Column - Document Details -->
                    <div class="col-md-8">
                        <!-- Document Preview/Content -->
                        <?php if (isset($document->ubl_document['data'])) : ?>
                        <div class="panel_s tw-mb-4">
                            <div class="panel-body">
                                <?php $this->load->view('peppol/admin/documents/sections/document_preview', ['document' => $document, 'ubl_data' => $document->ubl_document['data']]); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Attachments -->
                        <?php if (!empty($attachments)) : ?>
                        <div class="panel_s tw-mb-4">
                            <div class="panel-body">
                                <?php $this->load->view('peppol/admin/documents/sections/attachments', ['attachments' => $attachments]); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column - Sidebar -->
                    <div class="col-md-4">
                        <!-- Status Information -->
                        <div class="panel_s">
                            <div class="panel-body">
                                <?php $this->load->view('peppol/admin/documents/sections/status_info', ['document' => $document]); ?>
                            </div>
                        </div>

                        <!-- Transmission Details -->
                        <div class="panel_s tw-mt-4">
                            <div class="panel-body">
                                <?php $this->load->view('peppol/admin/documents/sections/transmission_info', ['document' => $document]); ?>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <?php if (!empty($metadata) && is_array($metadata) && count($metadata) > 0) : ?>
                        <div class="panel_s tw-mt-4">
                            <div class="panel-body">
                                <?php $this->load->view('peppol/admin/documents/sections/metadata', ['metadata' => $metadata]); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-edit"></i> <?php echo _l('peppol_update_document_status'); ?>
                </h4>
            </div>
            <div class="modal-body" id="statusUpdateModalBody">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Create Expense Loading Modal -->
<div class="modal fade" id="expenseLoadingModal" tabindex="-1" role="dialog" data-backdrop="static"
    data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-plus"></i> <?php echo _l('peppol_create_expense'); ?>
                </h4>
            </div>
            <div class="modal-body text-center" style="padding: 40px;">
                <div class="spinner-border text-primary tw-mb-4" role="status">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    // Global clarifications cache
    window.peppolClarificationsCache = <?php echo json_encode($clarifications ?? []); ?>;
});

/**
 * Open status update modal
 */
function openStatusUpdateModal(documentId) {
    $('#statusUpdateModal').modal('show');

    // Show loading
    $('#statusUpdateModalBody').html('<div class="text-center tw-p-8">' +
        '<i class="fa fa-spinner fa-spin fa-2x text-primary"></i>' +
        '<div class="tw-mt-3"><p class="text-muted"><?php echo _l("loading"); ?>...</p></div>' +
        '</div>');

    // Load status update form
    $.ajax({
        url: admin_url + 'peppol/get_status_update_form/' + documentId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#statusUpdateModalBody').html(response.content);
            } else {
                alert_float('danger', response.message);
                $('#statusUpdateModal').modal('hide');
            }
        },
        error: function() {
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
            $('#statusUpdateModal').modal('hide');
        }
    });
}

/**
 * Create expense from PEPPOL document
 */
function createExpenseFromDocument(documentId) {
    // Show loading modal
    $('#expenseLoadingModal').modal('show');

    $.getJSON(admin_url + 'peppol/create_expense/' + documentId)
        .done(function(response) {
            // Hide loading modal
            $('#expenseLoadingModal').modal('hide');

            if (response.success) {
                if (response.show_form) {
                    // Show form modal for user to review/modify auto-detected data
                    var modal = $('<div class="modal fade" tabindex="-1">');
                    modal.html('<div class="modal-dialog modal-lg"><div class="modal-content">' +
                        response.form_html + '</div></div>');
                    $('body').append(modal);
                    modal.modal('show');

                    // Clean up modal when closed
                    modal.on('hidden.bs.modal', function() {
                        modal.remove();
                    });
                } else {
                    alert_float('success', response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                alert_float('danger', response.message);
            }
        })
        .fail(function() {
            // Hide loading modal on error
            $('#expenseLoadingModal').modal('hide');
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        });
}

/**
 * Download UBL from provider
 */
function downloadProviderUbl(documentId) {
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

    form.appendTo('body').submit().remove();
}
</script>