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

        // Automatically set a dummy payment method for bypassing payment validation
        add_action('woocommerce_checkout_create_order', [$this, 'set_dummy_payment_method']);

        // Hook into the checkout process to modify order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'redirect_to_order_pay']);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        // Force a valid payment method for pending orders
        add_action('woocommerce_before_checkout_process', [$this, 'force_valid_payment_method']);
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
     * Automatically set a dummy payment method to bypass validation
     *
     * @param WC_Order $order
     */
    public function set_dummy_payment_method($order) {
        $order->set_payment_method('bacs'); // Use a valid payment method ID as a placeholder
    }

    /**
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        $order = wc_get_order($order_id);

        // Set order status to pending payment
        $order->update_status('pending-payment', __('Order created, waiting for payment.', 'multistep-checkout'));

        // Redirect to the order pay page
        wp_redirect($order->get_checkout_payment_url());
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
     * Force a valid payment method to avoid validation errors
     */
    public function force_valid_payment_method() {
        if (empty($_POST['payment_method'])) {
            $_POST['payment_method'] = 'bacs'; // Set a default payment method
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
