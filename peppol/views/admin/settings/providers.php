<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Get registered provider instances
$provider_instances = peppol_get_registered_providers();
$active_provider = get_option('peppol_active_provider', '');

// Convert provider instances to info for display
$providers = [];
foreach ($provider_instances as $provider_id => $instance) {
    try {
        if ($instance instanceof Abstract_peppol_provider) {
            $providers[] = $instance->get_provider_info();
        }
    } catch (Exception $e) {
        // Skip invalid providers
        continue;
    }
}
?>

<?php if (empty($providers)) : ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <?php echo _l('peppol_no_providers_registered'); ?>
</div>
<?php else : ?>
<form method="post" action="<?php echo admin_url('settings'); ?>">
    <input type="hidden" name="group" value="peppol" />

    <!-- Active Provider Selection -->
    <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
        data-title="<?php echo e(_l('peppol_active_provider_help')); ?>"></i>
    <?php echo render_select('settings[peppol_active_provider]', $providers, ['id', 'name'], _l('peppol_active_provider'), $active_provider, ['onchange' => 'peppolProviderChanged()']); ?>

    <?php if (function_exists('perfex_saas_is_tenant') && !perfex_saas_is_tenant()) : ?>
    <!-- SaaS Enforcement Option -->
    <?php render_yes_no_option('ps_global_peppol_enforce_on_all_tenants', _l('peppol_enforce_on_all_tenants'), _l('peppol_enforce_on_all_tenants_help')); ?>
    <?php endif; ?>

    <hr />

    <!-- Provider Configurations -->
    <?php foreach ($providers as $provider) : ?>
    <div class="provider-config" id="provider-config-<?php echo e($provider['id']); ?>"
        style="display: <?php echo $provider['id'] === $active_provider ? 'block' : 'none'; ?>;">
        <?php
                // Render provider-specific configuration fields from the instance
                try {
                    if (isset($provider_instances[$provider['id']])) {
                        $instance = $provider_instances[$provider['id']];
                        // Render settings using the provider's own inputs and values
                        echo $instance->render_setting_inputs();
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Error loading provider settings: ' . e($e->getMessage()) . '</div>';
                }
                ?>

        <?php if (!empty($provider['test_connection']) && $provider['test_connection']) : ?>
        <hr />
        <div class="form-group">
            <button type="button" class="btn btn-info btn-test-connection"
                data-provider="<?php echo e($provider['id']); ?>">
                <i class="fa fa-plug"></i>
                <?php echo _l('peppol_test_connection'); ?>
            </button>
            <div id="test-result-<?php echo e($provider['id']); ?>" class="test-connection-result"></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</form>
<?php endif; ?>