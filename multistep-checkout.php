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

        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Allow order creation without payment methods
        add_filter('woocommerce_order_needs_payment', '__return_false');

        // Bypass WooCommerce payment validation on checkout
        add_filter('woocommerce_checkout_process', [$this, 'bypass_payment_validation'], 99);

        // Clear WooCommerce notices after processing
        add_action('woocommerce_checkout_process', [$this, 'debug_checkout_process']);

        // Set default payment method and redirect after order creation
        add_action('woocommerce_checkout_order_created', [$this, 'set_default_payment_method_and_redirect'], 10, 1);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        // Debugging checkout
        add_action('woocommerce_checkout_process', [$this, 'custom_checkout_debug']);
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
     * Bypass WooCommerce payment validation on checkout
     */
    public function bypass_payment_validation() {
        if (isset($_POST['payment_method'])) {
            unset($_POST['payment_method']);
        }
        error_log('Bypassed payment validation on checkout.');
    }

    /**
     * Debug checkout process
     */
    public function debug_checkout_process() {
        error_log('Checkout Data: ' . print_r($_POST, true));
    }

    /**
     * Set default payment method and redirect to payment page
     *
     * @param WC_Order $order
     */
    public function set_default_payment_method_and_redirect($order) {
        // Set default payment method
        update_post_meta($order->get_id(), '_payment_method', '');
        update_post_meta($order->get_id(), '_payment_method_title', '');
        error_log('Default payment method cleared for Order ID: ' . $order->get_id());

        // Redirect to payment page
        $pay_url = $order->get_checkout_payment_url();
        error_log('Redirecting to payment page: ' . $pay_url);
        wp_redirect($pay_url);
        exit;
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

    /**
     * Debug WooCommerce session and cart
     */
    public function custom_checkout_debug() {
        if (is_checkout()) {
            error_log('Debug Checkout Process Start');
            error_log('Session Data: ' . print_r(WC()->session, true));
            error_log('Cart Data: ' . print_r(WC()->cart, true));
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
