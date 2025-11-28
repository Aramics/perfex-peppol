<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h5 class="tw-text-lg tw-font-semibold tw-mb-4">
    <i class="fa fa-exchange"></i> <?php echo _l('peppol_transmission_details'); ?>
</h5>

<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_provider'); ?></label>
    <div class="form-control-static">
        <span class="tw-capitalize"><?php echo e(ucfirst($document->provider)); ?></span>
    </div>
</div>

<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_provider_document_id'); ?></label>
    <div class="form-control-static">
        <?php if (!empty($document->provider_document_id)) : ?>
        <code><?php echo e($document->provider_document_id); ?></code>
        <?php else : ?>
        <span class="text-muted">-</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($document->provider_document_transmission_id)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_transmission_id'); ?></label>
    <div class="form-control-static">
        <code><?php echo e($document->provider_document_transmission_id); ?></code>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($document->expense_id)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('expense'); ?></label>
    <div class="form-control-static">
        <a href="<?php echo admin_url('expenses/expense/' . $document->expense_id); ?>" target="_blank" class="text-success">
            <i class="fa fa-external-link"></i> <?php echo _l('peppol_view_expense'); ?> #<?php echo $document->expense_id; ?>
        </a>
    </div>
</div>
<?php endif; ?>