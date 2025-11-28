<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h5 class="tw-text-lg tw-font-semibold tw-mb-4">
    <i class="fa fa-info-circle"></i> <?php echo _l('peppol_status_information'); ?>
</h5>

<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_current_status'); ?></label>
    <div class="form-control-static">
        <?php
        $status_class = '';
        switch ($document->status) {
            case 'SENT':
            case 'TECHNICAL_ACCEPTANCE':
            case 'FULLY_PAID':
            case 'ACCEPTED':
                $status_class = 'label-success';
                break;
            case 'QUEUED':
                $status_class = 'label-warning';
                break;
            case 'SEND_FAILED':
            case 'REJECTED':
                $status_class = 'label-danger';
                break;
            case 'received':
                $status_class = 'label-info';
                break;
            default:
                $status_class = 'label-default';
        }
        ?>
        <span class="label <?php echo $status_class; ?>">
            <?php echo ucfirst(str_replace('_', ' ', $document->status)); ?>
        </span>
    </div>
</div>

<?php if (!empty($document->received_at)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_direction'); ?></label>
    <div class="form-control-static">
        <span class="label label-info">
            <i class="fa fa-arrow-down"></i> <?php echo _l('peppol_inbound'); ?>
        </span>
    </div>
</div>
<?php else : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_direction'); ?></label>
    <div class="form-control-static">
        <span class="label label-primary">
            <i class="fa fa-arrow-up"></i> <?php echo _l('peppol_outbound'); ?>
        </span>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($document->local_reference_id)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_local_reference'); ?></label>
    <div class="form-control-static">
        <?php
            $document_number = '#' . $document->local_reference_id;
            $local_reference_link = admin_url($document->document_type . 's/list_' . $document->document_type . 's/' . $document->local_reference_id);
            ?>
        <code><?php echo e($document_number); ?></code>
        <a href="<?php echo $local_reference_link; ?>" target="_blank" class="tw-ml-2">
            <i class="fa fa-eye"></i>
        </a>
    </div>
</div>
<?php endif; ?>

<div class="form-group">
    <label class="control-label"><?php echo _l('client'); ?></label>
    <div class="form-control-static">
        <?php if (!empty($document->client->company)) : ?>
        <?php echo e($document->client->company); ?>
        <?php if (!empty($document->client->id)) : ?>
        <a href="<?php echo admin_url('clients/client/' . $document->client->id); ?>" target="_blank" class="tw-ml-2">
            <i class="fa fa-eye"></i>
        </a>
        <?php endif; ?>
        <?php else : ?>
        <span class="text-muted">-</span>
        <?php endif; ?>
    </div>
</div>


<?php if (!empty($document->sent_at)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_sent_at'); ?></label>
    <div class="form-control-static">
        <span class="tw-text-sm"><?php echo e(_dt($document->sent_at)); ?></span>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($document->received_at)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_received_at'); ?></label>
    <div class="form-control-static">
        <span class="tw-text-sm"><?php echo e(_dt($document->received_at)); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_created_at'); ?></label>
    <div class="form-control-static">
        <span class="tw-text-sm"><?php echo e(_dt($document->created_at)); ?></span>
    </div>
</div>

<?php if (!empty($document->updated_at)) : ?>
<div class="form-group">
    <label class="control-label"><?php echo _l('peppol_updated_at'); ?></label>
    <div class="form-control-static">
        <span class="tw-text-sm"><?php echo e(_dt($document->updated_at)); ?></span>
    </div>
</div>
<?php endif; ?>