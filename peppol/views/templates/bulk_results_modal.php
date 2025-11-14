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
                        <div class="panel panel-danger tw-mb-0">
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
        </div>
    </div>
</div>
</script>

<script>
/**
 * Simple template renderer for modal templates
 * Supports basic {{variable}} and {{#if}} {{/if}} conditionals
 */
window.PeppolModalRenderer = {
    /**
     * Render template with data
     */
    render: function(templateId, data) {
        var template = document.getElementById(templateId);
        if (!template) {
            console.error('Template not found: ' + templateId);
            return '';
        }

        var html = template.innerHTML;

        // Process conditionals first
        html = this.processConditionals(html, data);

        // Process loops
        html = this.processLoops(html, data);

        // Process simple variables
        html = this.processVariables(html, data);

        return html;
    },

    /**
     * Process {{#if}} conditionals
     */
    processConditionals: function(html, data) {
        return html.replace(/\{\{#if\s+([^}]+)\}\}([\s\S]*?)\{\{\/if\}\}/g, function(match, condition,
            content) {
            var value = data[condition.trim()];
            return value ? content : '';
        });
    },

    /**
     * Process {{#each}} loops
     */
    processLoops: function(html, data) {
        return html.replace(/\{\{#each\s+([^}]+)\}\}([\s\S]*?)\{\{\/each\}\}/g, function(match, arrayName,
            content) {
            var array = data[arrayName.trim()];
            if (!Array.isArray(array)) return '';

            return array.map(function(item, index) {
                var itemContent = content;
                // Replace {{this}} with item value
                itemContent = itemContent.replace(/\{\{this\}\}/g, item);
                // Replace {{@index_plus_1}} with 1-based index
                itemContent = itemContent.replace(/\{\{@index_plus_1\}\}/g, index + 1);
                return itemContent;
            }).join('');
        });
    },

    /**
     * Process {{variable}} replacements
     */
    processVariables: function(html, data) {
        return html.replace(/\{\{([^}]+)\}\}/g, function(match, variable) {
            var keys = variable.trim().split('.');
            var value = data;

            for (var i = 0; i < keys.length; i++) {
                if (value && typeof value === 'object' && keys[i] in value) {
                    value = value[keys[i]];
                } else {
                    return '';
                }
            }

            return this.escapeHtml(value);
        }.bind(this));
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = text.toString();
        return div.innerHTML;
    }
};
</script>