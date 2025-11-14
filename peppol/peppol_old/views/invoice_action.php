<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (!$client || empty($client->peppol_identifier)) : ?>
<div class="alert alert-info mtop15">
    <i class="fa fa-info-circle"></i>
    <?php echo _l('peppol_client_no_identifier'); ?>
</div>

<?php elseif ($peppol_invoice) : ?>
<div class="alert alert-info mtop15">
    <i class="fa fa-paper-plane"></i>
    <?php echo _l('peppol_status') . ': <strong>' . _l('peppol_status_' . $peppol_invoice->status) . '</strong>'; ?>

    <?php if ($peppol_invoice->peppol_document_id) : ?>
    <br><?php echo _l('peppol_document_id') . ': ' . $peppol_invoice->peppol_document_id; ?>
    <?php endif; ?>

    <?php if ($peppol_invoice->status == 'failed') : ?>
    <br>
    <a href="<?php echo admin_url('peppol/resend/' . $peppol_invoice->id); ?>" class="btn btn-warning btn-xs mtop5"
        onclick="return confirm('<?php echo _l('peppol_confirm_resend'); ?>')">
        <i class="fa fa-refresh"></i> <?php echo _l('peppol_resend'); ?>
    </a>
    <?php endif; ?>
</div>

<?php elseif ((int)$invoice->status >= Invoices_model::STATUS_UNPAID && (int)$invoice->status <= Invoices_model::STATUS_OVERDUE) : // Sent status 
?>
<div class="mtop15">
    <button type="button" class="btn btn-info" onclick="sendPeppolInvoiceAjax(<?php echo $invoice->id; ?>, this)">
        <i class="fa fa-paper-plane"></i> <?php echo _l('peppol_send_invoice'); ?>
    </button>
</div>

<script>
function sendPeppolInvoiceAjax(invoiceId, button) {
    if (!confirm("<?php echo _l('peppol_confirm_send'); ?>")) {
        return;
    }

    const originalText = $(button).html();
    $(button).prop("disabled", true).html("<i class=\"fa fa-spinner fa-spin\"></i> Sending...");

    $.ajax({
        url: admin_url + "peppol/send_ajax/" + invoiceId,
        type: "POST",
        dataType: "json",
        data: {
            [csrfData.token_name]: csrfData.hash
        },
        success: function(response) {
            if (response.success) {
                alert_float("success", response.message ||
                    "<?php echo _l('peppol_invoice_sent_successfully'); ?>");
                setTimeout(() => location.reload(), 1500);
            } else {
                alert_float("danger", response.message ||
                "<?php echo _l('peppol_invoice_send_failed'); ?>");
                $(button).prop("disabled", false).html(originalText);
            }
        },
        error: function(xhr, status, error) {
            alert_float("danger", "<?php echo _l('peppol_invoice_send_failed'); ?>: " + error);
            $(button).prop("disabled", false).html(originalText);
        }
    });
}
</script>
<?php endif; ?>