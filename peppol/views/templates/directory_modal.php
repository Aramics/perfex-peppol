<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Peppol Batch Directory Lookup Modal -->
<div class="modal fade" id="peppol-batch-lookup-modal" tabindex="-1" role="dialog"
    aria-labelledby="peppolBatchLookupModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="peppolBatchLookupModalLabel"
                    data-original-title="<?php echo _l('peppol_auto_lookup_button'); ?>">
                    <i class="fa fa-search"></i> <?php echo _l('peppol_auto_lookup_button'); ?>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo _l('close'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Customer Selection Section -->
                <div id="peppol-customer-selection">
                    <div class="form-group">
                        <label>
                            <input type="radio" name="lookup_mode" value="all" checked>
                            <?php echo _l('peppol_lookup_all_customers'); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="lookup_mode" value="selected">
                            <?php echo _l('peppol_select_specific_customers'); ?>
                        </label>
                    </div>

                    <div id="client-selection" style="display: none; margin-top: 10px;">
                        <div class="f_client_id">
                            <div class="form-group select-placeholder">
                                <label for="peppol_clientid" class="control-label"><?= _l('client'); ?></label>
                                <select id="peppol_clientid" name="peppol_clientid" data-live-search="true"
                                    data-width="100%" class="ajax-search selectpicker"
                                    data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" multiple
                                    data-actions-box="true">
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div id="peppol-progress" style="display: none;">
                    <div class="alert alert-info">
                        <strong><?php echo _l('peppol_processing_customers'); ?>...</strong>
                    </div>

                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%"
                            data-percent="">
                        </div>
                    </div>

                    <div id="progress-details"
                        style="margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 10px; background-color: #f9f9f9;">
                        <!-- Progress details will appear here -->
                    </div>
                </div>

                <!-- Multiple Results Selection -->
                <div id="peppol-multiple-results" style="display: none;">
                    <div class="alert alert-warning">
                        <strong><?php echo _l('peppol_multiple_results_found'); ?></strong> -
                        <?php echo _l('peppol_multiple_results_help'); ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label"><?php echo _l('peppol_select_correct_participant'); ?>:</label>
                        <div id="multiple-results-list"
                            style="max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 10px; margin-top: 10px;">
                            <!-- Multiple results will appear here -->
                        </div>
                    </div>

                    <div class="text-center" style="margin-top: 15px;">
                        <button type="button" class="btn btn-default"
                            onclick="PeppolLookup.skipMultipleSelection()"><?php echo _l('peppol_skip_selection'); ?></button>
                        <button type="button" class="btn btn-success" onclick="PeppolLookup.confirmMultipleSelection()"
                            disabled id="confirm-selection-btn">
                            <i class="fa fa-check"></i> <?php echo _l('peppol_confirm_selection'); ?>
                        </button>
                    </div>
                </div>


                <!-- Results Section -->
                <div id="peppol-results" style="display: none;">
                    <div class="alert alert-success">
                        <strong><?php echo _l('peppol_lookup_completed'); ?>!</strong>
                    </div>

                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="stat-card">
                                <h3 class="text-success" id="successful-count">0</h3>
                                <p><?php echo _l('peppol_successfully_updated'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="stat-card">
                                <h3 class="text-danger" id="failed-count">0</h3>
                                <p><?php echo _l('peppol_failed'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="stat-card">
                                <h3 class="text-warning" id="multiple-count">0</h3>
                                <p><?php echo _l('peppol_multiple_results'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div id="detailed-results"
                        style="margin-top: 20px; max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 10px; background-color: #f9f9f9;">
                        <!-- Detailed results will appear here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary" id="start-lookup-btn"
                    data-original-text="<?php echo _l('peppol_start_auto_lookup'); ?>">
                    <i class="fa fa-play"></i> <?php echo _l('peppol_start_auto_lookup'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- HTML Templates for Dynamic Content -->
<template id="peppol-customer-header-template">
    <div style="margin: 20px 0 10px 0; padding: 10px; background: #f0f0f0; border-left: 4px solid #337ab7; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-weight: 600; color: #337ab7;">
                <i class="fa fa-building"></i> <span class="customer-company"></span><span class="customer-vat"></span>
            </h5>
            <button type="button" class="btn btn-xs btn-default skip-customer-btn" style="margin-left: 10px;">
                <i class="fa fa-times"></i> <?php echo _l('peppol_skip_company'); ?>
            </button>
        </div>
    </div>
</template>

<template id="peppol-none-option-template">
    <div style="padding: 12px; margin: 6px 0 6px 20px; border: 1px solid #ddd; border-radius: 4px; background: #fff3cd; border-color: #ffeeba;">
        <div class="radio radio-warning">
            <input type="radio" class="none-radio" value="none">
            <label class="none-radio-label" style="font-weight: normal; cursor: pointer;">
                <div style="font-size: 15px; font-weight: 500; margin-bottom: 3px; color: #856404;">
                    <i class="fa fa-ban"></i> <?php echo _l('peppol_none_correct_option'); ?>
                </div>
                <div style="font-size: 12px; color: #856404;">
                    <?php echo _l('peppol_company_not_registered'); ?>
                </div>
            </label>
        </div>
    </div>
</template>

<template id="peppol-result-option-template">
    <div style="padding: 12px; margin: 6px 0 6px 20px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
        <div class="radio radio-primary">
            <input type="radio" class="result-radio">
            <label class="result-radio-label" style="font-weight: normal; cursor: pointer;">
                <div style="font-size: 15px; font-weight: 500; margin-bottom: 3px;">
                    <span class="result-name"></span><span class="result-vat-badge"></span>
                </div>
                <div style="font-size: 12px; color: #666;" class="result-details">
                </div>
            </label>
        </div>
    </div>
</template>

<template id="peppol-progress-message-template">
    <div><i class="fa progress-icon"></i> <span class="progress-message"></span></div>
</template>

<!-- JavaScript translations for PEPPOL Lookup -->
<script>
window.peppolTranslations = {
    lookupDialogError: '<?php echo addslashes(_l('peppol_lookup_dialog_error')); ?>',
    invalidCustomerId: '<?php echo addslashes(_l('peppol_invalid_customer_id')); ?>',
    modalInitFailed: '<?php echo addslashes(_l('peppol_modal_init_failed')); ?>',
    lookupStartFailed: '<?php echo addslashes(_l('peppol_lookup_start_failed')); ?>',
    selectOneCustomer: '<?php echo addslashes(_l('peppol_select_one_customer')); ?>',
    requestFailed: '<?php echo addslashes(_l('peppol_request_failed')); ?>',
    requestTimeout: '<?php echo addslashes(_l('peppol_request_timeout')); ?>',
    serverError: '<?php echo addslashes(_l('peppol_server_error')); ?>',
    selectCorrectParticipant: '<?php echo addslashes(_l('peppol_select_correct_participant')); ?>',
    makeSelection: '<?php echo addslashes(_l('peppol_make_selection')); ?>',
    processSelectionsFailed: '<?php echo addslashes(_l('peppol_process_selections_failed')); ?>',
    processingFailed: '<?php echo addslashes(_l('peppol_processing_failed')); ?>',
    unknownError: '<?php echo addslashes(_l('peppol_unknown_error')); ?>',
    unknownCompany: '<?php echo addslashes(_l('peppol_unknown_company')); ?>',
    makeSelectionsContinue: '<?php echo addslashes(_l('peppol_make_selections_continue')); ?>',
    confirmAllSelections: '<?php echo addslashes(_l('peppol_confirm_all_selections')); ?>',
    startAutoLookup: '<?php echo addslashes(_l('peppol_start_auto_lookup')); ?>'
};
</script>