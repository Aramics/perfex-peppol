/**
 * Peppol Directory Lookup JavaScript with Modal
 */

// Auto-lookup single customer (simple version)
function peppolAutoLookup(customerId) {
    if (!customerId) {
        alert('Invalid customer ID');
        return;
    }
    
    var $btn = $('button[onclick*="peppolAutoLookup(' + customerId + ')"]');
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Looking up...');
    
    $.ajax({
        url: admin_url + 'peppol/ajax_auto_lookup_customer',
        type: 'POST',
        data: { customer_id: customerId },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            alert('Peppol information updated successfully!\n\nCompany: ' + response.participant.name + '\nScheme: ' + response.participant.scheme + '\nIdentifier: ' + response.participant.identifier);
            
            if (response.participant.scheme) {
                $('input[name*="peppol_scheme"], input[name*="customers_peppol_scheme"]').val(response.participant.scheme);
            }
            if (response.participant.identifier) {
                $('input[name*="peppol_identifier"], input[name*="customers_peppol_identifier"]').val(response.participant.identifier);
            }
            
            if (window.location.href.indexOf('clients/client/') > -1) {
                location.reload();
            }
        } else {
            alert('Lookup failed: ' + response.message);
        }
    })
    .fail(function() {
        alert('Request failed. Please try again.');
    })
    .always(function() {
        $btn.prop('disabled', false).html('<i class="fa fa-search"></i> Auto-fill Peppol');
    });
}

// Modal-based batch lookup functionality
var PeppolBatchLookup = {
    isProcessing: false,
    currentProgress: 0,
    totalProcessed: 0,
    results: {
        successful: 0,
        failed: 0,
        multipleResults: 0
    },

    // Show the batch lookup modal
    showModal: function() {
        // Reset modal state
        this.resetModal();
        
        // Show modal
        $('#peppol-batch-lookup-modal').modal('show');
        
        // Initialize selectpicker for client dropdown when modal is shown
        $('#peppol-batch-lookup-modal').on('shown.bs.modal', function() {
            if (!$('#peppol_clientid').hasClass('selectpicker-initialized')) {
                init_ajax_search('customers', '#peppol_clientid');
                $('#peppol_clientid').addClass('selectpicker-initialized');
            }
        });
    },



    // Start the lookup process
    startLookup: function() {
        var mode = $('input[name="lookup_mode"]:checked').val();
        var customerIds = [];
        
        if (mode === 'selected') {
            customerIds = $('#peppol_clientid').val() || [];
            
            if (customerIds.length === 0) {
                alert('Please select at least one customer.');
                return;
            }
        }
        
        // Hide selection, show progress
        $('#peppol-customer-selection').hide();
        $('#peppol-progress').show();
        $('#start-lookup-btn').prop('disabled', true);
        
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
                    PeppolBatchLookup.results.successful++;
                } else {
                    if (result.message && (result.message.toLowerCase().indexOf('multiple') > -1 || result.message.indexOf('Manual selection required') > -1)) {
                        PeppolBatchLookup.results.multipleResults++;
                    } else {
                        PeppolBatchLookup.results.failed++;
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
        
        $('#start-lookup-btn').prop('disabled', false).text('Done');
    },

    // Reset modal
    resetModal: function() {
        this.isProcessing = false;
        this.currentProgress = 0;
        this.totalProcessed = 0;
        this.results = { successful: 0, failed: 0, multipleResults: 0 };
        
        // Reset form elements
        $('input[name="lookup_mode"][value="all"]').prop('checked', true);
        $('input[name="lookup_mode"][value="selected"]').prop('checked', false);
        $('#client-selection').hide();
        
        // Reset selectpicker if it's initialized
        if ($('#peppol_clientid').hasClass('selectpicker-initialized')) {
            $('#peppol_clientid').val('').selectpicker('refresh');
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
        
        // Reset button (button text will be from the PHP template)
        $('#start-lookup-btn').prop('disabled', false).find('i').attr('class', 'fa fa-play');
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
    // Make PeppolBatchLookup available globally
    window.PeppolBatchLookup = PeppolBatchLookup;
});