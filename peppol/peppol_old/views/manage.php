<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin">
                                    <?php echo _l('peppol_invoices'); ?>
                                </h4>
                                <hr class="hr-panel-heading">
                            </div>
                        </div>

                        <?php if (!is_peppol_configured()): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php echo _l('peppol_not_configured'); ?>
                            <a href="<?php echo admin_url('settings?group=peppol'); ?>" class="alert-link">
                                <?php echo _l('peppol_settings'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table dt-table scroll-responsive" id="peppol-invoices-table">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('peppol_table_invoice'); ?></th>
                                                <th><?php echo _l('peppol_table_client'); ?></th>
                                                <th><?php echo _l('peppol_table_status'); ?></th>
                                                <th><?php echo _l('peppol_table_provider'); ?></th>
                                                <th><?php echo _l('peppol_table_document_id'); ?></th>
                                                <th><?php echo _l('peppol_table_sent_date'); ?></th>
                                                <th><?php echo _l('peppol_table_action'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var table = $('#peppol-invoices-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?php echo admin_url('peppol/table'); ?>",
            "type": "POST"
        },
        "columns": [
            { "data": "invoice_number" },
            { "data": "client_name" },
            { "data": "status" },
            { "data": "provider" },
            { "data": "peppol_document_id" },
            { "data": "sent_at" },
            { "data": "actions", "orderable": false }
        ],
        "order": [[5, "desc"]],
        "language": {
            "emptyTable": "<?php echo _l('dt_empty_table'); ?>",
            "info": "<?php echo _l('dt_showing_entries'); ?>",
            "infoEmpty": "<?php echo _l('dt_info_empty'); ?>",
            "infoFiltered": "<?php echo _l('dt_info_filtered'); ?>",
            "lengthMenu": "<?php echo _l('dt_length_menu'); ?>",
            "loadingRecords": "<?php echo _l('dt_loading_records'); ?>",
            "processing": "<?php echo _l('dt_processing'); ?>",
            "search": "<?php echo _l('dt_search'); ?>",
            "zeroRecords": "<?php echo _l('dt_zero_records'); ?>",
            "paginate": {
                "first": "<?php echo _l('dt_paginate_first'); ?>",
                "last": "<?php echo _l('dt_paginate_last'); ?>",
                "next": "<?php echo _l('dt_paginate_next'); ?>",
                "previous": "<?php echo _l('dt_paginate_previous'); ?>"
            }
        }
    });
});
</script>

<?php init_tail(); ?>