<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if ($peppol_invoice): ?>
    <!-- PEPPOL Status -->
    <li>
        <a href="#" class="text-info">
            <i class="fa fa-paper-plane"></i> 
            <?php echo _l('peppol_status') . ': ' . _l('peppol_status_' . $peppol_invoice->status); ?>
        </a>
    </li>
    
    <?php if ($peppol_invoice->status == 'failed' && staff_can('create', 'peppol')): ?>
    <!-- Resend Failed Invoice -->
    <li>
        <a href="<?php echo admin_url('peppol/resend/' . $peppol_invoice->id); ?>"
           onclick="return confirm('<?php echo _l('peppol_confirm_resend'); ?>')">
            <i class="fa fa-refresh text-warning"></i> 
            <?php echo _l('peppol_resend'); ?>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (!empty($peppol_invoice->ubl_content) && staff_can('view', 'peppol')): ?>
    <!-- View UBL -->
    <li>
        <a href="<?php echo admin_url('peppol/view_ubl/' . $peppol_invoice->id); ?>" target="_blank">
            <i class="fa fa-eye text-info"></i> 
            <?php echo _l('peppol_view_ubl'); ?>
        </a>
    </li>
    
    <!-- Download UBL -->
    <li>
        <a href="<?php echo admin_url('peppol/download_ubl/' . $peppol_invoice->id); ?>">
            <i class="fa fa-download text-success"></i> 
            <?php echo _l('peppol_download_ubl'); ?>
        </a>
    </li>
    <?php endif; ?>

<?php else: ?>
    <?php if ((int)$invoice->status >= Invoices_model::STATUS_UNPAID && (int)$invoice->status <= Invoices_model::STATUS_OVERDUE && staff_can('create', 'peppol')): ?>
    <!-- Send via PEPPOL -->
    <li>
        <a href="#" onclick="sendPeppolInvoiceFromMenu(<?php echo $invoice->id; ?>); return false;">
            <i class="fa fa-paper-plane text-primary"></i> 
            <?php echo _l('peppol_send_invoice'); ?>
        </a>
    </li>
    <?php endif; ?>
<?php endif; ?>

<?php if (!isset($peppol_menu_js_added)): ?>
<script>
function sendPeppolInvoiceFromMenu(invoiceId) {
    if (!confirm("<?php echo _l('peppol_confirm_send'); ?>")) {
        return;
    }
    
    // Show loading state
    alert_float('info', 'Sending invoice via PEPPOL...');
    
    $.ajax({
        url: admin_url + "peppol/send_ajax/" + invoiceId,
        type: "POST",
        dataType: "json",
        data: {
            [csrfData.token_name]: csrfData.hash
        },
        success: function(response) {
            if (response.success) {
                alert_float("success", response.message || "<?php echo _l('peppol_invoice_sent_successfully'); ?>");
                setTimeout(() => location.reload(), 1500);
            } else {
                alert_float("danger", response.message || "<?php echo _l('peppol_invoice_send_failed'); ?>");
            }
        },
        error: function(xhr, status, error) {
            alert_float("danger", "<?php echo _l('peppol_invoice_send_failed'); ?>: " + error);
        }
    });
}

<?php 
// Prevent adding the script multiple times
$GLOBALS['peppol_menu_js_added'] = true; 
?>
</script>
<?php endif; ?>