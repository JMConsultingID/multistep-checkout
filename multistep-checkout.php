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

        // Redirect to order-pay after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_to_order_pay'], 1);

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
     * Redirect to the order pay page after checkout
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        if (!$order_id) return;

        // Get the order
        $order = wc_get_order($order_id);

        // Set order status to 'Pending Payment'
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending', __('Awaiting payment', 'multistep-checkout'));
        }

        // Generate the order-pay URL
        $order_pay_url = $order->get_checkout_payment_url();

        // Redirect to the order-pay page
        wp_redirect($order_pay_url);
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
}

// Initialize the plugin
new Multistep_Checkout();
