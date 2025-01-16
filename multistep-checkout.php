<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Multistep_Checkout {

    public function __construct() {
        // Hook into WooCommerce checkout fields to modify them
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Remove payment fields
        add_action('woocommerce_checkout_fields', [$this, 'remove_payment_fields']);

        // Hook into the checkout process to modify order creation
        add_action('woocommerce_checkout_create_order', [$this, 'set_order_payment_method']);
        add_action('woocommerce_checkout_order_processed', [$this, 'handle_order_redirect'], 10, 3);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);
    }

    /**
     * Customize WooCommerce checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function customize_checkout_fields($fields) {
        // Remove shipping fields
        unset($fields['shipping']);

        // Optionally remove some billing fields
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);

        return $fields;
    }

    /**
     * Remove payment fields from checkout
     *
     * @param array $fields
     */
    public function remove_payment_fields($fields) {
        unset($fields['payment']);
    }

    /**
     * Set a temporary payment method for the order
     *
     * @param WC_Order $order
     */
    public function set_order_payment_method($order) {
        $order->set_payment_method('');
        $order->set_payment_method_title('');
        $order->set_status('pending', 'Order created via multi-step checkout.');
    }

    /**
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function handle_order_redirect($order_id, $posted_data, $order) {
        if (!$order_id) {
            return;
        }

        // Ensure we have a valid order object
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Clear session and cart
        WC()->session->set('order_awaiting_payment', $order_id);
        WC()->cart->empty_cart();

        // Get the pay page URL
        $pay_url = $order->get_checkout_payment_url(true);

        // Redirect to payment page
        if (!empty($pay_url)) {
            wp_redirect($pay_url);
            exit;
        }
    }

    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        // Example validation: Ensure first name is filled in
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('Please fill in your billing first name.', 'multistep-checkout'), 'error');
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
