/**
 * PEPPOL Directory Lookup Module
 * Professional implementation for managing PEPPOL participant directory lookups
 * with both single customer and batch processing capabilities
 *
 * @version 2.0
 * @author PEPPOL Module
 */

"use strict";

/**
 * PEPPOL Directory Lookup Manager
 * Handles modal-based lookup functionality for both single and batch customer processing
 */
const PeppolLookup = {
	// Configuration constants
	CONSTANTS: {
		MODAL_SELECTOR: "#peppol-batch-lookup-modal",
		BATCH_SIZE: 10,
		AUTO_START_DELAY: 100,
		EVENTS: {
			MODAL_SHOWN: "shown.bs.modal.peppolLookup",
			LOOKUP_SUCCESS: "peppolLookupSuccess",
		},
	},

	// Core state management
	state: {
		isProcessing: false,
		currentProgress: 0,
		totalProcessed: 0,
		singleCustomerId: null,
		autoStartSingleLookup: false,
		pendingMultipleSelections: [],
	},

	// Processing results tracking
	results: {
		successful: 0,
		failed: 0,
		multipleResults: 0,
	},

	// DOM references cache
	dom: {
		$modal: null,
		$progressBar: null,
		$progressDetails: null,
		$customerSelection: null,
		$startButton: null,
		$clientSelect: null,
	},

	// Template cache
	templates: {
		customerHeader: null,
		noneOption: null,
		resultOption: null,
		progressMessage: null,
	},

	/**
	 * Initialize the module and cache DOM references
	 * @returns {boolean} Success status
	 */
	init() {
		try {
			this.cacheDOMElements();
			this.attachGlobalEventHandlers();
			window.PeppolLookup = this; // Global access for backward compatibility
			return true;
		} catch (error) {
			console.error("PeppolLookup initialization failed:", error);
			return false;
		}
	},

	/**
	 * Cache frequently used DOM elements and templates
	 * @private
	 */
	cacheDOMElements() {
		this.dom.$modal = $(this.CONSTANTS.MODAL_SELECTOR);

		if (this.dom.$modal.length === 0) {
			throw new Error("PEPPOL modal not found in DOM");
		}

		// Cache modal-specific elements
		this.dom.$progressBar = this.dom.$modal.find(".progress-bar");
		this.dom.$progressDetails = this.dom.$modal.find("#progress-details");
		this.dom.$customerSelection = this.dom.$modal.find(
			"#peppol-customer-selection"
		);
		this.dom.$startButton = this.dom.$modal.find("#start-lookup-btn");
		this.dom.$clientSelect = this.dom.$modal.find("#peppol_clientid");

		// Cache templates
		this.cacheTemplates();
	},

	/**
	 * Cache HTML templates for reuse
	 * @private
	 * @throws {Error} If required templates are missing
	 */
	cacheTemplates() {
		const templates = {
			"peppol-customer-header-template": "customerHeader",
			"peppol-none-option-template": "noneOption",
			"peppol-result-option-template": "resultOption",
			"peppol-progress-message-template": "progressMessage",
		};

		const missingTemplates = [];

		Object.entries(templates).forEach(([templateId, propertyName]) => {
			const template = document.getElementById(templateId);
			if (template) {
				this.templates[propertyName] = template.content.cloneNode(true);
			} else {
				missingTemplates.push(templateId);
			}
		});

		if (missingTemplates.length > 0) {
			throw new Error(
				`Required templates not found: ${missingTemplates.join(", ")}`
			);
		}
	},

	/**
	 * Attach global event handlers
	 * @private
	 */
	attachGlobalEventHandlers() {
		// Handle lookup mode radio button changes
		$(document)
			.off("change.peppolLookup")
			.on(
				"change.peppolLookup",
				'input[name="lookup_mode"]',
				this.handleLookupModeChange.bind(this)
			);
	},

	/**
	 * Show the batch lookup modal
	 * @param {number|null} customerId - Optional customer ID for single customer mode
	 * @public
	 */
	showModal(customerId = null) {
		try {
			this.validateModalAvailability();
			this.resetModal();
			this.configureModalForCustomer(customerId);
			this.displayModal();
		} catch (error) {
			console.error("Failed to show modal:", error);
			this.showErrorMessage(
				window.peppolTranslations?.lookupDialogError
			);
		}
	},

	/**
	 * Single customer lookup - shows modal and starts lookup immediately
	 * @param {number} customerId - Customer ID to lookup
	 * @public
	 */
	singleCustomerLookup(customerId) {
		if (!this.validateCustomerId(customerId)) {
			this.showErrorMessage(
				window.peppolTranslations?.invalidCustomerId
			);
			return;
		}

		this.state.autoStartSingleLookup = true;
		this.showModal(customerId);
	},

	/**
	 * Validate modal availability
	 * @private
	 * @throws {Error} If modal is not available
	 */
	validateModalAvailability() {
		if (!this.dom.$modal || this.dom.$modal.length === 0) {
			this.cacheDOMElements();
		}
	},

	/**
	 * Validate customer ID
	 * @private
	 * @param {*} customerId - Customer ID to validate
	 * @returns {boolean} Validation result
	 */
	validateCustomerId(customerId) {
		return (
			customerId &&
			(typeof customerId === "number" ||
				typeof customerId === "string") &&
			!isNaN(Number(customerId))
		);
	},

	/**
	 * Configure modal for specific customer or batch mode
	 * @private
	 * @param {number|null} customerId - Customer ID or null for batch mode
	 */
	configureModalForCustomer(customerId) {
		this.state.singleCustomerId = customerId || null;
	},

	/**
	 * Display the modal and setup event handlers
	 * @private
	 */
	displayModal() {
		this.dom.$modal.modal("show");

		// Setup modal shown event handler (remove previous to prevent stacking)
		this.dom.$modal
			.off(this.CONSTANTS.EVENTS.MODAL_SHOWN)
			.on(
				this.CONSTANTS.EVENTS.MODAL_SHOWN,
				this.handleModalShown.bind(this)
			);
	},

	/**
	 * Handle modal shown event
	 * @private
	 */
	handleModalShown() {
		try {
			this.initializeSelectPicker();
			this.setupStartButton();
			this.configureModalBehavior();
		} catch (error) {
			console.error("Modal setup failed:", error);
			this.showErrorMessage(
				window.peppolTranslations?.modalInitFailed
			);
		}
	},

	/**
	 * Initialize select picker if not already initialized
	 * @private
	 */
	initializeSelectPicker() {
		if (!this.dom.$clientSelect.hasClass("selectpicker-initialized")) {
			if (typeof init_ajax_search === "function") {
				init_ajax_search("customers", "#peppol_clientid");
				this.dom.$clientSelect.addClass("selectpicker-initialized");
			} else {
				console.warn("init_ajax_search function not available");
			}
		}
	},

	/**
	 * Setup start button event handler
	 * @private
	 */
	setupStartButton() {
		if (!this.dom.$startButton.hasClass("handler-initialized")) {
			this.dom.$startButton
				.off("click.peppolLookup")
				.on("click.peppolLookup", this.startLookup.bind(this))
				.addClass("handler-initialized");
		}
	},

	/**
	 * Configure modal behavior based on current state
	 * @private
	 */
	configureModalBehavior() {
		if (this.state.singleCustomerId) {
			this.handleSingleCustomerMode();
		} else {
			this.handleBatchMode();
		}
	},

	/**
	 * Handle single customer mode configuration
	 * @private
	 */
	handleSingleCustomerMode() {
		if (this.state.autoStartSingleLookup) {
			this.startImmediateLookup();
		} else {
			this.setupSingleCustomerUI();
		}
	},

	/**
	 * Handle batch mode configuration
	 * @private
	 */
	handleBatchMode() {
		this.showCustomerSelection();
		this.enableStartButton();
	},

	/**
	 * Start immediate lookup for single customer
	 * @private
	 */
	startImmediateLookup() {
		this.hideCustomerSelection();
		this.showProgress();
		this.disableStartButton();

		setTimeout(() => {
			this.startLookup();
		}, this.CONSTANTS.AUTO_START_DELAY);

		this.state.autoStartSingleLookup = false;
	},

	/**
	 * Setup UI for single customer mode with manual start
	 * @private
	 */
	setupSingleCustomerUI() {
		// Pre-select the customer in the dropdown
		this.dom.$modal
			.find('input[name="lookup_mode"][value="selected"]')
			.prop("checked", true);
		this.dom.$modal.find("#client-selection").show();

		// Add customer option to select dropdown
		this.dom.$clientSelect.append(
			`<option value="${this.state.singleCustomerId}" selected>Loading...</option>`
		);

		if (this.dom.$clientSelect.data("selectpicker")) {
			this.dom.$clientSelect.selectpicker("refresh");
		}

		// Configure UI for single customer mode
		this.dom.$modal
			.find("#peppol-customer-selection .form-group:first-child")
			.hide();
		this.dom.$modal
			.find('input[name="lookup_mode"]')
			.prop("disabled", true);

		// Start the lookup
		this.startLookup();
	},

	/**
	 * Start the lookup process
	 * @public
	 */
	startLookup() {
		try {
			const mode = $('input[name="lookup_mode"]:checked').val();
			const customerIds = this.determineCustomerIds(mode);

			if (!this.validateLookupRequest(customerIds, mode)) {
				return;
			}

			this.prepareForProcessing();
			this.initiateLookupProcess(customerIds);
		} catch (error) {
			console.error("Lookup start failed:", error);
			this.showErrorMessage(
				window.peppolTranslations?.lookupStartFailed
			);
		}
	},

	/**
	 * Determine customer IDs based on lookup mode
	 * @private
	 * @param {string} mode - Lookup mode ('all' or 'selected')
	 * @returns {Array} Array of customer IDs
	 */
	determineCustomerIds(mode) {
		// Handle single customer mode
		if (
			this.state.singleCustomerId &&
			this.dom.$customerSelection.is(":hidden")
		) {
			return [this.state.singleCustomerId];
		}

		// Handle batch modes
		if (mode === "selected") {
			this.clearSingleCustomerState();
			return this.dom.$clientSelect.val() || [];
		}

		// All customers mode
		this.clearSingleCustomerState();
		return [];
	},

	/**
	 * Validate lookup request parameters
	 * @private
	 * @param {Array} customerIds - Customer IDs array
	 * @param {string} mode - Lookup mode
	 * @returns {boolean} Validation result
	 */
	validateLookupRequest(customerIds, mode) {
		if (mode === "selected" && customerIds.length === 0) {
			this.showErrorMessage(
				window.peppolTranslations?.selectOneCustomer
			);
			return false;
		}
		return true;
	},

	/**
	 * Prepare UI for processing
	 * @private
	 */
	prepareForProcessing() {
		if (
			!this.state.singleCustomerId ||
			this.dom.$customerSelection.is(":visible")
		) {
			this.hideCustomerSelection();
			this.showProgress();
			this.disableStartButton();
		}

		this.resetProcessingState();
	},

	/**
	 * Reset processing state
	 * @private
	 */
	resetProcessingState() {
		this.state.isProcessing = true;
		this.state.currentProgress = 0;
		this.results = {successful: 0, failed: 0, multipleResults: 0};
	},

	/**
	 * Initiate lookup process
	 * @private
	 * @param {Array} customerIds - Customer IDs to process
	 */
	initiateLookupProcess(customerIds) {
		this.processNextBatch(customerIds, 0);
	},

	/**
	 * Process next batch of customers
	 * @private
	 * @param {Array} customerIds - Customer IDs array
	 * @param {number} offset - Current batch offset
	 */
	processNextBatch(customerIds, offset) {
		const requestData = {
			customer_ids: customerIds.join(","),
			offset: offset,
		};

		$.ajax({
			url: `${admin_url}peppol/ajax_batch_lookup_progress`,
			type: "POST",
			data: requestData,
			dataType: "json",
			timeout: 30000,
		})
			.done((response) => this.handleBatchResponse(response, customerIds))
			.fail((xhr, status, error) =>
				this.handleBatchError(xhr, status, error)
			);
	},

	/**
	 * Handle successful batch response
	 * @private
	 * @param {Object} response - Server response
	 * @param {Array} customerIds - Original customer IDs
	 */
	handleBatchResponse(response, customerIds) {
		if (!response.success) {
			this.showErrorMessage(
				`${window.peppolTranslations?.processingFailed}: ${response.message || window.peppolTranslations?.unknownError}`
			);
			this.resetModal();
			return;
		}

		this.updateProgress(response);

		if (response.completed) {
			this.showResults();
		} else {
			this.processNextBatch(customerIds, response.next_offset);
		}
	},

	/**
	 * Handle batch processing error
	 * @private
	 * @param {Object} xhr - XMLHttpRequest object
	 * @param {string} status - Error status
	 * @param {string} error - Error message
	 */
	handleBatchError(xhr, status, error) {
		console.error("Batch processing error:", {xhr, status, error});

		let errorMessage = window.peppolTranslations?.requestFailed;
		if (status === "timeout") {
			errorMessage = window.peppolTranslations?.requestTimeout;
		} else if (xhr.status === 500) {
			errorMessage = window.peppolTranslations?.serverError;
		}

		this.showErrorMessage(errorMessage);
		this.resetModal();
	},

	/**
	 * Update progress display
	 * @private
	 * @param {Object} response - Progress response from server
	 */
	updateProgress(response) {
		const processed = parseInt(response.processed) || 0;
		const total = Math.max(parseInt(response.total) || 1, 1); // Prevent division by zero
		const percentage = Math.round((processed / total) * 100);

		this.updateProgressBar(percentage, processed, total);
		this.processBatchResults(response.batch_results || []);
	},

	/**
	 * Update progress bar display
	 * @private
	 * @param {number} percentage - Progress percentage
	 * @param {number} processed - Number of processed items
	 * @param {number} total - Total number of items
	 */
	updateProgressBar(percentage, processed, total) {
		this.dom.$progressBar
			.css("width", `${percentage}%`)
			.text(`${processed} / ${total}`);
	},

	/**
	 * Process batch results and update UI
	 * @private
	 * @param {Array} batchResults - Array of batch processing results
	 */
	processBatchResults(batchResults) {
		batchResults.forEach((result) => {
			if (this.hasMultipleResults(result)) {
				this.handleMultipleResults(result);
			} else {
				this.handleSingleResult(result);
			}
		});
	},

	/**
	 * Check if result has multiple participants
	 * @private
	 * @param {Object} result - Processing result
	 * @returns {boolean} True if multiple results exist
	 */
	hasMultipleResults(result) {
		return (
			result.multiple_results &&
			Array.isArray(result.multiple_results) &&
			result.multiple_results.length > 1
		);
	},

	/**
	 * Handle multiple results scenario
	 * @private
	 * @param {Object} result - Processing result with multiple participants
	 */
	handleMultipleResults(result) {
		this.addToMultipleResultsQueue(
			result.customer_data,
			result.multiple_results
		);
		this.results.multipleResults++;
		this.logMultipleResultsFound(result.customer_data);
	},

	/**
	 * Handle single result scenario
	 * @private
	 * @param {Object} result - Processing result
	 */
	handleSingleResult(result) {
		const icon = result.success
			? "fa-check text-success"
			: "fa-times text-danger";
		this.logProcessingResult(icon, result.company, result.message);

		if (result.success) {
			this.results.successful++;
		} else {
			this.results.failed++;
		}
	},

	/**
	 * Log multiple results found message
	 * @private
	 * @param {Object} customerData - Customer data
	 */
	logMultipleResultsFound(customerData) {
		this.logProgressMessage(
			"fa-warning text-warning",
			`${customerData.company}: Multiple participants found - will require manual selection`
		);
	},

	/**
	 * Log processing result
	 * @private
	 * @param {string} iconClass - CSS icon class
	 * @param {string} company - Company name
	 * @param {string} message - Result message
	 */
	logProcessingResult(iconClass, company, message) {
		this.logProgressMessage(iconClass, `${company}: ${message}`);
	},

	/**
	 * Log progress message using template
	 * @private
	 * @param {string} iconClass - CSS icon class
	 * @param {string} message - Message text
	 */
	logProgressMessage(iconClass, message) {
		const template = this.templates.progressMessage.cloneNode(true);
		const $template = $(template);

		$template.find(".progress-icon").addClass(iconClass);
		$template.find(".progress-message").text(message);

		this.appendToProgressLog($template);
	},

	/**
	 * Append message to progress log
	 * @private
	 * @param {string|jQuery} content - HTML string or jQuery element to append
	 */
	appendToProgressLog(content) {
		this.dom.$progressDetails.append(content);
		this.scrollProgressLogToBottom();
	},

	/**
	 * Scroll progress log to bottom
	 * @private
	 */
	scrollProgressLogToBottom() {
		if (this.dom.$progressDetails.length) {
			this.dom.$progressDetails.scrollTop(
				this.dom.$progressDetails[0].scrollHeight
			);
		}
	},

	/**
	 * Add customer to multiple results queue
	 * @private
	 * @param {Object} customerData - Customer information
	 * @param {Array} multipleResults - Array of participant results
	 */
	addToMultipleResultsQueue(customerData, multipleResults) {
		const customerId = customerData.userid;

		// Check for existing customer to prevent duplicates
		const existingIndex = this.state.pendingMultipleSelections.findIndex(
			(selection) => selection.customer.userid === customerId
		);

		if (existingIndex !== -1) {
			console.warn(
				"Customer already in multiple results queue, updating:",
				customerId
			);
			this.state.pendingMultipleSelections[existingIndex].results =
				multipleResults;
			return;
		}

		// Add to pending selections
		this.state.pendingMultipleSelections.push({
			customer: customerData,
			results: multipleResults,
		});
	},

	/**
	 * Show final results or multiple results selection UI
	 * @public
	 */
	showResults() {
		if (this.state.pendingMultipleSelections.length > 0) {
			this.showMultipleResultsSelection();
		} else {
			this.showFinalResults();
		}
	},

	/**
	 * Show multiple results selection interface
	 * @private
	 */
	showMultipleResultsSelection() {
		this.hideProgress();
		this.dom.$modal.find("#peppol-multiple-results").show();
		this.renderAllMultipleSelections();
	},

	/**
	 * Show final processing results
	 * @private
	 */
	showFinalResults() {
		this.hideProgress();
		this.dom.$modal.find("#peppol-results").show();
		this.updateFinalResultsCounts();
		this.setupCompletionHandler();
		this.clearSingleCustomerState();
		this.handlePostLookupActions();
	},

	/**
	 * Update final results counts in UI
	 * @private
	 */
	updateFinalResultsCounts() {
		this.dom.$modal.find("#successful-count").text(this.results.successful);
		this.dom.$modal.find("#failed-count").text(this.results.failed);
		this.dom.$modal
			.find("#multiple-count")
			.text(this.results.multipleResults);
		this.dom.$modal
			.find("#detailed-results")
			.html(this.dom.$progressDetails.html());
	},

	/**
	 * Setup completion handler for final results
	 * @private
	 */
	setupCompletionHandler() {
		this.dom.$startButton
			.prop("disabled", false)
			.text("Done")
			.off("click.peppolLookup")
			.on("click.peppolLookup", () => this.dom.$modal.modal("hide"));
	},

	/**
	 * Handle post-lookup actions
	 * @private
	 */
	handlePostLookupActions() {
		this.refreshDirectoryTable();
		this.triggerCustomEvent();
		this.handleSingleCustomerPageReload();
	},

	/**
	 * Refresh directory table if it exists
	 * @private
	 */
	refreshDirectoryTable() {
		try {
			// Try different table refresh methods
			if (
				typeof directoryTable !== "undefined" &&
				directoryTable &&
				directoryTable.ajax
			) {
				directoryTable.ajax.reload();
			} else {
				const $table = $(".table-peppol-directory");
				if ($table.length && $table.DataTable) {
					$table.DataTable().ajax.reload();
				}
			}
		} catch (error) {
			console.warn("Table refresh failed:", error);
		}
	},

	/**
	 * Trigger custom success event
	 * @private
	 */
	triggerCustomEvent() {
		$(document).trigger(this.CONSTANTS.EVENTS.LOOKUP_SUCCESS);
	},

	/**
	 * Handle page reload for single customer mode
	 * @private
	 */
	handleSingleCustomerPageReload() {
		if (
			this.state.singleCustomerId &&
			window.location.href.includes("clients/client/")
		) {
			setTimeout(() => {
				this.dom.$modal.modal("hide");
				location.reload();
			}, 2000);
		}
	},

	/**
	 * Render all customers needing multiple result selections
	 * @private
	 */
	renderAllMultipleSelections() {
		const html = this.generateMultipleSelectionsHTML();
		this.dom.$modal.find("#multiple-results-list").html(html);
		this.updateMultipleSelectionsAlert();
		this.setupSelectionEventHandlers();
	},

	/**
	 * Generate HTML for multiple selections interface
	 * @private
	 * @returns {jQuery} Generated elements
	 */
	generateMultipleSelectionsHTML() {
		const $container = $("<div>");

		this.state.pendingMultipleSelections.forEach((selection) => {
			const $customerSelection =
				this.generateCustomerSelectionHTML(selection);
			$container.append($customerSelection);
		});

		return $container.html();
	},

	/**
	 * Generate HTML for single customer selection
	 * @private
	 * @param {Object} selection - Selection data containing customer and results
	 * @returns {jQuery} Generated customer selection element
	 */
	generateCustomerSelectionHTML(selection) {
		const {customer, results} = selection;
		const customerId = customer.userid;
		const customerVat = customer.vat
			? customer.vat.replace(/\D/g, "")
			: null;

		const $container = $("<div>");

		// Add customer header
		$container.append(this.generateCustomerHeader(customer, customerId));

		// Add none option
		$container.append(this.generateNoneOption(customerId));

		// Add result options
		results.forEach((result, index) => {
			$container.append(
				this.generateResultOption(
					result,
					index,
					customerId,
					customerVat
				)
			);
		});

		return $container;
	},

	/**
	 * Generate customer header using template
	 * @private
	 * @param {Object} customer - Customer data
	 * @param {number} customerId - Customer ID
	 * @returns {jQuery} Header element
	 */
	generateCustomerHeader(customer, customerId) {
		const template = this.templates.customerHeader.cloneNode(true);
		const $template = $(template);

		const vatDisplay = customer.vat
			? ` <small style="color: #666;">(VAT: ${customer.vat})</small>`
			: "";

		$template.find(".customer-company").text(customer.company);
		$template.find(".customer-vat").html(vatDisplay);
		$template
			.find(".skip-customer-btn")
			.on("click", () => this.skipSingleCustomer(customerId));

		return $template;
	},

	/**
	 * Generate "none of these" option using template
	 * @private
	 * @param {number} customerId - Customer ID
	 * @returns {jQuery} None option element
	 */
	generateNoneOption(customerId) {
		const noneRadioId = `participant_${customerId}_none`;

		const template = this.templates.noneOption.cloneNode(true);
		const $template = $(template);

		$template.find(".none-radio").attr({
			id: noneRadioId,
			name: `selected_participant_${customerId}`,
			"data-customer-id": customerId,
		});

		$template.find(".none-radio-label").attr("for", noneRadioId);

		return $template;
	},

	/**
	 * Generate result option using template
	 * @private
	 * @param {Object} result - Participant result data
	 * @param {number} index - Result index
	 * @param {number} customerId - Customer ID
	 * @param {string|null} customerVat - Customer VAT number
	 * @returns {jQuery} Result option element
	 */
	generateResultOption(result, index, customerId, customerVat) {
		const isVatMatch = this.checkVATMatch(result.vat, customerVat);
		const vatBadge = isVatMatch
			? '<span class="label label-success" style="margin-left: 8px;">VAT Match</span>'
			: "";
		const radioId = `participant_${customerId}_${index}`;

		const template = this.templates.resultOption.cloneNode(true);
		const $template = $(template);

		$template.find(".result-radio").attr({
			id: radioId,
			name: `selected_participant_${customerId}`,
			value: index,
			"data-customer-id": customerId,
		});

		$template.find(".result-radio-label").attr("for", radioId);
		$template
			.find(".result-name")
			.text(result.name || result.company || window.peppolTranslations?.unknownCompany);
		$template.find(".result-vat-badge").html(vatBadge);
		$template
			.find(".result-details")
			.html(this.generateResultDetails(result));

		return $template;
	},

	/**
	 * Check if VAT numbers match
	 * @private
	 * @param {string|null} resultVat - Result VAT number
	 * @param {string|null} customerVat - Customer VAT number
	 * @returns {boolean} True if VAT numbers match
	 */
	checkVATMatch(resultVat, customerVat) {
		if (!customerVat || !resultVat) return false;
		const cleanResultVat = resultVat.replace(/\D/g, "");
		return cleanResultVat === customerVat;
	},

	/**
	 * Generate result details HTML
	 * @private
	 * @param {Object} result - Result data
	 * @returns {string} Details HTML
	 */
	generateResultDetails(result) {
		const details = [];

		details.push(`<strong>Scheme:</strong> ${result.scheme || "N/A"}`);
		details.push(`<strong>ID:</strong> ${result.identifier || "N/A"}`);

		if (result.vat) {
			details.push(`<strong>VAT:</strong> ${result.vat}`);
		}

		if (result.country) {
			details.push(`<strong>Country:</strong> ${result.country}`);
		}

		return details
			.map(
				(detail) => `<span style="margin-right: 15px;">${detail}</span>`
			)
			.join("");
	},

	/**
	 * Update alert message for multiple selections
	 * @private
	 */
	updateMultipleSelectionsAlert() {
		const totalPending = this.state.pendingMultipleSelections.length;
		const alertMsg =
			totalPending === 1
				? `Multiple participants found for <strong>${this.state.pendingMultipleSelections[0].customer.company}</strong>`
				: `Multiple participants found for <strong>${totalPending} customers</strong>`;

		$(".alert-warning").html(
			`<strong>${alertMsg}</strong> - ${window.peppolTranslations?.selectCorrectParticipant}`
		);
	},

	/**
	 * Setup event handlers for selection interface
	 * @private
	 */
	setupSelectionEventHandlers() {
		const $resultsContainer = this.dom.$modal.find(
			"#multiple-results-list"
		);

		// Remove previous handlers to prevent stacking
		$resultsContainer.off("change.peppolSelection");

		// Add delegated event handler
		$resultsContainer.on(
			"change.peppolSelection",
			'input[name^="selected_participant_"]',
			this.checkAllSelectionsComplete.bind(this)
		);
	},

	/**
	 * Check if selections are complete and update UI accordingly
	 * @public
	 */
	checkAllSelectionsComplete() {
		const totalCustomers = this.state.pendingMultipleSelections.length;
		const selectedCount = this.countSelectedCustomers();

		this.updateConfirmButton(selectedCount, totalCustomers);
	},

	/**
	 * Count customers with selections made
	 * @private
	 * @returns {number} Number of customers with selections
	 */
	countSelectedCustomers() {
		return this.state.pendingMultipleSelections.filter((selection) => {
			const customerId = selection.customer.userid;
			return (
				this.dom.$modal.find(
					`input[name="selected_participant_${customerId}"]:checked`
				).length > 0
			);
		}).length;
	},

	/**
	 * Update confirm button state and text
	 * @private
	 * @param {number} selectedCount - Number of selected customers
	 * @param {number} totalCustomers - Total number of customers
	 */
	updateConfirmButton(selectedCount, totalCustomers) {
		const $confirmBtn = this.dom.$modal.find("#confirm-selection-btn");

		$confirmBtn.prop("disabled", selectedCount === 0);

		const buttonText = this.getConfirmButtonText(
			selectedCount,
			totalCustomers
		);
		$confirmBtn.text(buttonText);
	},

	/**
	 * Get appropriate confirm button text
	 * @private
	 * @param {number} selectedCount - Number of selected customers
	 * @param {number} totalCustomers - Total number of customers
	 * @returns {string} Button text
	 */
	getConfirmButtonText(selectedCount, totalCustomers) {
		if (selectedCount === 0) {
			return window.peppolTranslations?.makeSelectionsContinue;
		} else if (selectedCount === totalCustomers) {
			return `Confirm All Selections (${selectedCount}/${totalCustomers})`;
		} else {
			return `Confirm Selected (${selectedCount}/${totalCustomers})`;
		}
	},

	/**
	 * Skip a single customer from multiple results queue
	 * @public
	 * @param {number} customerId - Customer ID to skip
	 */
	skipSingleCustomer(customerId) {
		const customerIndex = this.findCustomerInQueue(customerId);

		if (customerIndex === -1) {
			console.error(
				"Customer not found in pending selections:",
				customerId
			);
			return;
		}

		const skippedCustomer =
			this.state.pendingMultipleSelections[customerIndex];
		this.logCustomerSkipped(skippedCustomer.customer);
		this.removeCustomerFromQueue(customerIndex);
		this.handleQueueUpdate();
	},

	/**
	 * Find customer index in pending queue
	 * @private
	 * @param {number} customerId - Customer ID to find
	 * @returns {number} Customer index or -1 if not found
	 */
	findCustomerInQueue(customerId) {
		return this.state.pendingMultipleSelections.findIndex(
			(selection) => selection.customer.userid == customerId
		);
	},

	/**
	 * Log that customer was skipped
	 * @private
	 * @param {Object} customer - Customer data
	 */
	logCustomerSkipped(customer) {
		this.logProgressMessage(
			"fa-info text-warning",
			`${customer.company}: Skipped by user`
		);
	},

	/**
	 * Remove customer from queue
	 * @private
	 * @param {number} index - Index to remove
	 */
	removeCustomerFromQueue(index) {
		this.state.pendingMultipleSelections.splice(index, 1);
	},

	/**
	 * Handle queue update after removal
	 * @private
	 */
	handleQueueUpdate() {
		this.renderAllMultipleSelections();

		if (this.state.pendingMultipleSelections.length === 0) {
			this.continueAfterMultipleResults();
		}
	},

	/**
	 * Skip all multiple selections
	 * @public
	 */
	skipMultipleSelection() {
		this.logAllCustomersSkipped();
		this.continueAfterMultipleResults();
	},

	/**
	 * Log all customers as skipped
	 * @private
	 */
	logAllCustomersSkipped() {
		this.state.pendingMultipleSelections.forEach((selection) => {
			this.logCustomerSkipped(selection.customer);
		});
	},

	/**
	 * Confirm multiple selections and send to server
	 * @public
	 */
	confirmMultipleSelection() {
		try {
			const selectionsToSend = this.collectValidSelections();

			if (selectionsToSend.length === 0) {
				this.showErrorMessage(
					window.peppolTranslations?.makeSelection
				);
				return;
			}

			this.logUnselectedCustomers();
			this.sendSelectionsToServer(selectionsToSend);
		} catch (error) {
			console.error("Multiple selection confirmation failed:", error);
			this.showErrorMessage(
				window.peppolTranslations?.processSelectionsFailed
			);
		}
	},

	/**
	 * Collect valid selections from UI
	 * @private
	 * @returns {Array} Array of selection objects
	 */
	collectValidSelections() {
		const selectionsToSend = [];
		const processedCustomers = new Set();

		this.state.pendingMultipleSelections.forEach((selection) => {
			const customerId = selection.customer.userid;

			// Prevent duplicates
			if (processedCustomers.has(customerId)) {
				console.warn("Duplicate customer found:", customerId);
				return;
			}
			processedCustomers.add(customerId);

			const selectedRadio = this.getSelectedRadioForCustomer(customerId);
			if (selectedRadio.length > 0) {
				const selectionData = this.createSelectionData(
					selectedRadio,
					selection,
					customerId
				);
				if (selectionData) {
					selectionsToSend.push(selectionData);
				}
			}
		});

		return selectionsToSend;
	},

	/**
	 * Get selected radio button for customer
	 * @private
	 * @param {number} customerId - Customer ID
	 * @returns {jQuery} Selected radio button element
	 */
	getSelectedRadioForCustomer(customerId) {
		const selectedRadios = this.dom.$modal.find(
			`input[name="selected_participant_${customerId}"]:checked`
		);

		if (selectedRadios.length > 1) {
			console.warn(
				"Multiple radio buttons selected for customer:",
				customerId
			);
			return selectedRadios.first();
		}

		return selectedRadios;
	},

	/**
	 * Create selection data object
	 * @private
	 * @param {jQuery} selectedRadio - Selected radio button
	 * @param {Object} selection - Selection data
	 * @param {number} customerId - Customer ID
	 * @returns {Object|null} Selection data object
	 */
	createSelectionData(selectedRadio, selection, customerId) {
		const selectedValue = selectedRadio.val();

		if (selectedValue === "none") {
			return {
				customer_id: customerId,
				type: "none",
			};
		}

		const resultIndex = parseInt(selectedValue);
		const selectedResult = selection.results[resultIndex];

		if (!selectedResult) {
			console.warn(
				"Selected result not found for customer:",
				customerId,
				"index:",
				resultIndex
			);
			return null;
		}

		return {
			customer_id: customerId,
			type: "participant",
			scheme: selectedResult.scheme,
			identifier: selectedResult.identifier,
			name: selectedResult.name || selectedResult.company,
			country: selectedResult.country,
		};
	},

	/**
	 * Log unselected customers as skipped
	 * @private
	 */
	logUnselectedCustomers() {
		this.state.pendingMultipleSelections.forEach((selection) => {
			const customerId = selection.customer.userid;
			const selectedRadio = this.dom.$modal.find(
				`input[name="selected_participant_${customerId}"]:checked`
			);

			if (selectedRadio.length === 0) {
				this.logCustomerSkipped(selection.customer);
			}
		});
	},

	/**
	 * Send selections to server
	 * @private
	 * @param {Array} selectionsToSend - Array of selection data
	 */
	sendSelectionsToServer(selectionsToSend) {
		$.ajax({
			url: `${admin_url}peppol/ajax_apply_batch_selections`,
			type: "POST",
			data: {selections: selectionsToSend},
			dataType: "json",
			timeout: 30000,
		})
			.done((response) => this.handleSelectionResponse(response))
			.fail((xhr, status, error) =>
				this.handleSelectionError(xhr, status, error)
			);
	},

	/**
	 * Handle selection response from server
	 * @private
	 * @param {Object} response - Server response
	 */
	handleSelectionResponse(response) {
		if (!response.success) {
			this.showErrorMessage(
				`Failed to apply selections: ${
					response.message || window.peppolTranslations?.unknownError
				}`
			);
			return;
		}

		this.processSelectionResults(response.results || []);
		this.continueAfterMultipleResults();
	},

	/**
	 * Handle selection request error
	 * @private
	 * @param {Object} xhr - XMLHttpRequest object
	 * @param {string} status - Error status
	 * @param {string} error - Error message
	 */
	handleSelectionError(xhr, status, error) {
		console.error("Selection request failed:", {xhr, status, error});

		let errorMessage = window.peppolTranslations?.requestFailed;
		if (status === "timeout") {
			errorMessage = "Request timed out. Please try again.";
		}

		this.showErrorMessage(errorMessage);
	},

	/**
	 * Process selection results from server
	 * @private
	 * @param {Array} results - Selection results
	 */
	processSelectionResults(results) {
		results.forEach((result) => {
			const icon = result.success
				? "fa-check text-success"
				: "fa-times text-danger";
			this.logProcessingResult(icon, result.company, result.message);

			if (result.success) {
				this.results.successful++;
			} else {
				this.results.failed++;
			}
		});
	},

	/**
	 * Continue after multiple results handling
	 * @public
	 */
	continueAfterMultipleResults() {
		this.clearMultipleResultsState();
		this.hideMultipleResultsUI();
		this.showFinalResults();
	},

	/**
	 * Clear multiple results state
	 * @private
	 */
	clearMultipleResultsState() {
		this.state.pendingMultipleSelections = [];
	},

	/**
	 * Hide multiple results UI elements
	 * @private
	 */
	hideMultipleResultsUI() {
		this.dom.$modal.find("#peppol-multiple-results").hide();
		this.dom.$modal.find("#confirm-selection-btn").prop("disabled", true);
		this.dom.$modal.find("#multiple-results-list").empty();
	},

	/**
	 * Handle lookup mode change
	 * @private
	 * @param {Event} event - Change event
	 */
	handleLookupModeChange(event) {
		const mode = $(event.target).val();
		const $clientSelection = $("#client-selection");

		if (mode === "selected") {
			$clientSelection.show();
		} else {
			$clientSelection.hide();
		}
	},

	/**
	 * Reset modal to initial state
	 * @public
	 */
	resetModal() {
		try {
			this.resetState();
			this.resetFormElements();
			this.resetProgressElements();
			this.resetResultElements();
			this.resetUIVisibility();
			this.resetStartButton();
		} catch (error) {
			console.error("Modal reset failed:", error);
		}
	},

	/**
	 * Reset internal state
	 * @private
	 */
	resetState() {
		this.state.isProcessing = false;
		this.state.currentProgress = 0;
		this.state.totalProcessed = 0;
		this.state.singleCustomerId = null;
		this.state.autoStartSingleLookup = false;
		this.state.pendingMultipleSelections = [];
		this.results = {successful: 0, failed: 0, multipleResults: 0};
	},

	/**
	 * Reset form elements
	 * @private
	 */
	resetFormElements() {
		$('input[name="lookup_mode"][value="all"]').prop("checked", true);
		$('input[name="lookup_mode"][value="selected"]').prop("checked", false);
		$('input[name="lookup_mode"]').prop("disabled", false);
		$("#client-selection").hide();
		$("#peppol-customer-selection .form-group:first-child").show();

		// Reset selectpicker
		if (this.dom.$clientSelect.hasClass("selectpicker-initialized")) {
			this.dom.$clientSelect.empty();
			if (this.dom.$clientSelect.data("selectpicker")) {
				this.dom.$clientSelect.selectpicker("refresh");
			}
		}
	},

	/**
	 * Reset progress elements
	 * @private
	 */
	resetProgressElements() {
		this.dom.$progressBar.css("width", "0%").text("0 / 0");
		this.dom.$progressDetails.empty();
	},

	/**
	 * Reset result elements
	 * @private
	 */
	resetResultElements() {
		this.dom.$modal.find("#successful-count").text("0");
		this.dom.$modal.find("#failed-count").text("0");
		this.dom.$modal.find("#multiple-count").text("0");
		this.dom.$modal.find("#detailed-results").empty();
		this.dom.$modal.find("#multiple-results-list").empty();
		this.dom.$modal
			.find("#confirm-selection-btn")
			.prop("disabled", true)
			.text(window.peppolTranslations?.confirmAllSelections);
		$('input[name^="selected_participant_"]').prop("checked", false);
	},

	/**
	 * Reset UI visibility
	 * @private
	 */
	resetUIVisibility() {
		this.showCustomerSelection();
		this.hideProgress();
		this.dom.$modal.find("#peppol-multiple-results").hide();
		this.dom.$modal.find("#peppol-results").hide();
	},

	/**
	 * Reset start button
	 * @private
	 */
	resetStartButton() {
		const originalButtonText =
			this.dom.$startButton.data("original-text") || window.peppolTranslations?.startAutoLookup;

		this.dom.$startButton
			.prop("disabled", false)
			.html(`<i class="fa fa-play"></i> ${originalButtonText}`)
			.off("click.peppolLookup")
			.on("click.peppolLookup", this.startLookup.bind(this));
	},

	// UI helper methods
	/**
	 * Show customer selection section
	 * @private
	 */
	showCustomerSelection() {
		this.dom.$customerSelection.show();
	},

	/**
	 * Hide customer selection section
	 * @private
	 */
	hideCustomerSelection() {
		this.dom.$customerSelection.hide();
	},

	/**
	 * Show progress section
	 * @private
	 */
	showProgress() {
		this.dom.$modal.find("#peppol-progress").show();
	},

	/**
	 * Hide progress section
	 * @private
	 */
	hideProgress() {
		this.dom.$modal.find("#peppol-progress").hide();
	},

	/**
	 * Enable start button
	 * @private
	 */
	enableStartButton() {
		this.dom.$startButton.prop("disabled", false);
	},

	/**
	 * Disable start button
	 * @private
	 */
	disableStartButton() {
		this.dom.$startButton.prop("disabled", true);
	},

	/**
	 * Clear single customer state
	 * @private
	 */
	clearSingleCustomerState() {
		this.state.singleCustomerId = null;
		this.state.autoStartSingleLookup = false;
	},

	/**
	 * Show error message to user
	 * @private
	 * @param {string} message - Error message to display
	 */
	showErrorMessage(message) {
		if (typeof alert === "function") {
			alert(message);
		} else {
			console.error("Error:", message);
		}
	},
};

// Initialize when document is ready
$(document).ready(() => {
	PeppolLookup.init();
});
