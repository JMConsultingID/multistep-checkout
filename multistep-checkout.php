<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.2
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Multistep_Checkout {

    public function __construct() {
        // Customize checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Allow order creation without payment methods
        add_filter('woocommerce_order_needs_payment', [$this, 'force_dummy_payment'], 10, 2);

        // Redirect to payment page after order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'handle_order_redirect'], 10, 2);

        // Validate checkout fields
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);
    }

    /**
     * Customize WooCommerce checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function customize_checkout_fields($fields) {
        unset($fields['shipping']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        return $fields;
    }

    /**
     * Force a dummy payment method for orders
     *
     * @param bool $needs_payment
     * @param WC_Order $order
     * @return bool
     */
    public function force_dummy_payment($needs_payment, $order) {
        // Set dummy payment method directly on the order
        if (!$order->get_payment_method()) {
            $order->set_payment_method('bacs'); // Use 'bacs' or another valid payment method
            $order->add_order_note(__('Temporary payment method applied automatically.', 'multistep-checkout'));
        }
        return false; // Bypass payment requirement
    }

    /**
     * Redirect to the payment page after order creation
     *
     * @param int $order_id
     * @param array $posted_data
     */
    public function handle_order_redirect($order_id, $posted_data) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Set order status to pending payment
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending-payment', __('Order created and waiting for payment.', 'multistep-checkout'));
        }

        // Redirect to order-pay page
        WC()->session->set('order_awaiting_payment', $order_id);

        $pay_url = $order->get_checkout_payment_url(true);
        if ($pay_url) {
            wp_safe_redirect($pay_url);
            exit;
        }
    }

    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('Please provide your first name.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['billing_email'])) {
            wc_add_notice(__('Please provide your email address.', 'multistep-checkout'), 'error');
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
