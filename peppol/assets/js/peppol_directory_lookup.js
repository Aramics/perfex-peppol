/**
 * Peppol Directory Lookup JavaScript with Modal
 */

// Modal-based lookup functionality (handles both batch and single customer)
var PeppolLookup = {
    isProcessing: false,
    currentProgress: 0,
    totalProcessed: 0,
    results: {
        successful: 0,
        failed: 0,
        multipleResults: 0
    },
    currentMultipleResults: null,
    currentMultipleCustomer: null,

    // Show the batch lookup modal
    showModal: function(customerId) {
        var self = this;
        
        // Reset modal state
        this.resetModal();
        
        // Store customer ID for single lookup mode
        this.singleCustomerId = customerId || null;
        
        // Show modal
        $('#peppol-batch-lookup-modal').modal('show');
        
        // Initialize selectpicker for client dropdown when modal is shown
        $('#peppol-batch-lookup-modal').on('shown.bs.modal', function() {
            if (!$('#peppol_clientid').hasClass('selectpicker-initialized')) {
                init_ajax_search('customers', '#peppol_clientid');
                $('#peppol_clientid').addClass('selectpicker-initialized');
            }
            
            // Initialize button click handler if not already done
            if (!$('#start-lookup-btn').hasClass('handler-initialized')) {
                $('#start-lookup-btn').on('click', function() {
                    PeppolLookup.startLookup();
                }).addClass('handler-initialized');
            }
            
            // If single customer mode, pre-select and configure UI
            if (self.singleCustomerId) {
                self.setupSingleCustomerMode();
            }
        });
    },

    // Single customer lookup - shows modal and starts lookup immediately
    singleCustomerLookup: function(customerId) {
        if (!customerId) {
            alert('Invalid customer ID');
            return;
        }
        
        var self = this;
        
        // Reset modal state
        this.resetModal();
        
        // Store customer ID for single lookup mode
        this.singleCustomerId = customerId;
        
        // Show modal
        $('#peppol-batch-lookup-modal').modal('show');
        
        // Initialize selectpicker if needed and start lookup immediately
        $('#peppol-batch-lookup-modal').on('shown.bs.modal', function() {
            if (!$('#peppol_clientid').hasClass('selectpicker-initialized')) {
                init_ajax_search('customers', '#peppol_clientid');
                $('#peppol_clientid').addClass('selectpicker-initialized');
            }
            
            // Initialize button click handler if not already done
            if (!$('#start-lookup-btn').hasClass('handler-initialized')) {
                $('#start-lookup-btn').on('click', function() {
                    PeppolLookup.startLookup();
                }).addClass('handler-initialized');
            }
            
            // Hide customer selection section and start processing immediately
            $('#peppol-customer-selection').hide();
            $('#peppol-progress').show();
            $('#start-lookup-btn').prop('disabled', true);
            
            // Start lookup immediately for single customer
            setTimeout(function() {
                self.startLookup();
            }, 100);
        });
    },

    // Setup single customer mode (used for batch modal with pre-selected customer)
    setupSingleCustomerMode: function() {
        // Pre-select the customer in the dropdown
        if (this.singleCustomerId) {
            // Set selected mode and show client selection
            $('input[name="lookup_mode"][value="selected"]').prop('checked', true);
            $('#client-selection').show();
            
            // Pre-select the customer (add option and select it)
            $('#peppol_clientid').append('<option value="' + this.singleCustomerId + '" selected>Loading...</option>');
            $('#peppol_clientid').selectpicker('refresh');
            
            // Disable the radio buttons and hide the all customers option
            $('#peppol-customer-selection .form-group:first-child').hide();
            $('input[name="lookup_mode"]').prop('disabled', true);
        }
    },

    // Handle multiple results with smart VAT matching
    handleMultipleResults: function(customerData, multipleResults) {
        // Smart VAT matching: check if any result matches customer's VAT
        var customerVat = customerData.vat ? customerData.vat.replace(/\D/g, '') : null;
        var exactMatches = [];
        
        if (customerVat) {
            exactMatches = multipleResults.filter(function(result) {
                if (result.vat) {
                    var resultVat = result.vat.replace(/\D/g, '');
                    return resultVat === customerVat;
                }
                return false;
            });
        }
        
        // If exactly one VAT match found, use it automatically
        if (exactMatches.length === 1) {
            var exactMatch = exactMatches[0];
            this.applyLookupResult(customerData.userid, exactMatch, 'VAT Match Found');
            return true; // Handled automatically
        }
        
        // If multiple VAT matches or no VAT match, show selection UI
        this.showMultipleResultsSelection(customerData, multipleResults, exactMatches);
        return false; // Requires user input
    },
    
    // Show multiple results selection UI
    showMultipleResultsSelection: function(customerData, multipleResults, vatMatches) {
        this.currentMultipleResults = multipleResults;
        this.currentMultipleCustomer = customerData;
        
        // Hide progress, show multiple results section
        $('#peppol-progress').hide();
        $('#peppol-multiple-results').show();
        
        // Build results list with clean, simple UI
        var html = '';
        multipleResults.forEach(function(result, index) {
            var isVatMatch = vatMatches.includes(result);
            var vatBadge = isVatMatch ? '<span class="label label-success">VAT Match</span>' : '';
            
            html += '<div class="radio" style="padding: 15px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9;">';
            html += '<label style="font-weight: normal; cursor: pointer; width: 100%; margin: 0;">';
            html += '<input type="radio" name="selected_participant" value="' + index + '" style="margin-right: 10px;">';
            html += '<div style="display: inline-block; width: calc(100% - 25px);">';
            html += '<div style="font-size: 16px; font-weight: 500; margin-bottom: 4px;">' + (result.name || result.company || 'Unknown Company') + ' ' + vatBadge + '</div>';
            html += '<div style="font-size: 13px; color: #666;">';
            html += '<span style="margin-right: 15px;"><strong>Scheme:</strong> ' + (result.scheme || 'N/A') + '</span>';
            html += '<span style="margin-right: 15px;"><strong>ID:</strong> ' + (result.identifier || 'N/A') + '</span>';
            if (result.vat) html += '<span style="margin-right: 15px;"><strong>VAT:</strong> ' + result.vat + '</span>';
            if (result.country) html += '<span><strong>Country:</strong> ' + result.country + '</span>';
            html += '</div>';
            html += '</div>';
            html += '</label>';
            html += '</div>';
        });
        
        $('#multiple-results-list').html(html);
        
        // Enable selection handling
        $('input[name="selected_participant"]').change(function() {
            $('#confirm-selection-btn').prop('disabled', false);
        });
    },
    
    // Skip multiple selection
    skipMultipleSelection: function() {
        // Mark as skipped and continue
        this.logMultipleResult(this.currentMultipleCustomer.company, 'Skipped by user');
        this.continueAfterMultipleResults();
    },
    
    // Confirm multiple selection
    confirmMultipleSelection: function() {
        var selectedIndex = $('input[name="selected_participant"]:checked').val();
        if (selectedIndex === undefined) {
            alert('Please select a participant first.');
            return;
        }
        
        var selectedResult = this.currentMultipleResults[parseInt(selectedIndex)];
        this.applyLookupResult(this.currentMultipleCustomer.userid, selectedResult, 'User Selected');
        this.continueAfterMultipleResults();
    },
    
    // Apply lookup result (either auto or user selected)
    applyLookupResult: function(customerId, result, method) {
        var self = this;
        // Apply the selected result
        $.ajax({
            url: admin_url + 'peppol/ajax_apply_lookup_result',
            type: 'POST',
            data: {
                customer_id: customerId,
                scheme: result.scheme,
                identifier: result.identifier,
                method: method
            },
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                self.logMultipleResult(
                    self.currentMultipleCustomer.company, 
                    'Applied: ' + (result.name || result.company) + ' (' + method + ')'
                );
                self.results.successful++;
            } else {
                self.logMultipleResult(
                    self.currentMultipleCustomer.company, 
                    'Failed to apply: ' + (response.message || 'Unknown error')
                );
                self.results.failed++;
            }
        }).fail(function() {
            self.logMultipleResult(
                self.currentMultipleCustomer.company, 
                'Failed to apply selection'
            );
            self.results.failed++;
        });
    },
    
    // Log multiple result handling
    logMultipleResult: function(company, message) {
        var icon = message.includes('Applied') || message.includes('VAT Match') ? 'fa-check text-success' : 
                   message.includes('Failed') ? 'fa-times text-danger' : 'fa-info text-warning';
        var html = '<div><i class="fa ' + icon + '"></i> ' + company + ': ' + message + '</div>';
        $('#progress-details').append(html);
        $('#progress-details').scrollTop($('#progress-details')[0].scrollHeight);
    },
    
    // Continue processing after multiple results handling
    continueAfterMultipleResults: function() {
        // Reset multiple results state
        this.currentMultipleResults = null;
        this.currentMultipleCustomer = null;
        
        // Hide multiple results section, show progress
        $('#peppol-multiple-results').hide();
        $('#peppol-progress').show();
        $('#confirm-selection-btn').prop('disabled', true);
        
        // Continue with next customer or finish
        this.processNextBatch([], this.currentProgress + 1);
    },

    // Start the lookup process
    startLookup: function() {
        var mode = $('input[name="lookup_mode"]:checked').val();
        var customerIds = [];
        
        // Handle single customer mode
        if (this.singleCustomerId) {
            customerIds = [this.singleCustomerId];
        } else if (mode === 'selected') {
            customerIds = $('#peppol_clientid').val() || [];
            
            if (customerIds.length === 0) {
                alert('Please select at least one customer.');
                return;
            }
        }
        
        // Hide selection, show progress (only if not already done for single customer)
        if (!this.singleCustomerId || $('#peppol-customer-selection').is(':visible')) {
            $('#peppol-customer-selection').hide();
            $('#peppol-progress').show();
            $('#start-lookup-btn').prop('disabled', true);
        }
        
        this.isProcessing = true;
        this.currentProgress = 0;
        this.results = { successful: 0, failed: 0, multipleResults: 0 };
        
        // Start processing
        this.processNextBatch(customerIds, 0);
    },

    // Process next batch of customers
    processNextBatch: function(customerIds, offset) {
        var self = this;
        
        $.ajax({
            url: admin_url + 'peppol/ajax_batch_lookup_progress',
            type: 'POST',
            data: {
                customer_ids: customerIds.join(','),
                offset: offset
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                self.updateProgress(response);
                
                if (response.completed) {
                    self.showResults();
                } else {
                    // Continue with next batch
                    self.processNextBatch(customerIds, response.next_offset);
                }
            } else {
                alert('Processing failed: ' + (response.message || 'Unknown error'));
                self.resetModal();
            }
        })
        .fail(function() {
            alert('Request failed. Please try again.');
            self.resetModal();
        });
    },

    // Update progress display
    updateProgress: function(response) {
        // Validate response data with fallbacks
        var processed = parseInt(response.processed) || 0;
        var total = parseInt(response.total) || 1; // Avoid division by zero
        
        // Calculate percentage safely
        var percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
        
        // Update progress bar with validation
        $('.progress-bar').css('width', percentage + '%');
        $('#progress-text').text(processed + ' / ' + total);
        
        // Add batch results to details and handle multiple results
        if (response.batch_results) {
            response.batch_results.forEach(function(result) {
                // Check for multiple results that need user intervention
                if (result.multiple_results && result.multiple_results.length > 1) {
                    // Check if we can auto-resolve with VAT matching
                    var handled = PeppolLookup.handleMultipleResults(result.customer_data, result.multiple_results);
                    
                    if (handled) {
                        // Auto-resolved with VAT match
                        var icon = 'fa-check text-success';
                        var html = '<div><i class="fa ' + icon + '"></i> ' + result.company + ': Auto-selected based on VAT match</div>';
                        $('#progress-details').append(html);
                        PeppolLookup.results.successful++;
                    } else {
                        // Requires user input - processing will pause here
                        PeppolLookup.results.multipleResults++;
                        return; // Stop processing until user selects
                    }
                } else {
                    // Regular single result or error
                    var icon = result.success ? 'fa-check text-success' : 'fa-times text-danger';
                    var html = '<div><i class="fa ' + icon + '"></i> ' + result.company + ': ' + result.message + '</div>';
                    $('#progress-details').append(html);
                    
                    if (result.success) {
                        PeppolLookup.results.successful++;
                    } else {
                        PeppolLookup.results.failed++;
                    }
                }
            });
            
            // Scroll to bottom
            $('#progress-details').scrollTop($('#progress-details')[0].scrollHeight);
        }
    },

    // Show final results
    showResults: function() {
        $('#peppol-progress').hide();
        $('#peppol-results').show();
        
        $('#successful-count').text(this.results.successful);
        $('#failed-count').text(this.results.failed);
        $('#multiple-count').text(this.results.multipleResults);
        
        // Copy progress details to results
        $('#detailed-results').html($('#progress-details').html());
        
        // Change button to close modal instead of resubmitting
        $('#start-lookup-btn').prop('disabled', false).text('Done').off('click').on('click', function() {
            $('#peppol-batch-lookup-modal').modal('hide');
        });
        
        // Handle post-lookup actions
        this.handlePostLookupActions();
    },
    
    // Handle actions after lookup completion
    handlePostLookupActions: function() {
        // Refresh directory table if it exists
        if (typeof directoryTable !== 'undefined' && directoryTable) {
            directoryTable.ajax.reload();
        } else if ($('.table-peppol-directory').length > 0) {
            $('.table-peppol-directory').DataTable().ajax.reload();
        }
        
        // Trigger custom event for other components to listen
        $(document).trigger('peppolLookupSuccess');
        
        // If single customer mode and on client page, reload page
        if (this.singleCustomerId && window.location.href.indexOf('clients/client/') > -1) {
            // Close modal and reload after short delay
            setTimeout(function() {
                $('#peppol-batch-lookup-modal').modal('hide');
                location.reload();
            }, 2000);
        }
    },

    // Reset modal
    resetModal: function() {
        this.isProcessing = false;
        this.currentProgress = 0;
        this.totalProcessed = 0;
        this.results = { successful: 0, failed: 0, multipleResults: 0 };
        
        // Reset single customer mode
        this.singleCustomerId = null;
        
        // Reset multiple results state
        this.currentMultipleResults = null;
        this.currentMultipleCustomer = null;
        
        // Reset form elements
        $('input[name="lookup_mode"][value="all"]').prop('checked', true);
        $('input[name="lookup_mode"][value="selected"]').prop('checked', false);
        $('input[name="lookup_mode"]').prop('disabled', false);
        $('#client-selection').hide();
        
        // Show hidden elements for batch mode
        $('#peppol-customer-selection .form-group:first-child').show();
        
        // Reset selectpicker if it's initialized
        if ($('#peppol_clientid').hasClass('selectpicker-initialized')) {
            $('#peppol_clientid').empty().selectpicker('refresh');
        }
        
        // Reset progress elements
        $('.progress-bar').css('width', '0%');
        $('#progress-text').text('0 / 0');
        $('#progress-details').empty();
        
        // Reset result elements
        $('#successful-count').text('0');
        $('#failed-count').text('0');
        $('#multiple-count').text('0');
        $('#detailed-results').empty();
        
        // Show/hide sections
        $('#peppol-customer-selection').show();
        $('#peppol-progress').hide();
        $('#peppol-multiple-results').hide();
        $('#peppol-results').hide();
        
        // Reset multiple results UI
        $('#multiple-results-list').empty();
        $('#confirm-selection-btn').prop('disabled', true);
        
        // Reset button (restore original text, keep proper click handler)
        var originalButtonText = $('#start-lookup-btn').data('original-text') || 'Start Auto Lookup';
        $('#start-lookup-btn')
            .prop('disabled', false)
            .html('<i class="fa fa-play"></i> ' + originalButtonText)
            .off('click')
            .on('click', function() {
                PeppolLookup.startLookup();
            });
    }
};

// Show radio button behavior
$(document).on('change', 'input[name="lookup_mode"]', function() {
    if ($(this).val() === 'selected') {
        $('#client-selection').show();
    } else {
        $('#client-selection').hide();
    }
});

// Initialize when document ready
$(document).ready(function() {
    // Make PeppolLookup available globally
    window.PeppolLookup = PeppolLookup;
});