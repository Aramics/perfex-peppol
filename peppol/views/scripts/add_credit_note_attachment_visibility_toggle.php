<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<script>
let creditNoteId = "<?= $credit_note->id; ?>";
let creditNoteAttachments = <?= json_encode($attachments); ?>;

$(document).ready(function() {
    // Add visibility toggle buttons to existing attachments
    addAttachmentVisibilityToggles();
});

function addAttachmentVisibilityToggles() {
    // Find all attachment rows that don't have toggle buttons yet
    $('[data-attachment-id]:not([data-toggle-added])').each(function() {
        var $attachmentRow = $(this);
        var attachmentId = $attachmentRow.data('attachment-id');
        console.log({
            attachmentId
        })
        if (!attachmentId) return;

        // Mark this row as processed
        $attachmentRow.attr('data-toggle-added', 'true');

        var $actionsCol = $attachmentRow.find('.col-md-4.text-right');

        if ($actionsCol.length) {
            // Try to determine current visibility from attachment data
            // Check if attachment row has visibility data attribute
            var isVisible = (creditNoteAttachments[attachmentId]['visible_to_customer'] ?? '0') == '1';

            var iconClass = isVisible ? 'fa-toggle-on' : 'fa-toggle-off';
            var tooltip = isVisible ? '<?= _l('hide_from_customer'); ?>' : '<?= _l('show_to_customer'); ?>';

            var toggleButton = $('<a>', {
                href: '#',
                class: 'text-muted mright10 attachment-visibility-toggle',
                'data-toggle': 'tooltip',
                'data-attachment-id': attachmentId,
                'data-visible': isVisible ? '1' : '0',
                'title': tooltip,
                html: '<i class="fa ' + iconClass + ' fa-lg" aria-hidden="true"></i>'
            });

            toggleButton.on('click', function(e) {
                e.preventDefault();
                toggleAttachmentVisibility(this);
            });

            // Add the toggle button before the delete button
            $actionsCol.prepend(toggleButton);

            // Initialize tooltip
            toggleButton.tooltip();
        }
    });
}

function toggleAttachmentVisibility(button) {
    var $button = $(button);
    var attachmentId = $button.data('attachment-id');
    var currentVisible = $button.data('visible') == '1';
    var newVisible = !currentVisible;

    // Update button state immediately (optimistic update)
    var $icon = $button.find('i');
    var newIconClass = newVisible ? 'fa-toggle-on' : 'fa-toggle-off';
    var newTooltip = newVisible ? '<?= _l('hide_from_customer'); ?>' : '<?= _l('show_to_customer'); ?>';

    $icon.removeClass('fa-toggle-on fa-toggle-off').addClass(newIconClass);
    $button.data('visible', newVisible ? '1' : '0');
    $button.attr('title', newTooltip).tooltip('fixTitle');

    // Call your existing toggle_file_visibility function if it exists
    if (typeof toggle_file_visibility === 'function') {
        // Get credit note ID from URL or page context
        if (creditNoteId) {
            toggle_file_visibility(attachmentId, creditNoteId, button);
        }
    }
}
</script>