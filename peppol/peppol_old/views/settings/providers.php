<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Provider Settings Tab Content -->
<?php $providers = get_peppol_providers(); ?>

<?php if (!is_peppol_configured()) : ?>
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    <?php echo _l('peppol_not_configured'); ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <?php
        $provider_options = [];
        foreach ($providers as $key => $provider) {
            $provider_options[] = ['id' => $key, 'name' => $provider['name']];
        }

        echo render_select(
            'settings[peppol_active_provider]',
            $provider_options,
            ['id', 'name'],
            _l('peppol_active_provider'),
            get_option('peppol_active_provider', 'ademico'),
            ['data-toggle' => 'tooltip', 'title' => 'Select your PEPPOL access point provider']
        );
        ?>
    </div>
    <div class="col-md-6">
        <?php
        echo render_select(
            'settings[peppol_environment]',
            [
                ['id' => 'sandbox', 'name' => 'Sandbox (Testing)'],
                ['id' => 'live', 'name' => 'Live (Production)']
            ],
            ['id', 'name'],
            _l('peppol_environment'),
            get_option('peppol_environment', 'sandbox'),
            ['data-toggle' => 'tooltip', 'title' => _l('peppol_environment_help')]
        );
        ?>
    </div>
</div>

<div class="tw-mt-4 tw-mb-4">
    <hr />
</div>

<?php foreach ($providers as $provider_key => $provider_config) : ?>

<div class="peppol-provider-settings" data-provider="<?php echo $provider_key; ?>"
    style="<?php echo get_option('peppol_active_provider', 'ademico') != $provider_key ? 'display: none;' : ''; ?>">

    <h4><?php echo $provider_config['name']; ?></h4>
    <hr>

    <?php
        // Load provider-specific view file
        if (isset($provider_config['view'])) {
            $this->load->view(PEPPOL_MODULE_NAME . '/' . $provider_config['view']);
        } else {
            // Fallback for providers without custom views
            echo '<div class="alert alert-info">No configuration available for this provider.</div>';
        }
        ?>

    <!-- Test Connection Button -->
    <div class="row">
        <div class="col-md-12">
            <button type="button" class="btn btn-info peppol-test-connection"
                data-provider="<?php echo $provider_key; ?>">
                <i class="fa fa-plug"></i> <?php echo _l('peppol_test_connection'); ?>
            </button>
            <div class="peppol-connection-test"></div>
        </div>
    </div>
</div>

<?php endforeach; ?>

<div class="tw-mt-4 tw-mb-4">
    <hr />
</div>

<!-- Webhook Information Section -->
<div id="webhook_info">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h4><i class="fa fa-info-circle"></i> <?php echo _l('webhook_urls'); ?></h4>
                <p><?php echo _l('webhook_configuration_help'); ?></p>

                <?php
                $active_provider = get_option('peppol_active_provider', 'ademico');
                if (isset($providers[$active_provider]) && isset($providers[$active_provider]['webhooks'])) :
                    $provider_config = $providers[$active_provider];
                ?>
                <div class="provider-webhook-section">
                    <h5><strong><?php echo $provider_config['name']; ?>:</strong></h5>

                    <?php if (isset($provider_config['webhooks']['endpoint'])) : ?>
                    <strong><?php echo _l('peppol_webhook_dedicated'); ?>:</strong><br>
                    <code><?php echo site_url($provider_config['webhooks']['endpoint']); ?></code><br>
                    <?php endif; ?>

                    <?php if (isset($provider_config['webhooks']['general'])) : ?>
                    <strong><?php echo _l('peppol_webhook_general'); ?>:</strong><br>
                    <code><?php echo site_url($provider_config['webhooks']['general']); ?></code><br>
                    <?php endif; ?>

                    <?php if (isset($provider_config['webhooks']['signature_header'])) : ?>
                    <small class="text-muted"><?php echo _l('peppol_webhook_signature'); ?>:
                        <?php echo $provider_config['webhooks']['signature_header']; ?></small><br>
                    <?php endif; ?>

                    <?php if (isset($provider_config['webhooks']['supported_events'])) : ?>
                    <small class="text-muted"><?php echo _l('peppol_webhook_events'); ?>:
                        <?php echo implode(', ', $provider_config['webhooks']['supported_events']); ?></small>
                    <?php endif; ?>

                    <br><br>
                </div>
                <?php else : ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <?php echo _l('peppol_no_webhook_config'); ?>
                </div>
                <?php endif; ?>

                <div class="provider-webhook-section">
                    <h5><strong><?php echo _l('health_check'); ?>:</strong></h5>
                    <code><?php echo site_url('peppol/webhook/health'); ?></code><br>
                    <small class="text-muted"><?php echo _l('peppol_webhook_health_help'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics (if module is configured) -->
    <?php if (is_peppol_configured()) : ?>
    <?php
        $CI = &get_instance();
        $CI->load->model('peppol/peppol_model');
        $stats = $CI->peppol_model->get_invoice_statistics();
        ?>

    <h4><?php echo _l('statistics'); ?></h4>
    <hr>

    <div class="row">
        <div class="col-md-3">
            <div class="peppol-stats-widget">
                <div class="peppol-stats-number text-success"><?php echo $stats['total_sent']; ?></div>
                <div class="peppol-stats-label"><?php echo _l('invoices_sent'); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="peppol-stats-widget">
                <div class="peppol-stats-number text-warning"><?php echo $stats['total_pending']; ?></div>
                <div class="peppol-stats-label"><?php echo _l('invoices_pending'); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="peppol-stats-widget">
                <div class="peppol-stats-number text-danger"><?php echo $stats['total_failed']; ?></div>
                <div class="peppol-stats-label"><?php echo _l('invoices_failed'); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="peppol-stats-widget">
                <div class="peppol-stats-number text-info"><?php echo $stats['total_received']; ?></div>
                <div class="peppol-stats-label"><?php echo _l('documents_received'); ?></div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Provider selection change functionality
    $(document).on('change', 'select[name="settings[peppol_active_provider]"]', function () {
        var provider = $(this).val();
        $('.peppol-provider-settings').hide();
        $('.peppol-provider-settings[data-provider="' + provider + '"]').show();
        
        // Update webhook information if function exists
        if (typeof updateWebhookInfo === 'function') {
            updateWebhookInfo(provider);
        }
    });

    // Initialize provider settings visibility
    var activeProvider = $('select[name="settings[peppol_active_provider]"]').val();
    if (activeProvider) {
        $('.peppol-provider-settings').hide();
        $('.peppol-provider-settings[data-provider="' + activeProvider + '"]').show();
    }

    // Test connection functionality is handled by the main peppol.js file
});
</script>