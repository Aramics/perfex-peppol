<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Document Details Modal Template -->
<script type="text/template" id="document-details-template">
    <div class="row">
        <div class="col-md-6">
            <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_document_information'); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_document_type'); ?></td>
                        <td>
                            <span class="label {{typeClass}}">{{typeFormatted}}</span>
                        </td>
                    </tr>
                    {{#if hasLocalReference}}
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_document_number'); ?></td>
                        <td>
                            <code>{{localReferenceId}}</code><a href="{{localReferenceLink}}" target="_blank"><i class="fa fa-eye"></i></a>
                        </td>
                    </tr>
                    {{/if}}
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('client'); ?></td>
                        <td>{{clientName}}</td>
                    </tr>
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_status'); ?></td>
                        <td>{{statusBadge}}</td>
                    </tr>
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_provider'); ?></td>
                        <td>
                            <span class="tw-capitalize">{{provider}}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_transmission_details'); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_provider_document_id'); ?></td>
                        <td>{{providerDocumentId}}</td>
                    </tr>
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_sent_at'); ?></td>
                        <td>{{sentAt}}</td>
                    </tr>
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_received_at'); ?></td>
                        <td>{{receivedAt}}</td>
                    </tr>
                    <tr>
                        <td class="tw-font-medium"><?php echo _l('peppol_created_at'); ?></td>
                        <td>
                            <span class="tw-text-sm">{{createdAt}}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Attachments Section -->
    <div class="row tw-mt-6">
        <div class="col-md-12">
            <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_attachments'); ?></h5>
            {{#if hasAttachments}}
            <div class="tw-bg-neutral-50 tw-border tw-rounded tw-p-4">
                <div class="list-group">
                    {{attachmentsList}}
                </div>
            </div>
            {{/if}}
            {{#unless hasAttachments}}
            <p class="text-muted tw-text-sm"><?php echo _l('peppol_no_attachments_found'); ?></p>
            {{/unless}}
        </div>
    </div>

    {{#if hasMetadata}}
    <div class="row tw-mt-6">
        <div class="col-md-12">
            <h5 class="tw-text-lg tw-font-semibold tw-mb-4"><?php echo _l('peppol_metadata'); ?></h5>
            <div class="tw-bg-neutral-50 tw-border tw-rounded tw-p-4">
                <pre class="tw-text-sm tw-m-0" style="max-height: 300px; overflow-y: auto;">{{metadata}}</pre>
            </div>
        </div>
    </div>
    {{/if}}
</script>

<!-- Preloader Template -->
<script type="text/template" id="document-details-preloader-template">
    <div id="document-details-preloader" class="text-center" style="padding: 40px;">
        <div class="spinner-border text-primary" role="status">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <span class="sr-only"><?php echo _l('loading'); ?></span>
        </div>
        <div class="tw-mt-3">
            <p class="text-muted"><?php echo _l('peppol_loading_document_details'); ?>...</p>
        </div>
    </div>
</script>