<?php
/**
 * Plugin Name: Bank Transfers by Hexakode Agency
 * Plugin URI: https://hexakodeagency.com
 * Author Name: Kalem Mentore
 * Author URI: https://hexakodeagency.com
 * Description: Allows customers to pay via bank transfer and upload receipt. Supports WooCommerce blocks.
 * Version: 1.0
 * Author: Hexakode Agency
 * Text Domain: hexakode-banktransfer
 */

if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function () {
    if (class_exists('WC_Payment_Gateway')) {
        require_once __DIR__ . '/includes/class-wc-gateway-banktransfer-hexakode.php';
        require_once __DIR__ . '/includes/class-banktransfer-blocks.php';
    }
}, 11);

add_action('before_woocommerce_init', function() {
    if ( class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil') ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

add_filter('woocommerce_payment_gateways', function ($methods) {
    $methods[] = 'WC_Gateway_BankTransfer_Hexakode';
    return $methods;
});

add_action('woocommerce_blocks_loaded', function () {
    if (class_exists('\\Automattic\\WooCommerce\\Blocks\\Payments\\PaymentMethodRegistry')) {
        add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
            $registry->register(new WC_BankTransfer_Blocks());
        });
    }
});

add_action('woocommerce_before_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || $order->get_payment_method() !== 'hexakode_banktransfer') return;

    if (get_transient('hexakode_receipt_notice_' . $order_id)) {
        echo '<div class="woocommerce-message" role="alert" style="margin-bottom: 20px;">Receipt uploaded successfully.</div>';
        delete_transient('hexakode_receipt_notice_' . $order_id);
    }

    $bank_rows = get_option('hexakode_banktransfer_accounts', []);
    if (empty($bank_rows)) return;

    echo '<h2>Bank Transfer Instructions</h2>';
    echo '<div style="overflow-x:auto;">';
    echo '<table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">';
    echo '<thead>
        <tr style="background-color: #f2f2f2;">
            <th style="border: 1px solid #ccc; padding: 8px;">Bank Name</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Name on Account</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Account Number</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Transit Number</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Branch</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Type</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Class</th>
        </tr>
    </thead><tbody>';

    foreach ((array) $bank_rows as $row) {
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['bank_name'] ?? '') . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['account_name'] ?? '') . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['account_number'] ?? '') . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['transit'] ?? '') . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['branch'] ?? '') . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['type'] ?? '') . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($row['category'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';

    echo '
<form method="post" enctype="multipart/form-data" style="margin-top: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; background-color: #f9f9f9; max-width: 500px;">
    <p style="margin-bottom: 10px;">
        <label for="bank_receipt" style="display: block; font-weight: bold; margin-bottom: 5px;">Upload Screenshot of Receipt:</label>
        <input
         accept=".png, .jpg, .jpeg, .heic, image/png, image/jpeg, image/heic"
        type="file" name="bank_receipt" id="bank_receipt" style="width: 100%; padding: 5px;" />
    </p>
    <button type="submit" style="background-color: #007cba; color: #fff; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;">
        Submit Receipt
    </button>
</form>';
});



add_action('template_redirect', function () {
    if (!is_wc_endpoint_url('order-received') || empty($_FILES['bank_receipt'])) return;

    $order_id = get_query_var('order-received');
    $order_id = absint($order_id);
    $order = wc_get_order($order_id);
    if (!$order || $order->get_payment_method() !== 'hexakode_banktransfer') return;

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $upload = wp_handle_upload($_FILES['bank_receipt'], ['test_form' => false]);
    if (!empty($upload['url'])) {
        $order->update_meta_data('_bank_receipt_url', $upload['url']);
        $order->save();

        wc_add_notice('Receipt uploaded successfully.', 'success');

        // Store a transient or flag for immediate display
        set_transient('hexakode_receipt_notice_' . $order_id, true, 60);
        
        // Refresh page to trigger rendering of the notice
        wp_safe_redirect(wc_get_endpoint_url('order-received', $order_id));
        exit;
    }
});



add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
    $receipt_url = $order->get_meta('_bank_receipt_url');
    if ($receipt_url) {
        echo '<p><strong>Bank Transfer Receipt:</strong> <a href="' . esc_url($receipt_url) . '" target="_blank">View Receipt</a></p>';
    }
});