<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<form id="mark-status-form">
    <input type="hidden" name="document_id" value="<?php echo $document->id; ?>">

    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <?php echo _l('peppol_mark_status_help'); ?>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="response-status"><?php echo _l('peppol_response_status'); ?> <span
                        class="text-danger">*</span></label>
                <select name="status" id="response-status" class="form-control" required>
                    <option value=""><?php echo _l('peppol_select_status'); ?></option>
                    <?php if (!empty($document->received_at)) : ?>
                    <option value="AB"><?php echo _l('peppol_status_acknowledged'); ?></option>
                    <option value="IP"><?php echo _l('peppol_status_in_process'); ?></option>
                    <option value="AP"><?php echo _l('peppol_status_accepted'); ?></option>
                    <option value="RE"><?php echo _l('peppol_status_rejected'); ?></option>
                    <?php if ($document->document_type == 'invoice') : ?>
                    <option value="PD"><?php echo _l('peppol_status_paid'); ?></option>
                    <?php endif; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="col-md-5">
            <div class="form-group">
                <label for="response-note"><?php echo _l('peppol_response_note'); ?></label>
                <input type="text" name="note" id="response-note" class="form-control"
                    placeholder="<?php echo _l('peppol_response_note_placeholder'); ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="response-effective-date"><?php echo _l('peppol_effective_date'); ?></label>
                <input type="datetime-local" name="effective_date" id="response-effective-date" class="form-control">
            </div>
        </div>
    </div>

    <!-- Clarifications Section -->
    <div class="row tw-mt-4">
        <div class="col-md-12">
            <div class="form-group">
                <label>
                    <?php echo _l('peppol_clarifications'); ?>
                    <small class="text-muted">(<?php echo _l('peppol_clarifications_optional'); ?>)</small>
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

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">
            <?php echo _l('cancel'); ?>
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-paper-plane"></i> <?php echo _l('peppol_send_response'); ?>
        </button>
    </div>
</form>

<script>
$(function() {
    var clarificationOptions = window.peppolClarificationsCache || {};
    var clarificationCounter = 0;

    // Add clarification button
    $('#add-clarification').on('click', function() {
        addClarificationRow();
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
                    $('#statusUpdateModal').modal('hide');
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
                $btn.prop('disabled', false).html(originalText);
            });
    });
});
</script>