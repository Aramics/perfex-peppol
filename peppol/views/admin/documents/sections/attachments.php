<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h5 class="tw-text-lg tw-font-semibold tw-mb-4">
    <i class="fa fa-paperclip"></i> <?php echo _l('peppol_attachments'); ?>
    <span class="badge badge-light"><?php echo count($attachments); ?></span>
</h5>

<?php if (!empty($attachments) && count($attachments) > 0) : ?>
<div class="list-group">
    <?php foreach ($attachments as $attachment) : ?>
    <div class="list-group-item tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-space-x-3">
            <div>
                <i class="fa fa-file-o fa-fw text-muted fa-2x"></i>
            </div>
            <div>
                <?php if (!empty($attachment['external_link'])) : ?>
                <a href="<?php echo e($attachment['external_link']); ?>" target="_blank" class="text-primary">
                    <strong><?php echo e($attachment['file_name']); ?></strong>
                    <i class="fa fa-external-link fa-xs tw-ml-1"></i>
                </a>
                <?php else : ?>
                <strong><?php echo e($attachment['file_name']); ?></strong>
                <?php endif; ?>

                <?php if (!empty($attachment['description']) && $attachment['description'] !== ($attachment['file_name'] ?? '')) : ?>
                <br><small class="text-muted"><?php echo e($attachment['description']); ?></small>
                <?php endif; ?>

                <?php if (!empty($attachment['mime_type'])) : ?>
                <br><small class="text-info"><?php echo e($attachment['mime_type']); ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if (!empty($attachment['external_link'])) : ?>
            <a href="<?php echo e($attachment['external_link']); ?>" target="_blank" class="btn btn-sm btn-primary"
                data-toggle="tooltip" title="<?php echo _l('download'); ?>">
                <i class="fa fa-download"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else : ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <?php echo _l('peppol_no_attachments_found'); ?>
</div>
<?php endif; ?>