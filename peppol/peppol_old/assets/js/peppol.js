/**
 * PEPPOL Module JavaScript
 */

$(document).ready(function () {
	// Test connection button
	$(document).on("click", ".peppol-test-connection", function (e) {
		e.preventDefault();

		var button = $(this);
		var provider = button.data("provider");
		var environment =
			button.data("environment") ||
			$('select[name="settings[peppol_environment]"]').val();
		var resultContainer = button.siblings(".peppol-connection-test");

		peppolShowLoading(button, "Testing...");
		resultContainer.html("");

		$.post(
			admin_url + "peppol/test_connection",
			{
				provider: provider,
				environment: environment,
			},
			function (response) {
				if (response.success) {
					resultContainer.html(
						'<div class="alert alert-success mtop10"><i class="fa fa-check"></i> ' +
							response.message +
							"</div>"
					);
					alert_float("success", "Connection test successful");
				} else {
					resultContainer.html(
						'<div class="alert alert-danger mtop10"><i class="fa fa-times"></i> ' +
							response.message +
							"</div>"
					);
					alert_float(
						"danger",
						"Connection test failed: " + response.message
					);
				}
			},
			"json"
		)
			.fail(function () {
				resultContainer.html(
					'<div class="alert alert-danger mtop10"><i class="fa fa-times"></i> Connection test failed</div>'
				);
				alert_float("danger", "Connection test failed");
			})
			.always(function () {
				peppolHideLoading(button);
			});
	});

	// Auto-refresh status on PEPPOL invoices page
	if ($("#peppol-invoices-table").length) {
		setInterval(function () {
			$("#peppol-invoices-table").DataTable().ajax.reload(null, false);
		}, 30000); // Refresh every 30 seconds
	}

	// Provider configuration data (will be set by the settings page)
	window.peppolProviders = window.peppolProviders || {};

	// Update webhook information dynamically
	function updateWebhookInfo(provider) {
		var webhookContainer = $(".provider-webhook-section").first();

		if (
			window.peppolProviders[provider] &&
			window.peppolProviders[provider].webhooks
		) {
			var config = window.peppolProviders[provider];
			var webhookHtml = "<h5><strong>" + config.name + ":</strong></h5>";

			if (config.webhooks.endpoint) {
				webhookHtml += "<strong>Dedicated Webhook:</strong><br>";
				webhookHtml +=
					"<code>" +
					peppolGetSiteUrl() +
					config.webhooks.endpoint +
					"</code><br>";
			}

			if (config.webhooks.general) {
				webhookHtml += "<strong>General Webhook:</strong><br>";
				webhookHtml +=
					"<code>" +
					peppolGetSiteUrl() +
					config.webhooks.general +
					"</code><br>";
			}

			if (config.webhooks.signature_header) {
				webhookHtml +=
					'<small class="text-muted">Signature Header: ' +
					config.webhooks.signature_header +
					"</small><br>";
			}

			if (config.webhooks.supported_events) {
				webhookHtml +=
					'<small class="text-muted">Supported Events: ' +
					config.webhooks.supported_events.join(", ") +
					"</small>";
			}

			webhookHtml += "<br><br>";
			webhookContainer.html(webhookHtml);
		} else {
			webhookContainer.html(
				'<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> No webhook configuration available for this provider.</div>'
			);
		}
	}

	// Provider selection change
	$(document).on(
		"change",
		'select[name="settings[peppol_active_provider]"]',
		function () {
			var provider = $(this).val();
			$(".peppol-provider-settings").hide();
			$(
				'.peppol-provider-settings[data-provider="' + provider + '"]'
			).show();

			// Update webhook information
			updateWebhookInfo(provider);
		}
	);

	// Initialize provider settings visibility
	var activeProvider = $(
		'select[name="settings[peppol_active_provider]"]'
	).val();
	if (activeProvider) {
		$(".peppol-provider-settings").hide();
		$(
			'.peppol-provider-settings[data-provider="' + activeProvider + '"]'
		).show();
	}

	// Confirm send actions
	$(document).on("click", ".peppol-send-confirm", function (e) {
		if (
			!confirm("Are you sure you want to send this invoice via PEPPOL?")
		) {
			e.preventDefault();
			return false;
		}
	});

	// Confirm resend actions
	$(document).on("click", ".peppol-resend-confirm", function (e) {
		if (
			!confirm("Are you sure you want to resend this invoice via PEPPOL?")
		) {
			e.preventDefault();
			return false;
		}
	});

	// UBL content modal
	$(document).on("click", ".view-ubl-content", function (e) {
		e.preventDefault();

		var url = $(this).attr("href");

		$.get(url, function (data) {
			var modal = $("#ublContentModal");
			if (modal.length === 0) {
				modal = $(
					'<div id="ublContentModal" class="modal fade" tabindex="-1" role="dialog">' +
						'<div class="modal-dialog modal-lg" role="document">' +
						'<div class="modal-content">' +
						'<div class="modal-header">' +
						'<h4 class="modal-title">UBL Content</h4>' +
						'<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
						'<span aria-hidden="true">&times;</span>' +
						"</button>" +
						"</div>" +
						'<div class="modal-body">' +
						'<pre class="peppol-document-preview"></pre>' +
						"</div>" +
						'<div class="modal-footer">' +
						'<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
						"</div>" +
						"</div>" +
						"</div>" +
						"</div>"
				);
				$("body").append(modal);
			}

			modal.find(".peppol-document-preview").text(data);
			modal.modal("show");
		}).fail(function () {
			alert_float("danger", "Failed to load UBL content");
		});
	});

	// Environment change warning
	$(document).on(
		"change",
		'select[name="settings[peppol_environment]"]',
		function () {
			var environment = $(this).val();
			var warningText = "";

			if (environment === "live") {
				warningText =
					"Warning: You are switching to the live environment. Make sure all settings are correct before sending real invoices.";
			} else {
				warningText =
					"You are switching to the sandbox environment. This is safe for testing.";
			}

			if (warningText) {
				alert_float("info", warningText);
			}
		}
	);

	// Form validation for PEPPOL settings
	$("#settings-form").on("submit", function (e) {
		var errors = [];

		// Validate PEPPOL identifier format
		var identifier = $(
			'input[name="settings[peppol_company_identifier]"]'
		).val();
		if (identifier && !/^[0-9A-Za-z]+$/.test(identifier)) {
			errors.push(
				"PEPPOL identifier must contain only alphanumeric characters."
			);
		}

		// Validate PEPPOL scheme format
		var scheme = $(
			'input[name="settings[peppol_company_scheme]"]'
		).val();
		if (scheme && !/^[0-9]{4}$/.test(scheme)) {
			errors.push(
				"PEPPOL scheme must be exactly 4 digits."
			);
		}

		// Validate required fields for active provider
		var activeProvider = $(
			'select[name="settings[peppol_active_provider]"]'
		).val();
		if (activeProvider) {
			var requiredFields = {
				ademico: [
					"peppol_ademico_oauth2_client_identifier",
					"peppol_ademico_oauth2_client_secret",
				],
				unit4: ["peppol_unit4_username", "peppol_unit4_password"],
				recommand: [
					"peppol_recommand_api_key",
					"peppol_recommand_company_id",
				],
			};

			if (requiredFields[activeProvider]) {
				requiredFields[activeProvider].forEach(function (field) {
					var value = $(
						'input[name="settings[' + field + ']"]'
					).val();
					if (!value) {
						errors.push(
							'Field "' +
								field +
								'" is required for the selected provider.'
						);
					}
				});
			}
		}

		if (errors.length > 0) {
			e.preventDefault();
			alert_float("danger", errors.join("<br>"));
			return false;
		}
	});
});

