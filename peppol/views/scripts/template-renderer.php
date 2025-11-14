<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

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