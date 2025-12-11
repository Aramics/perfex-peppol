<?php
defined('BASEPATH') or exit('No direct script access allowed');
$card_class = 'panel_s panel-body tw-px-4 tw-py-3';
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <!-- Page Header -->
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
            <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                <?php echo _l('peppol_directory_menu'); ?>
            </h4>
            <div>
                <?php if (is_admin()) { ?>
                <button type="button" class="btn btn-info" onclick="PeppolLookup.showModal()">
                    <i class="fa fa-search"></i> <?php echo _l('peppol_auto_lookup_button'); ?>
                </button>
                <?php } ?>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="row tw-mb-3">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong><?php echo _l('peppol_directory_title'); ?></strong> -
                    <?php echo _l('peppol_directory_info'); ?>
                </div>
            </div>
        </div>

        <!-- Directory Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <div class="clearfix"></div>
                            <?php render_datatable([
                                _l('client_company'),
                                _l('client_vat_number'),
                                _l('peppol_scheme'),
                                _l('peppol_identifier'),
                                _l('customer_active'),
                                _l('options')
                            ], 'peppol-directory'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Directory Modal Template -->
<?php $this->load->view('peppol/templates/directory_modal'); ?>

<?php init_tail(); ?>

<!-- Include Peppol Directory Lookup JavaScript -->
<script src="<?= module_dir_url('peppol', 'assets/js/peppol_directory_lookup.js') ?>"></script>

<!-- Directory Page Specific Styles -->
<style>
.stat-card {
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #f9f9f9;
    margin-bottom: 10px;
}

.stat-card h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: bold;
}

.stat-card p {
    margin: 0;
    color: #666;
    font-size: 12px;
}

#peppol-progress .progress {
    height: 25px;
}

#peppol-progress .progress-bar {
    line-height: 25px;
    font-weight: bold;
}

.table-peppol-directory td {
    vertical-align: middle;
}

.table-peppol-directory code {
    background-color: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}
</style>

<script>
$(function() {
    // Initialize DataTable with Perfex pattern
    var directoryTable = initDataTable('.table-peppol-directory', admin_url + 'peppol/directory', undefined,
        undefined, undefined, [0, 'asc']);

    // Custom search functionality could be added here if needed

    // Refresh table after successful lookup
    $(document).on('peppolLookupSuccess', function() {
        directoryTable.ajax.reload();
    });
});
</script>