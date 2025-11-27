<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Document Details Content (Server-side rendered) -->
<div class="row">
    <div class="col-md-6">
        <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_document_information'); ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_document_type'); ?></td>
                    <td>
                        <?php
                        $type_class = $document->document_type === 'invoice' ? 'label-primary' : 'label-info';
                        $type_formatted = ucfirst(str_replace('_', ' ', $document->document_type));
                        ?>
                        <span class="label <?php echo $type_class; ?>"><?php echo e($type_formatted); ?></span>
                    </td>
                </tr>
                <?php if (!empty($document->local_reference_id)) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_document_number'); ?></td>
                    <td>
                        <?php
                            $document_number = '#' . $document->local_reference_id;
                            $local_reference_link = admin_url($document->document_type . 's/list_' . $document->document_type . 's/' . $document->local_reference_id);
                            ?>
                        <code><?php echo e($document_number); ?></code>
                        <a href="<?php echo $local_reference_link; ?>" target="_blank" class="tw-ml-2">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('client'); ?></td>
                    <td><?php echo !empty($document->client->company) ? e($document->client->company) : '<span class="text-muted">-</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_status'); ?></td>
                    <td>
                        <?php
                        $status_class = '';
                        switch ($document->status) {
                            case 'sent':
                            case 'delivered':
                                $status_class = 'label-success';
                                break;
                            case 'pending':
                            case 'queued':
                                $status_class = 'label-warning';
                                break;
                            case 'failed':
                                $status_class = 'label-danger';
                                break;
                            case 'received':
                                $status_class = 'label-info';
                                break;
                            default:
                                $status_class = 'label-default';
                        }
                        ?>
                        <span
                            class="label <?php echo $status_class; ?>"><?php echo ucfirst($document->status); ?></span>

                        <?php if (!empty($document->received_at) && !empty($document->provider_document_id)) : ?>
                        <button type="button" id="show-update-status-form" class="btn btn-xs btn-primary tw-ml-2"
                            data-toggle="tooltip" title="<?php echo _l('peppol_update_document_status'); ?>">
                            <i class="fa fa-edit"></i>
                        </button>
                        
                        <?php 
                        // Check if already converted to expense
                        $expense_id = null;
                        if (!empty($metadata['expense_id'])) {
                            $expense_id = $metadata['expense_id'];
                        }
                        ?>
                        
                        <?php if ($expense_id): ?>
                        <a href="<?php echo admin_url('expenses/expense/' . $expense_id); ?>" target="_blank" 
                           class="btn btn-xs btn-success tw-ml-2" data-toggle="tooltip" 
                           title="<?php echo _l('peppol_view_expense_record'); ?>">
                            <i class="fa fa-external-link"></i>
                        </a>
                        <?php else: ?>
                        <button type="button" id="create-expense-btn" class="btn btn-xs btn-warning tw-ml-2"
                            data-toggle="tooltip" title="<?php echo _l('peppol_create_expense'); ?>"
                            data-document-id="<?php echo $document->id; ?>">
                            <i class="fa fa-plus"></i>
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_provider'); ?></td>
                    <td>
                        <span class="tw-capitalize"><?php echo e(ucfirst($document->provider)); ?></span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_transmission_details'); ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_provider_document_id'); ?></td>
                    <td>
                        <?php if (!empty($document->provider_document_id)) : ?>
                        <code><?php echo e($document->provider_document_id); ?></code>
                        <?php else : ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_sent_at'); ?></td>
                    <td>
                        <?php if (!empty($document->sent_at)) : ?>
                        <span class="tw-text-sm"><?php echo e(_dt($document->sent_at)); ?></span>
                        <?php else : ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_received_at'); ?></td>
                    <td>
                        <?php if (!empty($document->received_at)) : ?>
                        <span class="tw-text-sm"><?php echo e(_dt($document->received_at)); ?></span>
                        <?php else : ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_created_at'); ?></td>
                    <td>
                        <span class="tw-text-sm"><?php echo e(_dt($document->created_at)); ?></span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Attachments Section -->
