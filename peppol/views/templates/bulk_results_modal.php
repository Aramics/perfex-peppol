<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- PEPPOL Bulk Results Modal -->
<div class="modal fade" id="peppolBulkResultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo _l('close'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-list-alt"></i>
                    <?php echo _l('peppol_bulk_operation_results'); ?> (<span id="modal-document-type"></span>)
                </h4>
            </div>
            <div class="modal-body">
                <!-- Summary Statistics -->
                <div class="row mb-3">
                    <div class="col-sm-3 text-center">
                        <div class="bg-info text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block" id="stat-total">0</span>
                            <p><?php echo _l('peppol_total_processed'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-3 text-center">
                        <div class="bg-success text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block" id="stat-success">0</span>
                            <p><?php echo _l('peppol_successful'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-3 text-center">
                        <div class="bg-danger text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block" id="stat-errors">0</span>
                            <p><?php echo _l('peppol_failed'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-3 text-center">
                        <div class="bg-warning text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block" id="stat-rate">0%</span>
                            <p><?php echo _l('peppol_success_rate'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Summary Message -->
                <div class="alert alert-info tw-mt-4 tw-mb-8" id="summary-message" style="display: none;">
                    <i class="fa fa-info-circle"></i> <span id="message-text"></span>
                </div>

                <!-- Errors Section -->
                <div class="row mt-3" id="errors-section" style="display: none;">
                    <div class="col-md-12">
                        <div class="panel panel-danger tw-mb-0">
                            <div class="panel-heading">
                                <h4><?php echo _l('peppol_error_details'); ?> (<span id="error-count">0</span>
                                    <?php echo _l('peppol_errors_shown'); ?>)</h4>
                            </div>
                            <div class="panel-body" style="max-height: 300px; overflow-y: auto;" id="errors-container">
                                <!-- Errors will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>