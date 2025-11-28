<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Expense Creation Form -->
<div class="modal-header">
    <h4 class="modal-title">
        <i class="fa fa-plus"></i>
        <?php echo _l('peppol_create_expense'); ?>
    </h4>
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">

    <form id="expense-creation-form" data-document-id="<?php echo $document->id; ?>">
        <div class="row">
            <!-- Category -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="expense-category"><?php echo _l('expense_category'); ?> <span
                            class="text-danger">*</span></label>
                    <select name="category" id="expense-category" class="form-control" required>
                        <?php foreach ($expense_categories as $category) : ?>
                        <option value="<?php echo $category['id']; ?>"
                            <?php echo ($category['id'] == $expense_data['category']) ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($expense_data['category'])) : ?>
                    <small class="text-success">
                        <i class="fa fa-check"></i> <?php echo _l('auto_detected'); ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Mode -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="expense-payment-mode"><?php echo _l('payment_mode'); ?></label>
                    <select name="paymentmode" id="expense-payment-mode" class="form-control">
                        <option value=""><?php echo _l('none'); ?></option>
                        <?php foreach ($payment_modes as $mode) : ?>
                        <option value="<?php echo $mode['id']; ?>"
                            <?php echo ($mode['id'] == $expense_data['paymentmode']) ? 'selected' : ''; ?>>
                            <?php echo e($mode['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($expense_data['paymentmode'])) : ?>
                    <small class="text-success">
                        <i class="fa fa-check"></i> <?php echo _l('auto_detected'); ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Tax Rate 1 -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="expense-tax-rate"><?php echo _l('tax_1'); ?> (%)</label>
                    <input type="number" name="tax_rate" id="expense-tax-rate" class="form-control" step="0.01" min="0"
                        max="100" value="<?php echo $expense_data['tax1_rate']; ?>">
                    <?php if ($expense_data['tax1_rate'] > 0) : ?>
                    <small class="text-success">
                        <i class="fa fa-check"></i> <?php echo _l('auto_detected'); ?>:
                        <?php echo $expense_data['tax1_rate']; ?>%
                    </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tax Rate 2 -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="expense-tax2-rate"><?php echo _l('tax_2'); ?> (%)</label>
                    <input type="number" name="tax2_rate" id="expense-tax2-rate" class="form-control" step="0.01"
                        min="0" max="100" value="<?php echo $expense_data['tax2_rate']; ?>">
                    <?php if ($expense_data['tax2_rate'] > 0) : ?>
                    <small class="text-success">
                        <i class="fa fa-check"></i> <?php echo _l('auto_detected'); ?>:
                        <?php echo $expense_data['tax2_rate']; ?>%
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Auto-detected Information Display -->
        <?php if (!empty($expense_data['paymentmode']) || $expense_data['tax1_rate'] > 0 || $expense_data['tax2_rate'] > 0) : ?>
        <div class="alert alert-info">
            <h5><i class="fa fa-magic"></i> <?php echo _l('auto_detected_information'); ?></h5>
            <ul class="list-unstyled tw-mb-0">
                <?php if (!empty($expense_data['paymentmode'])) : ?>
                <li><strong><?php echo _l('payment_mode'); ?>:</strong>
                    <?php
                            $selected_mode = array_filter($payment_modes, function ($mode) use ($expense_data) {
                                return $mode['id'] == $expense_data['paymentmode'];
                            });
                            if ($selected_mode) {
                                echo e(array_values($selected_mode)[0]['name']);
                            }
                            ?>
                </li>
                <?php endif; ?>

                <?php if ($expense_data['tax1_rate'] > 0) : ?>
                <li><strong><?php echo _l('tax_rate'); ?>:</strong> <?php echo $expense_data['tax1_rate']; ?>%</li>
                <?php endif; ?>

                <?php if ($expense_data['tax2_rate'] > 0) : ?>
                <li><strong><?php echo _l('tax_2'); ?>:</strong> <?php echo $expense_data['tax2_rate']; ?>%</li>
                <?php endif; ?>
            </ul>
            <small class="text-muted">
                <?php echo _l('peppol_auto_detected_help'); ?>
            </small>
        </div>
        <?php endif; ?>

        <!-- Readonly Information -->
        <div class="row">
            <div class="col-md-12">
                <h5><?php echo _l('expense_details'); ?></h5>
                <table class="table table-bordered table-sm">
                    <tr>
                        <td><strong><?php echo _l('expense_name'); ?>:</strong></td>
                        <td><?php echo e($expense_data['expense_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo _l('amount'); ?>:</strong></td>
                        <td>
                            <?php echo app_format_money($expense_data['amount'], $expense_data['currency']); ?>
                            <?php if ($document->document_type === 'credit_note' && $expense_data['amount'] < 0) : ?>
                            <small class="text-muted">(<?php echo _l('negative_for_credit_note'); ?>)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo _l('expense_date'); ?>:</strong></td>
                        <td><?php echo e(_d($expense_data['date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo _l('reference_no'); ?>:</strong></td>
                        <td><?php echo e($expense_data['reference_no']); ?></td>
                    </tr>
                    <?php if (!empty($ubl_data['seller']['scheme']) && !empty($ubl_data['seller']['identifier'])) : ?>
                    <tr>
                        <td><strong><?php echo _l('vendor_identifier'); ?>:</strong></td>
                        <td>
                            <code><?php echo e($ubl_data['seller']['scheme']); ?>:<?php echo e($ubl_data['seller']['identifier']); ?></code>
                            <?php if (!empty($ubl_data['seller']['vat_number'])) : ?>
                            <br><small class="text-muted">VAT:
                                <?php echo e($ubl_data['seller']['vat_number']); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($expense_data['note'])) : ?>
                    <tr>
                        <td><strong><?php echo _l('expense_note'); ?>:</strong></td>
                        <td>
                            <div class="tw-max-h-20 tw-overflow-y-auto">
                                <?php echo nl2br(e($expense_data['note'])); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">
        <?php echo _l('cancel'); ?>
    </button>
    <button type="button" id="create-expense-submit" class="btn btn-primary">
        <i class="fa fa-plus"></i> <?php echo _l('peppol_create_expense'); ?>
    </button>
</div>

<script>
$(function() {
    $('#create-expense-submit').on('click', function() {
        var $form = $('#expense-creation-form');
        var documentId = $form.data('document-id');

        var formData = {
            category: $form.find('[name="category"]').val(),
            paymentmode: $form.find('[name="paymentmode"]').val(),
            tax_rate: $form.find('[name="tax_rate"]').val(),
            tax2_rate: $form.find('[name="tax2_rate"]').val()
        };

        $.post(admin_url + 'peppol/create_expense/' + documentId, formData)
            .done(function(response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                if (response.success) {
                    alert_float('success', response.message);
                    $('.modal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert_float('danger', response.message);
                }
            })
            .fail(function() {
                alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
            });
    });
});
</script>