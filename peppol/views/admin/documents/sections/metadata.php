<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h5 class="tw-text-lg tw-font-semibold tw-mb-4">
    <i class="fa fa-code"></i> <?php echo _l('peppol_metadata'); ?>
    <button type="button" class="btn btn-xs btn-default" id="toggleMetadata">
        <i class="fa fa-eye"></i> <?php echo _l('show_hide'); ?>
    </button>
</h5>

<div id="metadataContent" style="display: none;">
    <div class="tw-bg-neutral-50 tw-border tw-rounded tw-p-4">
        <pre class="tw-text-sm tw-m-0"
            style="max-height: 400px; overflow-y: auto;"><?php echo json_encode($metadata, JSON_PRETTY_PRINT); ?></pre>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    $('#toggleMetadata').on('click', function() {
        var $content = $('#metadataContent');
        var $icon = $(this).find('i');

        if ($content.is(':visible')) {
            $content.slideUp();
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $content.slideDown();
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });
});
</script>