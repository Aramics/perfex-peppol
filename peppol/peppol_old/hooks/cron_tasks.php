<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEPPOL Cron Tasks and Background Processing Hooks
 */

/**
 * Run PEPPOL cron tasks
 */
hooks()->add_action('after_cron_run', 'run_peppol_cron');