<div class="row tw-mt-6">
    <div class="col-md-12">
        <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_attachments'); ?></h5>
        <?php if (!empty($attachments) && count($attachments) > 0) : ?>
        <div class="tw-bg-neutral-50 tw-border tw-rounded tw-p-4">
            <div class="list-group">
                <?php foreach ($attachments as $attachment) : ?>
                <div class="list-group-item tw-flex tw-items-center tw-justify-between">
                    <div>
                        <i class="fa fa-file-o fa-fw text-muted"></i>
                        <?php if (!empty($attachment['external_link'])) : ?>
                        <a href="<?php echo e($attachment['external_link']); ?>" target="_blank" class="text-primary">
                            <strong><?php echo e($attachment['file_name'] ?? 'Unknown File'); ?></strong>
                            <i class="fa fa-external-link fa-xs"></i>
                        </a>
                        <?php else : ?>
                        <strong><?php echo e($attachment['file_name'] ?? 'Unknown File'); ?></strong>
                        <?php endif; ?>

                        <?php if (!empty($attachment['description']) && $attachment['description'] !== ($attachment['file_name'] ?? '')) : ?>
                        <br><small class="text-muted"><?php echo e($attachment['description']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else : ?>
        <p class="text-muted tw-text-sm"><?php echo _l('peppol_no_attachments_found'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($metadata) && is_array($metadata) && count($metadata) > 0) : ?>
<!-- Metadata Section -->
<div class="row tw-mt-6">
    <div class="col-md-12">
        <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_metadata'); ?></h5>
        <div class="tw-bg-neutral-50 tw-border tw-rounded tw-p-4">
            <pre class="tw-text-sm tw-m-0"
                style="max-height: 300px; overflow-y: auto;"><?php echo json_encode($metadata, JSON_PRETTY_PRINT); ?></pre>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($document->received_at) && !empty($document->provider_document_id)) : ?>
