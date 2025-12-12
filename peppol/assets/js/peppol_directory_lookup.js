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
		multipleResults: 0,
	},
	pendingMultipleSelections: [], // Array to store all customers needing selection

	// Show the batch lookup modal
	showModal: function (customerId) {
		var self = this;

		// Reset modal state
		this.resetModal();

		// Store customer ID for single lookup mode
		this.singleCustomerId = customerId || null;

		// Show modal
		$("#peppol-batch-lookup-modal").modal("show");

		// Initialize selectpicker for client dropdown when modal is shown
		$("#peppol-batch-lookup-modal").on("shown.bs.modal", function () {
			if (!$("#peppol_clientid").hasClass("selectpicker-initialized")) {
				init_ajax_search("customers", "#peppol_clientid");
				$("#peppol_clientid").addClass("selectpicker-initialized");
			}

			// Initialize button click handler if not already done
			if (!$("#start-lookup-btn").hasClass("handler-initialized")) {
				$("#start-lookup-btn")
					.on("click", function () {
						PeppolLookup.startLookup();
					})
					.addClass("handler-initialized");
			}

			// If single customer mode, pre-select and configure UI
			if (self.singleCustomerId) {
				self.setupSingleCustomerMode();
			}
		});
	},

	// Single customer lookup - shows modal and starts lookup immediately
	singleCustomerLookup: function (customerId) {
		if (!customerId) {
			alert("Invalid customer ID");
			return;
		}

		var self = this;

		// Reset modal state
		this.resetModal();

		// Store customer ID for single lookup mode
		this.singleCustomerId = customerId;

		// Show modal
		$("#peppol-batch-lookup-modal").modal("show");

		// Initialize selectpicker if needed and start lookup immediately
		$("#peppol-batch-lookup-modal").on("shown.bs.modal", function () {
			if (!$("#peppol_clientid").hasClass("selectpicker-initialized")) {
				init_ajax_search("customers", "#peppol_clientid");
				$("#peppol_clientid").addClass("selectpicker-initialized");
			}

			// Initialize button click handler if not already done
			if (!$("#start-lookup-btn").hasClass("handler-initialized")) {
				$("#start-lookup-btn")
					.on("click", function () {
						PeppolLookup.startLookup();
					})
					.addClass("handler-initialized");
			}

			// Hide customer selection section and start processing immediately
			$("#peppol-customer-selection").hide();
			$("#peppol-progress").show();
			$("#start-lookup-btn").prop("disabled", true);

			// Start lookup immediately for single customer
			setTimeout(function () {
				self.startLookup();
			}, 100);
		});
	},

	// Setup single customer mode (used for batch modal with pre-selected customer)
	setupSingleCustomerMode: function () {
		// Pre-select the customer in the dropdown
		if (this.singleCustomerId) {
			// Set selected mode and show client selection
			$('input[name="lookup_mode"][value="selected"]').prop(
				"checked",
				true
			);
			$("#client-selection").show();

			// Pre-select the customer (add option and select it)
			$("#peppol_clientid").append(
				'<option value="' +
					this.singleCustomerId +
					'" selected>Loading...</option>'
			);
			$("#peppol_clientid").selectpicker("refresh");

			// Disable the radio buttons and hide the all customers option
			$("#peppol-customer-selection .form-group:first-child").hide();
			$('input[name="lookup_mode"]').prop("disabled", true);
		}
	},

	// Add customer to multiple results queue
	addToMultipleResultsQueue: function (customerData, multipleResults) {
		// Add to pending selections
		this.pendingMultipleSelections.push({
			customer: customerData,
			results: multipleResults,
		});

		// Just collect them for now - don't show UI until processing is complete
		// Show a progress message that we found multiple results
		var html =
			'<div><i class="fa fa-warning text-warning"></i> ' +
			customerData.company +
			": Multiple participants found - will require manual selection</div>";
		$("#progress-details").append(html);
		$("#progress-details").scrollTop(
			$("#progress-details")[0].scrollHeight
		);
	},

	// Render all customers needing multiple result selections
	renderAllMultipleSelections: function () {
		var html = "";
		var totalPending = this.pendingMultipleSelections.length;

		// Update alert message
		var alertMsg =
			totalPending === 1
				? "Multiple participants found for <strong>" +
				  this.pendingMultipleSelections[0].customer.company +
				  "</strong>"
				: "Multiple participants found for <strong>" +
				  totalPending +
				  " customers</strong>";
		$(".alert-warning").html(
			"<strong>" +
				alertMsg +
				"</strong> - Please select the correct participant for each customer."
		);

		this.pendingMultipleSelections.forEach(function (
			selection,
			selectionIndex
		) {
			var customerData = selection.customer;
			var multipleResults = selection.results;
			var customerVat = customerData.vat
				? customerData.vat.replace(/\D/g, "")
				: null;
			var customerId = customerData.userid; // Use customer ID for unique identification

			// Customer header with skip option
			html +=
				'<div style="margin: 20px 0 10px 0; padding: 10px; background: #f0f0f0; border-left: 4px solid #337ab7; border-radius: 4px;">';
			html +=
				'<div style="display: flex; justify-content: space-between; align-items: center;">';
			html += '<h5 style="margin: 0; font-weight: 600; color: #337ab7;">';
			html += '<i class="fa fa-building"></i> ' + customerData.company;
			if (customerData.vat)
				html +=
					' <small style="color: #666;">(VAT: ' +
					customerData.vat +
					")</small>";
			html += "</h5>";
			html +=
				'<button type="button" class="btn btn-xs btn-default" onclick="PeppolLookup.skipSingleCustomer(' +
				customerId +
				')" style="margin-left: 10px;">';
			html += '<i class="fa fa-times"></i> Skip this company';
			html += "</button>";
			html += "</div>";
			html += "</div>";

			// Add "None of these" option first
			var noneRadioId = "participant_" + customerId + "_none";
			html +=
				'<div style="padding: 12px; margin: 6px 0 6px 20px; border: 1px solid #ddd; border-radius: 4px; background: #fff3cd; border-color: #ffeeba;">';
			html += '<div class="radio radio-warning">';
			html +=
				'<input type="radio" id="' +
				noneRadioId +
				'" name="selected_participant_' +
				customerId +
				'" value="none" data-customer-id="' +
				customerId +
				'">';
			html +=
				'<label for="' +
				noneRadioId +
				'" style="font-weight: normal; cursor: pointer;">';
			html +=
				'<div style="font-size: 15px; font-weight: 500; margin-bottom: 3px; color: #856404;">';
			html +=
				'<i class="fa fa-ban"></i> None of these options are correct';
			html += "</div>";
			html += '<div style="font-size: 12px; color: #856404;">';
			html +=
				"Company is not registered or these participants do not match";
			html += "</div>";
			html += "</label>";
			html += "</div>";
			html += "</div>";

			// Results for this customer
			multipleResults.forEach(function (result, resultIndex) {
				// Check if this result matches customer's VAT
				var isVatMatch = false;
				if (customerVat && result.vat) {
					var resultVat = result.vat.replace(/\D/g, "");
					isVatMatch = resultVat === customerVat;
				}

				var vatBadge = isVatMatch
					? '<span class="label label-success" style="margin-left: 8px;">VAT Match</span>'
					: "";
				var radioId =
					"participant_" + customerId + "_" + resultIndex;

				html +=
					'<div style="padding: 12px; margin: 6px 0 6px 20px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">';
				html += '<div class="radio radio-primary">';
				html +=
					'<input type="radio" id="' +
					radioId +
					'" name="selected_participant_' +
					customerId +
					'" value="' +
					resultIndex +
					'" data-customer-id="' +
					customerId +
					'">';
				html +=
					'<label for="' +
					radioId +
					'" style="font-weight: normal; cursor: pointer;">';
				html +=
					'<div style="font-size: 15px; font-weight: 500; margin-bottom: 3px;">' +
					(result.name || result.company || "Unknown Company") +
					vatBadge +
					"</div>";
				html += '<div style="font-size: 12px; color: #666;">';
				html +=
					'<span style="margin-right: 15px;"><strong>Scheme:</strong> ' +
					(result.scheme || "N/A") +
					"</span>";
				html +=
					'<span style="margin-right: 15px;"><strong>ID:</strong> ' +
					(result.identifier || "N/A") +
					"</span>";
				if (result.vat)
					html +=
						'<span style="margin-right: 15px;"><strong>VAT:</strong> ' +
						result.vat +
						"</span>";
				if (result.country)
					html +=
						"<span><strong>Country:</strong> " +
						result.country +
						"</span>";
				html += "</div>";
				html += "</label>";
				html += "</div>";
				html += "</div>";
			});
		});

		$("#multiple-results-list").html(html);

		// Update selection handling
		this.updateSelectionHandling();
	},

	// Update selection handling for multiple customers
	updateSelectionHandling: function () {
		var self = this;

		// Remove old handlers and add new ones
		$('input[name^="selected_participant_"]')
			.off("change")
			.on("change", function () {
				self.checkAllSelectionsComplete();
			});
	},

	// Check if any selections have been made (enable button if at least one)
	checkAllSelectionsComplete: function () {
		var totalCustomers = this.pendingMultipleSelections.length;
		var selectedCount = 0;

		// Count selections by checking each customer's radio buttons
		this.pendingMultipleSelections.forEach(function(selection) {
			var customerId = selection.customer.userid;
			if ($('input[name="selected_participant_' + customerId + '"]:checked').length > 0) {
				selectedCount++;
			}
		});

		// Enable confirm button if at least one selection is made
		$("#confirm-selection-btn").prop("disabled", selectedCount === 0);

		// Update button text to show progress
		var buttonText = 
			selectedCount === 0
				? "Make Selections to Continue"
				: selectedCount === totalCustomers
				? "Confirm All Selections (" + selectedCount + "/" + totalCustomers + ")"
				: "Confirm Selected (" + selectedCount + "/" + totalCustomers + ")";
		$("#confirm-selection-btn").text(buttonText);
	},

	// Skip a single customer from the multiple results queue
	skipSingleCustomer: function (customerId) {
		// Find customer by ID
		var customerIndex = -1;
		var skippedCustomer = null;
		
		for (var i = 0; i < this.pendingMultipleSelections.length; i++) {
			if (this.pendingMultipleSelections[i].customer.userid == customerId) {
				customerIndex = i;
				skippedCustomer = this.pendingMultipleSelections[i];
				break;
			}
		}
		
		if (customerIndex === -1) {
			console.error('Customer not found in pending selections');
			return;
		}

		// Log as skipped
		var html =
			'<div><i class="fa fa-info text-warning"></i> ' +
			skippedCustomer.customer.company +
			": Skipped by user</div>";
		$("#progress-details").append(html);
		$("#progress-details").scrollTop(
			$("#progress-details")[0].scrollHeight
		);

		// Remove from pending selections
		this.pendingMultipleSelections.splice(customerIndex, 1);

		// Re-render the list
		this.renderAllMultipleSelections();

		// If no more pending selections, continue processing
		if (this.pendingMultipleSelections.length === 0) {
			this.continueAfterMultipleResults();
		}
	},

	// Skip all multiple selections
	skipMultipleSelection: function () {
		var self = this;

		// Log all as skipped and continue processing
		this.pendingMultipleSelections.forEach(function (selection) {
			var html =
				'<div><i class="fa fa-info text-warning"></i> ' +
				selection.customer.company +
				": Skipped by user</div>";
			$("#progress-details").append(html);
		});
		$("#progress-details").scrollTop(
			$("#progress-details")[0].scrollHeight
		);

		this.continueAfterMultipleResults();
	},

	// Confirm multiple selections (send all in single batch request)
	confirmMultipleSelection: function () {
		var self = this;
		var selectionsToSend = [];
		
		// Collect all customers with selections made
		this.pendingMultipleSelections.forEach(function (selection) {
			var customerId = selection.customer.userid;
			var selectedRadio = $('input[name="selected_participant_' + customerId + '"]:checked');
			
			if (selectedRadio.length > 0) {
				var selectedValue = selectedRadio.val();
				
				if (selectedValue === "none") {
					// User selected "none of these"
					selectionsToSend.push({
						customer_id: customerId,
						type: 'none'
					});
				} else {
					// User selected a specific result
					var resultIndex = parseInt(selectedValue);
					var selectedResult = selection.results[resultIndex];
					
					selectionsToSend.push({
						customer_id: customerId,
						type: 'participant',
						scheme: selectedResult.scheme,
						identifier: selectedResult.identifier,
						name: selectedResult.name || selectedResult.company,
						country: selectedResult.country
					});
				}
			}
		});

		if (selectionsToSend.length === 0) {
			alert("Please make at least one selection before confirming.");
			return;
		}

		// Log unselected customers as skipped
		this.pendingMultipleSelections.forEach(function (selection) {
			var customerId = selection.customer.userid;
			var selectedRadio = $('input[name="selected_participant_' + customerId + '"]:checked');
			
			if (selectedRadio.length === 0) {
				// No selection made - skip this customer
				var html = '<div><i class="fa fa-info text-warning"></i> ' + 
						   selection.customer.company + ': Skipped (no selection made)</div>';
				$("#progress-details").append(html);
			}
		});

		// Send single batch request
		$.ajax({
			url: admin_url + "peppol/ajax_apply_batch_selections",
			type: "POST",
			data: {
				selections: selectionsToSend
			},
			dataType: "json"
		})
		.done(function (response) {
			if (response.success) {
				// Log all results from batch
				response.results.forEach(function(result) {
					var icon = result.success ? "fa-check text-success" : "fa-times text-danger";
					var html = '<div><i class="fa ' + icon + '"></i> ' + 
							   result.company + ': ' + result.message + '</div>';
					$("#progress-details").append(html);
					
					if (result.success) {
						self.results.successful++;
					} else {
						self.results.failed++;
					}
				});
				
				$("#progress-details").scrollTop($("#progress-details")[0].scrollHeight);
				
				// Continue to final results
				self.continueAfterMultipleResults();
			} else {
				alert('Failed to apply selections: ' + (response.message || 'Unknown error'));
			}
		})
		.fail(function () {
			alert('Request failed. Please try again.');
		});
	},

	// Continue after multiple results handling (show final results)
	continueAfterMultipleResults: function () {
		// Reset multiple results state
		this.pendingMultipleSelections = [];

		// Hide multiple results section
		$("#peppol-multiple-results").hide();
		$("#confirm-selection-btn").prop("disabled", true);

		// Clear selection UI
		$("#multiple-results-list").empty();

		// Show final results now that all selections are complete
		this.showResults();
	},

	// Start the lookup process
	startLookup: function () {
		var mode = $('input[name="lookup_mode"]:checked').val();
		var customerIds = [];

		// Handle single customer mode
		if (this.singleCustomerId) {
			customerIds = [this.singleCustomerId];
		} else if (mode === "selected") {
			customerIds = $("#peppol_clientid").val() || [];

			if (customerIds.length === 0) {
				alert("Please select at least one customer.");
				return;
			}
		}

		// Hide selection, show progress (only if not already done for single customer)
		if (
			!this.singleCustomerId ||
			$("#peppol-customer-selection").is(":visible")
		) {
			$("#peppol-customer-selection").hide();
			$("#peppol-progress").show();
			$("#start-lookup-btn").prop("disabled", true);
		}

		this.isProcessing = true;
		this.currentProgress = 0;
		this.results = {successful: 0, failed: 0, multipleResults: 0};

		// Start processing
		this.processNextBatch(customerIds, 0);
	},

	// Process next batch of customers
	processNextBatch: function (customerIds, offset) {
		var self = this;

		$.ajax({
			url: admin_url + "peppol/ajax_batch_lookup_progress",
			type: "POST",
			data: {
				customer_ids: customerIds.join(","),
				offset: offset,
			},
			dataType: "json",
		})
			.done(function (response) {
				if (response.success) {
					self.updateProgress(response);

					if (response.completed) {
						self.showResults();
					} else {
						// Continue with next batch
						self.processNextBatch(
							customerIds,
							response.next_offset
						);
					}
				} else {
					alert(
						"Processing failed: " +
							(response.message || "Unknown error")
					);
					self.resetModal();
				}
			})
			.fail(function () {
				alert("Request failed. Please try again.");
				self.resetModal();
			});
	},

	// Update progress display
	updateProgress: function (response) {
		// Validate response data with fallbacks
		var processed = parseInt(response.processed) || 0;
		var total = parseInt(response.total) || 1; // Avoid division by zero

		// Calculate percentage safely
		var percentage = total > 0 ? Math.round((processed / total) * 100) : 0;

		// Update progress bar with validation
		$(".progress-bar").css("width", percentage + "%");
		$(".progress-bar").text(processed + " / " + total);

		// Add batch results to details and handle multiple results
		if (response.batch_results) {
			response.batch_results.forEach(function (result) {
				// Check for multiple results that need user intervention
				if (
					result.multiple_results &&
					result.multiple_results.length > 1
				) {
					// Add to multiple results queue instead of immediately showing
					PeppolLookup.addToMultipleResultsQueue(
						result.customer_data,
						result.multiple_results
					);
					PeppolLookup.results.multipleResults++;
				} else {
					// Regular single result or error
					var icon = result.success
						? "fa-check text-success"
						: "fa-times text-danger";
					var html =
						'<div><i class="fa ' +
						icon +
						'"></i> ' +
						result.company +
						": " +
						result.message +
						"</div>";
					$("#progress-details").append(html);

					if (result.success) {
						PeppolLookup.results.successful++;
					} else {
						PeppolLookup.results.failed++;
					}
				}
			});

			// Scroll to bottom
			$("#progress-details").scrollTop(
				$("#progress-details")[0].scrollHeight
			);
		}
	},

	// Show final results (or multiple results selection if pending)
	showResults: function () {
		// If there are pending multiple selections, show selection UI instead of final results
		if (this.pendingMultipleSelections.length > 0) {
			$("#peppol-progress").hide();
			$("#peppol-multiple-results").show();
			this.renderAllMultipleSelections();
			return; // Don't show final results yet
		}

		// Show final results
		$("#peppol-progress").hide();
		$("#peppol-results").show();

		$("#successful-count").text(this.results.successful);
		$("#failed-count").text(this.results.failed);
		$("#multiple-count").text(this.results.multipleResults);

		// Copy progress details to results
		$("#detailed-results").html($("#progress-details").html());

		// Change button to close modal instead of resubmitting
		$("#start-lookup-btn")
			.prop("disabled", false)
			.text("Done")
			.off("click")
			.on("click", function () {
				$("#peppol-batch-lookup-modal").modal("hide");
			});

		// Reset single customer state when lookup completes
		this.singleCustomerId = null;

		// Handle post-lookup actions
		this.handlePostLookupActions();
	},

	// Handle actions after lookup completion
	handlePostLookupActions: function () {
		// Refresh directory table if it exists
		if (typeof directoryTable !== "undefined" && directoryTable) {
			directoryTable.ajax.reload();
		} else if ($(".table-peppol-directory").length > 0) {
			$(".table-peppol-directory").DataTable().ajax.reload();
		}

		// Trigger custom event for other components to listen
		$(document).trigger("peppolLookupSuccess");

		// If single customer mode and on client page, reload page
		if (
			this.singleCustomerId &&
			window.location.href.indexOf("clients/client/") > -1
		) {
			// Close modal and reload after short delay
			setTimeout(function () {
				$("#peppol-batch-lookup-modal").modal("hide");
				location.reload();
			}, 2000);
		}
	},

	// Reset modal
	resetModal: function () {
		this.isProcessing = false;
		this.currentProgress = 0;
		this.totalProcessed = 0;
		this.results = {successful: 0, failed: 0, multipleResults: 0};

		// Reset single customer mode
		this.singleCustomerId = null;

		// Reset multiple results queue
		this.pendingMultipleSelections = [];

		// Reset form elements
		$('input[name="lookup_mode"][value="all"]').prop("checked", true);
		$('input[name="lookup_mode"][value="selected"]').prop("checked", false);
		$('input[name="lookup_mode"]').prop("disabled", false);
		$("#client-selection").hide();

		// Show hidden elements for batch mode
		$("#peppol-customer-selection .form-group:first-child").show();

		// Reset selectpicker if it's initialized
		if ($("#peppol_clientid").hasClass("selectpicker-initialized")) {
			$("#peppol_clientid").empty().selectpicker("refresh");
		}

		// Reset progress elements
		$(".progress-bar").css("width", "0%");
		$(".progress-bar").text("0 / 0");
		$("#progress-details").empty();

		// Reset result elements
		$("#successful-count").text("0");
		$("#failed-count").text("0");
		$("#multiple-count").text("0");
		$("#detailed-results").empty();

		// Show/hide sections
		$("#peppol-customer-selection").show();
		$("#peppol-progress").hide();
		$("#peppol-multiple-results").hide();
		$("#peppol-results").hide();

		// Reset multiple results UI
		$("#multiple-results-list").empty();
		$("#confirm-selection-btn")
			.prop("disabled", true)
			.text("Confirm All Selections");
		$('input[name^="selected_participant_"]').prop("checked", false);

		// Reset button (restore original text, keep proper click handler)
		var originalButtonText =
			$("#start-lookup-btn").data("original-text") || "Start Auto Lookup";
		$("#start-lookup-btn")
			.prop("disabled", false)
			.html('<i class="fa fa-play"></i> ' + originalButtonText)
			.off("click")
			.on("click", function () {
				PeppolLookup.startLookup();
			});
	},
};

// Show radio button behavior
$(document).on("change", 'input[name="lookup_mode"]', function () {
	if ($(this).val() === "selected") {
		$("#client-selection").show();
	} else {
		$("#client-selection").hide();
	}
});

// Initialize when document ready
$(document).ready(function () {
	// Make PeppolLookup available globally
	window.PeppolLookup = PeppolLookup;
});
