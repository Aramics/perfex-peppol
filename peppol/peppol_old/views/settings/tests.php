<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Tests Tab Content -->
<?php $providers = get_peppol_providers(); ?>

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <strong><?php echo _l('peppol_test_suite'); ?></strong><br>
            <?php echo _l('peppol_test_suite_help'); ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <label for="test_provider_select"><?php echo _l('peppol_test_provider'); ?></label>
                <select id="test_provider_select" class="form-control">
                    <?php foreach ($providers as $key => $provider) : ?>
                    <option value="<?php echo $key; ?>" <?php echo get_option('peppol_active_provider', 'ademico') == $key ? 'selected' : ''; ?>>
                        <?php echo $provider['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted"><?php echo _l('peppol_test_provider_help'); ?></small>
            </div>
            <div class="col-md-6">
                <label for="test_suite_select"><?php echo _l('peppol_test_suite_type'); ?></label>
                <select id="test_suite_select" class="form-control">
                    <option value="all"><?php echo _l('peppol_test_all'); ?></option>
                    <option value="legal_entity"><?php echo _l('peppol_test_legal_entities'); ?></option>
                    <option value="document"><?php echo _l('peppol_test_documents'); ?></option>
                </select>
                <small class="text-muted"><?php echo _l('peppol_test_suite_type_help'); ?></small>
            </div>
        </div>
        
        <div class="tw-mt-4">
            <button type="button" id="run-peppol-tests" class="btn btn-success btn-lg">
                <i class="fa fa-play"></i> <?php echo _l('peppol_run_tests'); ?>
            </button>
            <button type="button" id="stop-peppol-tests" class="btn btn-danger" style="display: none;">
                <i class="fa fa-stop"></i> <?php echo _l('peppol_stop_tests'); ?>
            </button>
        </div>
    </div>
</div>

<div id="test-results-container" class="tw-mt-4" style="display: none;">
    <div class="row">
        <div class="col-md-12">
            <div id="test-summary" class="alert" style="display: none;"></div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title"><?php echo _l('peppol_test_results'); ?></h5>
                </div>
                <div class="panel-body">
                    <div id="test-output" class="well" style="background: #f8f9fa; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; border: 1px solid #ddd;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Test Suite Runner functionality
    let testRunning = false;
    let testAborted = false;
    
    // Handle URL parameters for provider/test type override
    const urlParams = new URLSearchParams(window.location.search);
    const providerParam = urlParams.get('provider');
    const testTypeParam = urlParams.get('test_type');
    
    if (providerParam) {
        $('#test_provider_select').val(providerParam);
    }
    
    if (testTypeParam) {
        $('#test_suite_select').val(testTypeParam);
    }
    
    // Run Tests button click handler
    $('#run-peppol-tests').on('click', function() {
        if (testRunning) {
            return;
        }
        
        const provider = $('#test_provider_select').val();
        const testType = $('#test_suite_select').val();
        
        startTestExecution(provider, testType);
    });
    
    // Stop Tests button click handler
    $('#stop-peppol-tests').on('click', function() {
        if (testRunning) {
            stopTestExecution();
        }
    });
    
    function startTestExecution(provider, testType) {
        testRunning = true;
        testAborted = false;
        
        // Update UI
        $('#run-peppol-tests').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('peppol_running_tests'); ?>');
        $('#stop-peppol-tests').show();
        $('#test-results-container').show();
        $('#test-output').empty();
        $('#test-summary').hide();
        
        // Start the test execution
        const url = admin_url + 'peppol/run_tests';
        const data = {
            provider: provider,
            test_type: testType,
            action: 'start'
        };
        
        // Use a regular HTTP request to stream the output
        fetch(url + '?' + new URLSearchParams(data))
            .then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                function readStream() {
                    return reader.read().then(({ done, value }) => {
                        if (done || testAborted) {
                            finishTestExecution();
                            return;
                        }
                        
                        const chunk = decoder.decode(value, { stream: true });
                        appendTestOutput(chunk);
                        
                        return readStream();
                    });
                }
                
                return readStream();
            })
            .catch(error => {
                appendTestOutput('\n❌ <?php echo _l('peppol_test_error'); ?>: ' + error.message + '\n');
                finishTestExecution();
            });
    }
    
    function stopTestExecution() {
        testAborted = true;
        appendTestOutput('\n🛑 <?php echo _l('peppol_test_stopped'); ?>\n');
        finishTestExecution();
    }
    
    function finishTestExecution() {
        testRunning = false;
        
        // Update UI
        $('#run-peppol-tests').prop('disabled', false).html('<i class="fa fa-play"></i> <?php echo _l('peppol_run_tests'); ?>');
        $('#stop-peppol-tests').hide();
        
        // Show summary
        const output = $('#test-output').text();
        let summaryClass = 'alert-info';
        let summaryText = '<?php echo _l('peppol_test_completed'); ?>';
        
        if (output.includes('❌') || output.includes('💥')) {
            summaryClass = 'alert-danger';
            summaryText = '<?php echo _l('peppol_test_failed'); ?>';
        } else if (output.includes('✅') && output.includes('🎉')) {
            summaryClass = 'alert-success';
            summaryText = '<?php echo _l('peppol_test_passed'); ?>';
        }
        
        $('#test-summary').removeClass('alert-success alert-warning alert-danger alert-info')
                          .addClass(summaryClass)
                          .text(summaryText)
                          .show();
    }
    
    function appendTestOutput(text) {
        const outputDiv = $('#test-output');
        outputDiv.text(outputDiv.text() + text);
        
        // Auto-scroll to bottom
        outputDiv.scrollTop(outputDiv[0].scrollHeight);
    }
});
</script>