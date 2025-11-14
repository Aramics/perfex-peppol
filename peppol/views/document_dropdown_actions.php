<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
/**
 * Unified PEPPOL document dropdown actions
 * 
 * Required variables:
 * - $document_type: 'invoice' or 'credit_note'
 * - $document: The invoice or credit note object
 * - $peppol_document: The PEPPOL document record (if exists)
 */

// Helper function to check if document can be sent via PEPPOL
function can_send_peppol_document($document, $document_type)
{
    if ($document_type === 'invoice') {
        return (int)$document->status >= Invoices_model::STATUS_UNPAID &&
            (int)$document->status <= Invoices_model::STATUS_OVERDUE;
    } elseif ($document_type === 'credit_note') {
        return (int)$document->status >= 1;
    }
    return false;
}

// Document type configuration
$config = [
    'invoice' => [
        'send_url' => 'peppol/send_ajax/' . $document->id,
        'resend_url' => 'peppol/resend/' . ($peppol_document ? $peppol_document->id : ''),
        'view_ubl_url' => 'peppol/view_ubl/' . ($peppol_document ? $peppol_document->id : ''),
        'download_ubl_url' => 'peppol/download_ubl/' . ($peppol_document ? $peppol_document->id : ''),
        'send_lang' => 'peppol_send_invoice',
        'js_function' => 'sendPeppolInvoice',
        'js_global_key' => 'peppol_single_js_added'
    ],
    'credit_note' => [
        'send_url' => 'peppol/send_credit_note_ajax/' . $document->id,
        'resend_url' => 'peppol/resend_credit_note/' . ($peppol_document ? $peppol_document->id : ''),
        'view_ubl_url' => 'peppol/view_credit_note_ubl/' . ($peppol_document ? $peppol_document->id : ''),
        'download_ubl_url' => 'peppol/download_credit_note_ubl/' . ($peppol_document ? $peppol_document->id : ''),
        'send_lang' => 'peppol_send_credit_note',
        'js_function' => 'sendPeppolCreditNote',
        'js_global_key' => 'peppol_credit_note_single_js_added'
    ]
];

$cfg = $config[$document_type];
?>

<!-- PEPPOL Actions Dropdown -->
<div class="btn-group peppol-dropdown">
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
        aria-expanded="false">
        <i class="fa fa-list-alt"></i> <?php echo _l('peppol'); ?> <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <?php if ($peppol_document) : ?>
        <!-- PEPPOL Status -->
        <li>
            <a href="#" class="text-info">
                <i class="fa fa-info-circle"></i>
                <?php echo _l('peppol_status'); ?>: <?php echo _l('peppol_status_' . $peppol_document->status); ?>
            </a>
        </li>

        <?php if ($peppol_document->status == 'failed' && staff_can('create', 'peppol')) : ?>
        <!-- Resend Failed Document -->
        <li>
            <a href="<?php echo admin_url($cfg['resend_url']); ?>"
                onclick="return confirm('<?php echo _l('peppol_confirm_resend'); ?>')">
                <i class="fa fa-refresh text-warning"></i>
                <?php echo _l('peppol_resend'); ?>
            </a>
        </li>
        <?php endif; ?>

        <?php if (!empty($peppol_document->ubl_content) && staff_can('view', 'peppol')) : ?>
        <li class="divider"></li>
        <!-- View UBL -->
        <li>
            <a href="<?php echo admin_url($cfg['view_ubl_url']); ?>" target="_blank">
                <?php echo _l('peppol_view_ubl'); ?>
            </a>
        </li>

        <!-- Download UBL -->
        <li>
            <a href="<?php echo admin_url($cfg['download_ubl_url']); ?>">
                <?php echo _l('peppol_download_ubl'); ?>
            </a>
        </li>
        <?php endif; ?>

        <?php else : ?>
        <?php if (can_send_peppol_document($document, $document_type) && staff_can('create', 'peppol')) : ?>
        <!-- Send via PEPPOL -->
        <li>
            <a href="#" onclick="<?php echo $cfg['js_function']; ?>(<?php echo $document->id; ?>); return false;">
                <?php echo _l($cfg['send_lang']); ?>
            </a>
        </li>
        <?php else : ?>
        <li>
            <a href="#" class="text-muted">
                <?php echo _l('peppol_not_available'); ?>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>
    </ul>
</div>

<?php if (!isset($GLOBALS[$cfg['js_global_key']])) : ?>
<script>
function <?php echo $cfg['js_function']; ?>(documentId) {
    if (!confirm("<?php echo _l('peppol_confirm_send'); ?>")) {
        return;
    }

    alert_float('info', '<?php echo _l('peppol_sending_' . $document_type); ?>');

    $.ajax({
        url: admin_url + "<?php echo $cfg['send_url']; ?>",
        type: "POST",
        dataType: "json",
        data: {
            [csrfData.token_name]: csrfData.hash
        },
        success: function(response) {
            if (response.success) {
                alert_float("success", response.message ||
                    "<?php echo _l('peppol_' . $document_type . '_sent_successfully'); ?>");
                setTimeout(() => location.reload(), 1500);
            } else {
                alert_float("danger", response.message ||
                    "<?php echo _l('peppol_' . $document_type . '_send_failed'); ?>");
            }
        },
        error: function(xhr, status, error) {
            alert_float("danger", "<?php echo _l('peppol_' . $document_type . '_send_failed'); ?>: " +
                error);
        }
    });
}
</script>
<?php
    $GLOBALS[$cfg['js_global_key']] = true;
endif; ?>

<style>
.peppol-dropdown .dropdown-menu {
    min-width: 200px;
}

.peppol-dropdown .dropdown-menu li a {
    padding: 8px 15px;
}

.peppol-dropdown .dropdown-menu .divider {
    margin: 5px 0;
}
</style>