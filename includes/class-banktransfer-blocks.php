<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_BankTransfer_Blocks extends AbstractPaymentMethodType {
    protected $name = 'hexakode_banktransfer';
    protected $gateway;

    public function initialize() {
        $gateways = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $gateways[$this->name] ?? null;
    }

    public function is_active() {
        return $this->gateway && $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_enqueue_script(
            'wc-banktransfer-block',
            plugins_url('block/banktransfer-block.js', __DIR__),
            ['wp-element', 'wc-blocks-registry', 'wc-settings', 'wp-i18n'],
            null,
            true
        );

        $settings = get_option('woocommerce_hexakode_banktransfer_settings', []);
        wp_add_inline_script(
            'wc-banktransfer-block',
            'window.wc = window.wc || {}; window.wc.wcSettings = window.wc.wcSettings || {}; window.wc.wcSettings["hexakode_banktransfer_data"] = ' . wp_json_encode([
                'title' => $settings['title'] ?? 'Bank Transfer',
                'description' => $settings['description'] ?? 'Pay by bank transfer and upload your receipt after checkout.',
                'ariaLabel' => $settings['title'] ?? 'Bank Transfer Payment',
            ]) . ';',
            'before'
        );

        return ['wc-banktransfer-block'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->get_option('title'),
            'description' => $this->gateway->get_option('description'),
            'ariaLabel' => $this->gateway->get_option('title'),
            'supports' => ['products', 'subscriptions', 'default', 'virtual'],
        ];
    }

    public function enqueue_payment_method_script() {
    wp_enqueue_script(
        'wc-banktransfer-block',
        plugins_url('block/banktransfer-block.js', __DIR__),
        ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
        null,
        true
    );
}
}
