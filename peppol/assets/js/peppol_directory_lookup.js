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
        var percentage = Math.round((response.processed / response.total) * 100);
        
        $('.progress-bar').css('width', percentage + '%');
        $('#progress-text').text(response.processed + ' / ' + response.total);
        
        // Add batch results to details
        if (response.batch_results) {
            response.batch_results.forEach(function(result) {
                var icon = result.success ? 'fa-check text-success' : 'fa-times text-danger';
                var html = '<div><i class="fa ' + icon + '"></i> ' + result.company + ': ' + result.message + '</div>';
                $('#progress-details').append(html);
                
                if (result.success) {
                    PeppolLookup.results.successful++;
                } else {
                    if (result.message && (result.message.toLowerCase().indexOf('multiple') > -1 || result.message.indexOf('Manual selection required') > -1)) {
                        PeppolLookup.results.multipleResults++;
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
        $('#peppol-results').hide();
        
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