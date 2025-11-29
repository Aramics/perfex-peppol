<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Define default PEPPOL settings tabs
$peppol_settings_tabs = [
    'general' => [
        'id' => 'peppol_general',
        'title' => _l('peppol_general_settings'),
        'file' => __DIR__ . '/settings/general.php',
        'active' => true,
        'weight' => 10
    ],
    'bank' => [
        'id' => 'peppol_bank',
        'title' => _l('peppol_bank_information'),
        'file' => __DIR__ . '/settings/bank.php',
        'active' => false,
        'weight' => 20
    ],
    'providers' => [
        'id' => 'peppol_providers',
        'title' => _l('peppol_providers'),
        'file' => __DIR__ . '/settings/providers.php',
        'active' => false,
        'weight' => 30
    ],
    'notifications' => [
        'id' => 'peppol_notifications',
        'title' => _l('peppol_notifications'),
        'file' => __DIR__ . '/settings/notifications.php',
        'active' => false,
        'weight' => 40
    ],
];

// Allow modules and plugins to modify tabs through hooks
$peppol_settings_tabs = hooks()->apply_filters('peppol_settings_tabs', $peppol_settings_tabs);

// Sort tabs by weight
uasort($peppol_settings_tabs, function ($a, $b) {
    return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
});

// Ensure at least one tab is active
$has_active = false;
foreach ($peppol_settings_tabs as $tab) {
    if (!empty($tab['active'])) {
        $has_active = true;
        break;
    }
}

// If no active tab found, make first one active
if (!$has_active && !empty($peppol_settings_tabs)) {
    $first_key = array_key_first($peppol_settings_tabs);
    $peppol_settings_tabs[$first_key]['active'] = true;
}
?>

<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="scroller arrow-left tw-mt-px"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right tw-mt-px"><i class="fa fa-angle-right"></i></div>
    <!-- Nav tabs -->
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <?php foreach ($peppol_settings_tabs as $tab_key => $tab) : ?>
            <?php if (empty($tab['hidden'])) : ?>
            <li role="presentation" class="<?php echo !empty($tab['active']) ? 'active' : ''; ?>">
                <a href="#<?php echo e($tab['id']); ?>" aria-controls="<?php echo e($tab['id']); ?>" role="tab"
                    data-toggle="tab">
                    <?php if (!empty($tab['icon'])) : ?>
                    <i class="<?php echo e($tab['icon']); ?>"></i>
                    <?php endif; ?>
                    <?php echo e($tab['title']); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Tab panes -->
<div class="tab-content mtop30">
    <?php foreach ($peppol_settings_tabs as $tab_key => $tab) : ?>
    <?php if (empty($tab['hidden'])) : ?>
    <div role="tabpanel" class="tab-pane <?php echo !empty($tab['active']) ? 'active' : ''; ?>"
        id="<?php echo e($tab['id']); ?>">
        <?php
                // Include tab content file if it exists
                if (!empty($tab['file']) && file_exists($tab['file'])) {
                    require_once($tab['file']);
                } elseif (!empty($tab['content'])) {
                    // Allow direct content specification
                    echo $tab['content'];
                } else {
                    echo '<div class="alert alert-warning">Tab content not found for: ' . e($tab['title']) . '</div>';
                }
                ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php require_once(__DIR__ . '/settings/scripts.php'); ?>