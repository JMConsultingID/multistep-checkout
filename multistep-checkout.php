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
        // Hook into WooCommerce checkout process to bypass payment validation
        add_action('woocommerce_checkout_process', [$this, 'bypass_payment_validation']);

        // Redirect to the order-pay page after order creation
        add_action('woocommerce_thankyou', [$this, 'redirect_to_order_pay'], 1);
    }

    /**
     * Bypass payment validation during checkout process
     */
    public function bypass_payment_validation() {
        // Disable default payment requirement (optional)
        add_filter('woocommerce_order_needs_payment', '__return_false');
    }

    /**
     * Redirect to the order-pay page after creating the order
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
}

// Initialize the plugin
new Multistep_Checkout();
