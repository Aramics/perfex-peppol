<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h5 class="tw-text-lg tw-font-semibold tw-mb-4">
    <i class="fa fa-file-text"></i> <?php echo _l('peppol_document_preview'); ?>
</h5>

<?php if (!empty($ubl_data)) : ?>
<div class="row">
    <div class="col-md-6 col-md-offset-6">
        <div class="table-responsive">
            <table class="table table-sm">
                <caption><?php echo _l('peppol_document_details'); ?></caption>
                <?php if (!empty($ubl_data['document_number'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_number'); ?></td>
                    <td><?php echo e($ubl_data['document_number']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['issue_date'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_date'); ?></td>
                    <td><?php echo e($ubl_data['issue_date']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['due_date'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('due_date'); ?></td>
                    <td><?php echo e($ubl_data['due_date']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['currency_code'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('currency'); ?></td>
                    <td><?php echo e($ubl_data['currency_code']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-responsive">
            <table class="table table-sm">
                <caption><?php echo _l('peppol_seller_information'); ?></caption>
                <?php if (!empty($ubl_data['seller']['name'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('name'); ?></td>
                    <td><?php echo e($ubl_data['seller']['name']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['seller']['identifier']) && !empty($ubl_data['seller']['scheme'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_identifier'); ?></td>
                    <td><code><?php echo e($ubl_data['seller']['scheme'] . ':' . $ubl_data['seller']['identifier']); ?></code>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['seller']['vat_number'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('client_vat_number'); ?></td>
                    <td><?php echo e($ubl_data['seller']['vat_number']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['seller']['address'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('client_address'); ?></td>
                    <td><?php echo nl2br(e($ubl_data['seller']['address'])); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['seller']['country'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('country'); ?></td>
                    <td><?php echo e($ubl_data['seller']['country']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Buyer/Receiver Information -->
    <?php if (!empty($ubl_data['buyer'])) : ?>
    <div class="col-md-6">
        <div class="table-responsive">
            <table class="table table-sm">
                <caption><?php echo _l('peppol_buyer_information'); ?></caption>
                <?php if (!empty($ubl_data['buyer']['name'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('name'); ?></td>
                    <td><?php echo e($ubl_data['buyer']['name']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['buyer']['identifier']) && !empty($ubl_data['buyer']['scheme'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('peppol_identifier'); ?></td>
                    <td><code><?php echo e($ubl_data['buyer']['scheme'] . ':' . $ubl_data['buyer']['identifier']); ?></code>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['buyer']['vat_number'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('client_vat_number'); ?></td>
                    <td><?php echo e($ubl_data['buyer']['vat_number']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['buyer']['address'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('client_address'); ?></td>
                    <td><?php echo nl2br(e($ubl_data['buyer']['address'])); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($ubl_data['buyer']['country_code'])) : ?>
                <tr>
                    <td class="tw-font-medium"><?php echo _l('country'); ?></td>
                    <td><?php echo e($ubl_data['buyer']['country_code']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($ubl_data['items']) && is_array($ubl_data['items'])) : ?>
<?php
        // Abstract currency to avoid repeated calls
        $display_currency = $ubl_data['currency_code'] ?? get_base_currency()->name;
        ?>
<div class="row tw-mt-4">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-striped">
                <caption>
                    <?php echo _l('peppol_line_items'); ?>
                </caption>
                <thead>
                    <tr>
                        <th><?php echo _l('peppol_description'); ?></th>
                        <th class="text-center"><?php echo _l('peppol_quantity'); ?></th>
                        <th class="text-right"><?php echo _l('peppol_unit_price'); ?></th>
                        <th class="text-right"><?php echo _l('peppol_amount'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ubl_data['items'] as $item) : ?>
                    <tr>
                        <td>
                            <strong><?php echo e($item['description'] ?? $item['long_description'] ?? '-'); ?></strong>
                            <?php if (!empty($item['long_description']) && $item['description'] !== $item['long_description']) : ?>
                            <br><small class="text-muted"><?php echo e($item['long_description']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($item['taxname']) && is_array($item['taxname'])) : ?>
                            <br><small class="text-info">Tax:
                                <?php
                                                $formatted_taxes = [];
                                                foreach ($item['taxname'] as $tax) {
                                                    if (strpos($tax, '|') !== false) {
                                                        list($tax_type, $tax_rate) = explode('|', $tax, 2);
                                                        $formatted_taxes[] = e($tax_type . ' ' . $tax_rate . '%');
                                                    } else {
                                                        $formatted_taxes[] = e($tax);
                                                    }
                                                }
                                                echo implode(', ', $formatted_taxes);
                                                ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php echo e($item['qty'] ?? '-'); ?>
                            <?php if (!empty($item['unit'])) : ?>
                            <small class="text-muted">(<?php echo e($item['unit']); ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php echo app_format_money($item['rate'] ?? 0, $display_currency); ?>
                        </td>
                        <td class="text-right">
                            <?php
                                        $line_amount = ($item['rate'] ?? 0) * ($item['qty'] ?? 0);
                                        echo app_format_money($line_amount, $display_currency);
                                        ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <!-- Totals Section -->
                <?php if (!empty($ubl_data['totals'])) : ?>
                <tbody class="tw-border-t-2">
                    <?php if (isset($ubl_data['totals']['subtotal'])) : ?>
                    <tr>
                        <td colspan="3" class="text-right tw-font-medium"><?php echo _l('peppol_subtotal'); ?></td>
                        <td class="text-right tw-font-medium">
                            <?php echo app_format_money($ubl_data['totals']['subtotal'], $display_currency); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (isset($ubl_data['totals']['tax_amount'])) : ?>
                    <tr>
                        <td colspan="3" class="text-right tw-font-medium"><?php echo _l('peppol_tax'); ?></td>
                        <td class="text-right tw-font-medium">
                            <?php echo app_format_money($ubl_data['totals']['tax_amount'], $display_currency); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (isset($ubl_data['totals']['total'])) : ?>
                    <tr class="tw-bg-neutral-100">
                        <td colspan="3" class="text-right tw-font-bold"><?php echo _l('peppol_total'); ?></td>
                        <td class="text-right tw-font-bold">
                            <?php echo app_format_money($ubl_data['totals']['total'], $display_currency); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($ubl_data['notes'])) : ?>
<div class="row tw-mt-4">
    <div class="col-md-12">
        <h6 class="tw-font-semibold"><?php echo _l('peppol_notes'); ?></h6>
        <div class="tw-bg-neutral-50 tw-border tw-rounded tw-p-3">
            <?php echo nl2br(e($ubl_data['notes'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else : ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <?php echo _l('peppol_no_preview_available'); ?>
</div>
<?php endif; ?>