<!-- Status Update Form (Initially Hidden) -->
<div class="row tw-mt-6">
    <div class="col-md-12">
        <div id="update-status-form-container" style="display: none;">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h5 class="panel-title">
                        <i class="fa fa-reply"></i>
                        <?php echo _l('peppol_mark_document_status'); ?>
                    </h5>
                </div>
                <div class="panel-body">
                    <p class="text-muted tw-mb-4"><?php echo _l('peppol_mark_status_help'); ?></p>

                    <form id="mark-status-form">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('peppol_response_status'); ?></label>
                                    <select name="status" id="response-status" class="form-control" required>
                                        <option value=""><?php echo _l('peppol_select_status'); ?></option>
                                        <option value="AB"><?php echo _l('peppol_status_acknowledged'); ?></option>
                                        <option value="IP"><?php echo _l('peppol_status_in_process'); ?></option>
                                        <option value="AP"><?php echo _l('peppol_status_accepted'); ?></option>
                                        <option value="RE"><?php echo _l('peppol_status_rejected'); ?></option>
                                        <option value="PD"><?php echo _l('peppol_status_paid'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label><?php echo _l('peppol_response_note'); ?></label>
                                    <input type="text" name="note" class="form-control"
                                        placeholder="<?php echo _l('peppol_response_note_placeholder'); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo _l('peppol_effective_date'); ?></label>
                                    <input type="datetime-local" name="effective_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Clarifications Section -->
                        <div class="row tw-mt-4">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>
                                        <?php echo _l('peppol_clarifications'); ?>
                                        <small
                                            class="text-muted">(<?php echo _l('peppol_clarifications_optional'); ?>)</small>
                                    </label>
                                    <div id="clarifications-container">
                                        <!-- Clarifications will be added here dynamically -->
                                    </div>
                                    <button type="button" id="add-clarification" class="btn btn-sm btn-default tw-mt-2">
                                        <i class="fa fa-plus"></i> <?php echo _l('peppol_add_clarification'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row tw-mt-4">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-paper-plane"></i> <?php echo _l('peppol_send_response'); ?>
                                </button>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" id="cancel-update-status" class="btn btn-default">
                                    <i class="fa fa-times"></i> <?php echo _l('cancel'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- End update-status-form-container -->
    </div>
</div>

<script>
$(function() {
    var clarificationOptions = {};
    var clarificationCounter = 0;

    // Use passed clarifications data or cached data, fallback to AJAX if neither available
    if (typeof peppolClarificationsCache !== 'undefined' && peppolClarificationsCache) {
        clarificationOptions = peppolClarificationsCache;
        console.log('Using cached clarifications data');
    } else if (<?php echo json_encode(isset($clarifications) ? $clarifications : null); ?>) {
        clarificationOptions = <?php echo json_encode(isset($clarifications) ? $clarifications : '{}'); ?>;
        console.log('Using passed clarifications data');
    } else {
        // Fallback to AJAX request (should rarely happen)
        console.log('Fetching clarifications data via AJAX');
        $.get(admin_url + 'peppol/get_clarifications')
            .done(function(response) {
                if (response.success) {
                    clarificationOptions = response.data;
                    // Cache it globally for future use
                    if (typeof window.peppolClarificationsCache === 'undefined') {
                        window.peppolClarificationsCache = response.data;
                    }
                }
            });
    }

    // Show/Hide Status Update Form
    $('#show-update-status-form').on('click', function() {
        $(this).prop('disabled', true).addClass('disabled');

        $('#update-status-form-container').slideDown(400, function() {
            document.querySelector('#update-status-form-container').scrollIntoView();
        });
    });

    $('#cancel-update-status').on('click', function() {
        $('#update-status-form-container').slideUp(400, function() {
            // Reset form
            $('#mark-status-form')[0].reset();
            // Clear all clarifications
            $('#clarifications-container').empty();
            clarificationCounter = 0;
            // Re-enable the trigger button
            $('#show-update-status-form').prop('disabled', false).removeClass('disabled');
        });
    });

    // Add clarification button
    $('#add-clarification').on('click', function() {
        addClarificationRow();
    });
    
    // Create expense from PEPPOL document
    $('#create-expense-btn').on('click', function() {
        var $btn = $(this);
        var documentId = $btn.data('document-id');
        var documentType = '<?php echo $document->document_type; ?>';
        
        // Confirmation dialog
        var message = documentType === 'credit_note' ? 
            '<?php echo _l("peppol_confirm_create_expense_credit"); ?>' :
            '<?php echo _l("peppol_confirm_create_expense"); ?>';
            
        if (!confirm(message)) {
            return;
        }
        
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        
        $.post(admin_url + 'peppol/create_expense/' + documentId)
        .done(function(response) {
            if (response.success) {
                alert_float('success', response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                alert_float('danger', response.message);
            }
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });

    function addClarificationRow() {
        var index = clarificationCounter++;
        var row = $(`
            <div class="clarification-row tw-border tw-border-neutral-300 tw-rounded tw-p-3 tw-mb-2" data-index="${index}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo _l('peppol_clarification_type'); ?></label>
                            <select name="clarifications[${index}][clarificationType]" class="form-control clarification-type" required>
                                <option value=""><?php echo _l('peppol_select_type'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo _l('peppol_clarification_code'); ?></label>
                            <select name="clarifications[${index}][clarificationCode]" class="form-control clarification-code" required>
                                <option value=""><?php echo _l('peppol_select_code'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label><?php echo _l('peppol_clarification_message'); ?></label>
                            <input type="text" name="clarifications[${index}][clarification]" class="form-control" 
                                   placeholder="<?php echo _l('peppol_clarification_message_placeholder'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm form-control remove-clarification">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        // Populate type options
        if (clarificationOptions.types) {
            var typeSelect = row.find('.clarification-type');
            $.each(clarificationOptions.types, function(key, value) {
                typeSelect.append('<option value="' + key + '">' + value + '</option>');
            });
        }

        $('#clarifications-container').append(row);

        // Handle type change to update code options
        row.find('.clarification-type').on('change', function() {
            var type = $(this).val();
            var codeSelect = row.find('.clarification-code');
            codeSelect.html('<option value=""><?php echo _l('peppol_select_code'); ?></option>');

            var codes = type === 'OPStatusReason' ? clarificationOptions.reason_codes :
                clarificationOptions.action_codes;
            if (codes) {
                $.each(codes, function(key, value) {
                    codeSelect.append('<option value="' + key + '">' + value + '</option>');
                });
            }
        });

        // Remove clarification
        row.find('.remove-clarification').on('click', function() {
            row.remove();
        });
    }

    $('#mark-status-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();

        // Collect form data
        var formData = {
            document_id: <?php echo $document->id; ?>,
            status: $form.find('[name="status"]').val(),
            note: $form.find('[name="note"]').val(),
            effective_date: $form.find('[name="effective_date"]').val(),
            clarifications: []
        };

        // Collect clarifications
        $('.clarification-row').each(function() {
            var $row = $(this);
            var clarification = {
                clarificationType: $row.find('[name$="[clarificationType]"]').val(),
                clarificationCode: $row.find('[name$="[clarificationCode]"]').val(),
                clarification: $row.find('[name$="[clarification]"]').val()
            };

            if (clarification.clarificationType && clarification.clarificationCode &&
                clarification.clarification) {
                formData.clarifications.push(clarification);
            }
        });

        if (!formData.status) {
            alert_float('danger', '<?php echo _l("peppol_select_status"); ?>');
            return;
        }

        $btn.prop('disabled', true).html(
            '<i class="fa fa-spinner fa-spin"></i> <?php echo _l("processing"); ?>');

        $.post(admin_url + 'peppol/mark_document_status', formData)
            .done(function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    // Hide form and reset trigger button
                    $('#update-status-form-container').slideUp(400, function() {
                        $('#show-update-status-form').prop('disabled', false).removeClass(
                            'disabled');
                    });
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert_float('danger', response.message);
                }
            })
            .fail(function() {
                alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
            })
            .always(function() {
                $btn.prop('disabled', false).html(originalText);
            });
    });
});
</script>
<?php endif; ?>