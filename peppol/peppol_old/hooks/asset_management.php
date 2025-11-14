<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Asset Management Hooks
 */

/**
 * Load PEPPOL CSS in admin head
 */
hooks()->add_action('app_admin_head', function () {
    // Only load on PEPPOL-related pages
    if (peppol_should_load_assets()) {
        echo '<link rel="stylesheet" type="text/css" href="' . module_dir_url(PEPPOL_MODULE_NAME, 'assets/css/peppol.css') . '">' . PHP_EOL;
    }
});

/**
 * Load PEPPOL JavaScript in admin footer
 */
hooks()->add_action('app_admin_footer', function () {
    // Only load on PEPPOL-related pages
    if (peppol_should_load_assets()) {
        echo '<script type="text/javascript" src="' . module_dir_url(PEPPOL_MODULE_NAME, 'assets/js/peppol.js') . '"></script>' . PHP_EOL;
    }
});