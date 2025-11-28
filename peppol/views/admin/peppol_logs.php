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
                <?php echo _l('peppol_logs'); ?>
            </h4>
            <?php if (staff_can('delete', 'peppol_logs')) { ?>
            <button type="button" class="btn btn-danger" onclick="clearLogs()">
                <i class="fa fa-trash"></i>
                <?php echo _l('peppol_clear_logs'); ?>
            </button>
            <?php } ?>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Logs Table -->
                        <?php render_datatable([
                            _l('id'),
                            _l('peppol_message'),
                            _l('peppol_date_created')
                        ], 'peppol-logs'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    // Initialize DataTable with Perfex pattern
    initDataTable('.table-peppol-logs', window.location.href, undefined,
        undefined, undefined, [2, 'desc']);
});

function clearLogs() {
    if (!confirm('<?php echo _l('peppol_confirm_clear_logs'); ?>')) {
        return;
    }

    $.post(admin_url + 'peppol/clear_logs', {}, function(response) {
        if (response.success) {
            alert_float('success', response.message);
            $('.table-peppol-logs').DataTable().ajax.reload();
        } else {
            alert_float('danger', response.message);
        }
    }, 'json').fail(function() {
        alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
    });
}
</script>