<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (staff_can('create', 'peppol') && is_peppol_configured()) { ?>

<div class="btn-group mleft5" id="multiple_invoice_peppol_dropdown_template" data-toggle="tooltip"
    data-title="<?= _l('peppol_batch_action'); ?>">
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
<?php } ?>

<script id="multiple_invoice_peppol_script">
'use strict';
document.addEventListener('DOMContentLoaded', function() {
    var invoicesTableSelector = 'table#invoices';
    var isInvoiceView = window.location.href.includes('admin/invoices') && $(invoicesTableSelector)
        .length > 0;
    if (isInvoiceView) {
        const saveMultipleInvoicePeppol = (action) => {
            const ids = [];
            const checkedBoxes = document.querySelectorAll(
                `${invoicesTableSelector} .multiple-invoice-toggle:checked`);
            for (let i = 0; i < checkedBoxes.length; i++) {
                ids.push(checkedBoxes[i].value);
            }
            
            if (ids.length === 0) {
                alert_float('warning', '<?php echo _l('peppol_no_invoices_selected'); ?>');
                return false;
            }

            if (action === 'send') {
                handleBulkPeppolSend(ids);
            } else if (action === 'download') {
                handleBulkUblDownload(ids);
            }

            return true;
        }

        const handleBulkPeppolSend = (ids) => {
            if (!confirm('<?php echo _l('peppol_confirm_bulk_send'); ?>')) {
                return;
            }

            // Show loading state
            let progressModal = createProgressModal('<?php echo _l('peppol_sending_invoices'); ?>', ids.length);
            
            $.ajax({
                url: admin_url + 'peppol/bulk_send',
                type: 'POST',
                data: { invoice_ids: ids },
                dataType: 'json',
                beforeSend: function() {
                    $('#bulk-progress-modal').modal('show');
                },
                success: function(response) {
                    updateProgressModal(response);
                    if (response.success) {
                        alert_float('success', response.message);
                        // Reload the table to reflect status changes
                        $('.table-invoices').DataTable().ajax.reload();
                    } else {
                        alert_float('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#bulk-progress-modal').modal('hide');
                    alert_float('danger', '<?php echo _l('peppol_bulk_operation_failed'); ?>');
                },
                complete: function() {
                    setTimeout(() => {
                        $('#bulk-progress-modal').modal('hide');
                    }, 2000);
                }
            });
        }

        const handleBulkUblDownload = (ids) => {
            // Create a temporary form to submit for download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = admin_url + 'peppol/bulk_download_ubl';
            form.style.display = 'none';

            // Add invoice IDs as hidden inputs
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'invoice_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = csrfData.token_name;
            csrfInput.value = csrfData.hash;
            form.appendChild(csrfInput);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            alert_float('info', '<?php echo _l('peppol_preparing_download'); ?>');
        }

        const createProgressModal = (title, total) => {
            const modalHtml = `
                <div class="modal fade" id="bulk-progress-modal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">${title}</h4>
                            </div>
                            <div class="modal-body">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%">
                                        <span class="progress-text">0 / ${total}</span>
                                    </div>
                                </div>
                                <div class="progress-details mt-3">
                                    <div id="progress-success" class="text-success"></div>
                                    <div id="progress-errors" class="text-danger"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#bulk-progress-modal').remove();
            $('body').append(modalHtml);
            return $('#bulk-progress-modal');
        }

        const updateProgressModal = (response) => {
            if (response.progress) {
                const percent = (response.progress.completed / response.progress.total) * 100;
                $('.progress-bar').css('width', percent + '%');
                $('.progress-text').text(`${response.progress.completed} / ${response.progress.total}`);
                
                if (response.progress.success > 0) {
                    $('#progress-success').text(`✓ ${response.progress.success} sent successfully`);
                }
                
                if (response.progress.errors > 0) {
                    $('#progress-errors').text(`✗ ${response.progress.errors} failed`);
                }
            }
        }
        window.saveMultipleInvoicePeppol = saveMultipleInvoicePeppol;


        $('#multiple_invoice_peppol_dropdown_template').insertBefore('._buttons .pull-right')

        // Prevent sorting when the check box is clicked on the heading 
        $(`${invoicesTableSelector} #multiple-invoice-toggle`).on('click', function(e) {
            e.stopPropagation();
        });

        // Check box toggle select/deselect all
        $(`${invoicesTableSelector} tr`).on('change', '#multiple-invoice-toggle', function(e) {
            var checkboxes = $(`${invoicesTableSelector} .multiple-invoice-toggle`);
            checkboxes.prop('checked', $(this).prop('checked'));
        });
    }
});
</script>