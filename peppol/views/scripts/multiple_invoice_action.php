<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (staff_can('create', 'invoices')) { ?>
<template id="multiple_invoice_peppol_dropdown_template">
    <div class="btn-group mleft5" data-toggle="tooltip" data-title="<?= _l('peppol_batch_action'); ?>">
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">
            <?php echo _l('peppol_batch_action'); ?> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            <li><a href="javascript:;"
                    onclick="saveMultipleInvoicePeppol('send')"><?php echo _l('peppol_send_selected'); ?></a>
            </li>
            <li class="divider"></li>

            <li><a href="javascript:;"
                    onclick="saveMultipleInvoicePeppol('download')"><?php echo _l('peppol_download_selected'); ?></a>
            </li>

        </ul>
    </div>
</template>
<?php } ?>

<script id="multiple_invoice_peppol_script">
'use strict';
document.addEventListener('DOMContentLoaded', function() {
    var invoicesTableSelector = 'table#invoices';
    var isInvoiceView = window.location.href.includes('admin/invoices') && $(invoicesTableSelector)
        .length > 0;
    if (isInvoiceView) {
        alert('dd');
        const saveMultipleInvoicePeppol = (action) => {
            const ids = [];
            const checkedBoxes = document.querySelectorAll(
                `${invoicesTableSelector} .mutliple-invoice-toggle:checked`);
            for (let i = 0; i < checkedBoxes.length; i++) {
                ids.push(checkedBoxes[i].value);
            }
            // 
            console.log(ids, action);

            return true;
        }
        window.saveMultipleInvoicePeppol = saveMultipleInvoicePeppol;


        // Prevent sorting when the check box is clicked on the heading 
        $(`${invoicesTableSelector} #mutliple-invoice-toggle`).on('click', function(e) {
            e.stopPropagation();
        });

        // Check box toggle select/deselect all
        $(`${invoicesTableSelector} tr`).on('change', '#mutliple-invoice-toggle', function(e) {
            var checkboxes = $(`${invoicesTableSelector} .mutliple-invoice-toggle`);
            checkboxes.prop('checked', $(this).prop('checked'));
        });
    }
});
</script>