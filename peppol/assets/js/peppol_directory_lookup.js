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
    customers: [],
    selectedCustomers: [],
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
        var modalHtml = this.getModalHtml();
        
        // Remove existing modal
        $('#peppol-batch-lookup-modal').remove();
        
        // Add modal to body
        $('body').append(modalHtml);
        
        // Show modal
        $('#peppol-batch-lookup-modal').modal('show');
        
        // Load customers
        this.loadCustomers();
    },

    // Get modal HTML
    getModalHtml: function() {
        return `
        <div class="modal fade" id="peppol-batch-lookup-modal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-search"></i> Peppol Auto Lookup
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="peppol-customer-selection">
                            <div class="form-group">
                                <label>
                                    <input type="radio" name="lookup_mode" value="all" checked> 
                                    Auto-lookup all customers without Peppol data
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="radio" name="lookup_mode" value="selected"> 
                                    Select specific customers
                                </label>
                            </div>
                            
                            <div id="customer-list" style="display: none; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;">
                                <div class="text-center">
                                    <i class="fa fa-spinner fa-spin"></i> Loading customers...
                                </div>
                            </div>
                        </div>

                        <div id="peppol-progress" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Processing customers...</strong>
                            </div>
                            
                            <div class="progress">
                                <div class="progress-bar" style="width: 0%">
                                    <span id="progress-text">0 / 0</span>
                                </div>
                            </div>
                            
                            <div id="progress-details" style="margin-top: 15px; max-height: 200px; overflow-y: auto;">
                                <!-- Progress details will appear here -->
                            </div>
                        </div>

                        <div id="peppol-results" style="display: none;">
                            <div class="alert alert-success">
                                <strong>Lookup completed!</strong>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="stat-card">
                                        <h3 class="text-success" id="successful-count">0</h3>
                                        <p>Successfully Updated</p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="stat-card">
                                        <h3 class="text-danger" id="failed-count">0</h3>
                                        <p>Failed</p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="stat-card">
                                        <h3 class="text-warning" id="multiple-count">0</h3>
                                        <p>Multiple Results</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="detailed-results" style="margin-top: 20px; max-height: 300px; overflow-y: auto;">
                                <!-- Detailed results will appear here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="start-lookup-btn" onclick="PeppolBatchLookup.startLookup()">
                            <i class="fa fa-play"></i> Start Auto Lookup
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    },

    // Load customers from server
    loadCustomers: function() {
        var self = this;
        
        $.ajax({
            url: admin_url + 'peppol/ajax_get_customers',
            type: 'GET',
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                self.customers = response.customers;
                self.renderCustomerList();
            } else {
                $('#customer-list').html('<div class="text-danger">Failed to load customers</div>');
            }
        })
        .fail(function() {
            $('#customer-list').html('<div class="text-danger">Failed to load customers</div>');
        });
    },

    // Render customer list
    renderCustomerList: function() {
        var html = '<div class="checkbox"><label><input type="checkbox" id="select-all-customers"> <strong>Select All</strong></label></div><hr>';
        
        if (this.customers.length === 0) {
            html += '<div class="text-muted">No customers found without Peppol data.</div>';
        } else {
            this.customers.forEach(function(customer) {
                var vatInfo = customer.vat ? ' (' + customer.vat + ')' : ' (No VAT)';
                html += '<div class="checkbox">';
                html += '<label>';
                html += '<input type="checkbox" name="customer_ids[]" value="' + customer.userid + '"> ';
                html += customer.company + vatInfo;
                html += '</label>';
                html += '</div>';
            });
        }
        
        $('#customer-list').html(html);
        
        // Bind select all checkbox
        $('#select-all-customers').change(function() {
            $('input[name="customer_ids[]"]').prop('checked', $(this).is(':checked'));
        });
    },

    // Start the lookup process
    startLookup: function() {
        var mode = $('input[name="lookup_mode"]:checked').val();
        var customerIds = [];
        
        if (mode === 'selected') {
            customerIds = $('input[name="customer_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
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
        $('#peppol-progress').hide();
        $('#peppol-results').hide();
        $('#peppol-customer-selection').show();
        $('#start-lookup-btn').prop('disabled', false);
    }
};

// Show radio button behavior
$(document).on('change', 'input[name="lookup_mode"]', function() {
    if ($(this).val() === 'selected') {
        $('#customer-list').show();
    } else {
        $('#customer-list').hide();
    }
});

// Initialize when document ready
$(document).ready(function() {
    // Make PeppolBatchLookup available globally
    window.PeppolBatchLookup = PeppolBatchLookup;
});