<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Peppol Batch Directory Lookup Modal -->
<div class="modal fade" id="peppol-batch-lookup-modal" tabindex="-1" role="dialog" aria-labelledby="peppolBatchLookupModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="peppolBatchLookupModalLabel" data-original-title="<?php echo _l('peppol_auto_lookup_button'); ?>">
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
                                <select id="peppol_clientid" name="peppol_clientid" data-live-search="true" data-width="100%" 
                                    class="ajax-search selectpicker" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" 
                                    multiple data-actions-box="true">
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
                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                            <span id="progress-text">0 / 0</span>
                        </div>
                    </div>
                    
                    <div id="progress-details" style="margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 10px; background-color: #f9f9f9;">
                        <!-- Progress details will appear here -->
                    </div>
                </div>

                <!-- Multiple Results Selection -->
                <div id="peppol-multiple-results" style="display: none;">
                    <div class="alert alert-warning">
                        <strong><?php echo _l('peppol_multiple_results_found'); ?></strong> - <?php echo _l('peppol_multiple_results_help'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label"><?php echo _l('peppol_select_correct_participant'); ?>:</label>
                        <div id="multiple-results-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 10px; margin-top: 10px;">
                            <!-- Multiple results will appear here -->
                        </div>
                    </div>
                    
                    <div class="text-center" style="margin-top: 15px;">
                        <button type="button" class="btn btn-default" onclick="PeppolLookup.skipMultipleSelection()"><?php echo _l('peppol_skip_selection'); ?></button>
                        <button type="button" class="btn btn-success" onclick="PeppolLookup.confirmMultipleSelection()" disabled id="confirm-selection-btn">
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
                    
                    <div id="detailed-results" style="margin-top: 20px; max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 10px; background-color: #f9f9f9;">
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