/**
 * Show loading state for buttons
 */
function peppolShowLoading(button, text) {
	button.prop("disabled", true);
	button.data("original-text", button.html());
	button.html(
		'<i class="fa fa-spinner fa-spin"></i> ' + (text || "Processing...")
	);
}

/**
 * Hide loading state for buttons
 */
function peppolHideLoading(button) {
	button.prop("disabled", false);
	var originalText = button.data("original-text");
	if (originalText) {
		button.html(originalText);
	}
}

/**
 * Format PEPPOL status for display
 */
function formatPeppolStatus(status) {
	var statusMap = {
		pending: "Pending",
		queued: "Queued",
		sending: "Sending",
		sent: "Sent",
		delivered: "Delivered",
		failed: "Failed",
		received: "Received",
		processed: "Processed",
	};

	return statusMap[status] || status;
}

/**
 * Get PEPPOL status badge HTML
 */
function getPeppolStatusBadge(status) {
	var badgeClass = "label-default";

	switch (status) {
		case "sent":
		case "delivered":
		case "processed":
			badgeClass = "label-success";
			break;
		case "failed":
			badgeClass = "label-danger";
			break;
		case "pending":
		case "queued":
		case "received":
			badgeClass = "label-warning";
			break;
		case "sending":
			badgeClass = "label-info";
			break;
	}

	return (
		'<span class="label ' +
		badgeClass +
		'">' +
		formatPeppolStatus(status) +
		"</span>"
	);
}

/**
 * Get site URL for webhook display
 */
function peppolGetSiteUrl() {
	return window.location.protocol + "//" + window.location.host + "/";
}

/**
 * Set provider configuration data (called from settings page)
 */
function setPeppolProviders(providers) {
	window.peppolProviders = providers;
}
