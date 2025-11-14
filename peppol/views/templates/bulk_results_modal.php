<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<script type="text/template" id="peppol-bulk-results-modal-template">
<div class="modal fade" id="peppolBulkResultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo _l('close'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-list-alt"></i> 
                    <?php echo _l('peppol_bulk_operation_results'); ?> ({{documentType}})
                </h4>
            </div>
            <div class="modal-body">
                <!-- Summary Statistics -->
                <div class="row mb-3">
                    <div class="col-sm-3 text-center">
                        <div class="bg-info text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block">{{stats.total}}</span>
                            <p><?php echo _l('peppol_total_processed'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-3 text-center">
                        <div class="bg-success text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block">{{stats.success}}</span>
                            <p><?php echo _l('peppol_successful'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-3 text-center">
                        <div class="bg-danger text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block">{{stats.errors}}</span>
                            <p><?php echo _l('peppol_failed'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-3 text-center">
                        <div class="bg-warning text-white p-3 tw-rounded-md">
                            <span class="tw-text-3xl tw-font-bold tw-block">{{stats.successRate}}%</span>
                            <p><?php echo _l('peppol_success_rate'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Summary Message -->
                {{#if message}}
                <div class="alert alert-info tw-mt-4 tw-mb-8">
                    <i class="fa fa-info-circle"></i> {{message}}
                </div>
                {{/if}}

                <!-- Errors Section -->
                {{#if hasErrors}}
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h4><?php echo _l('peppol_error_details'); ?> ({{errorCount}} <?php echo _l('peppol_errors_shown'); ?>)</h4>
                            </div>
                            <div class="panel-body" style="max-height: 300px; overflow-y: auto;">
                                {{#each errors}}
                                <div class="alert alert-danger">
                                    <small class="text-muted">#{{@index_plus_1}}</small><br>
                                    {{this}}
                                </div>
                                {{/each}}
                            </div>
                        </div>
                    </div>
                </div>
                {{/if}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>
</script>