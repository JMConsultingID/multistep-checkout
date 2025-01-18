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
        // Disable payment options on checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Force order status to Pending after checkout
        add_action('woocommerce_checkout_create_order', [$this, 'set_order_to_pending'], 10, 2);

        // Prevent automatic status change to Processing
        //add_filter('woocommerce_payment_complete_order_status', [$this, 'prevent_status_change'], 10, 3);

        // Redirect to order-pay page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_to_order_pay'], 10);
    }

    /**
     * Force order status to Pending after checkout
     *
     * @param WC_Order $order
     * @param array $data
     */
    public function set_order_to_pending($order, $data) {
        $order->set_status('pending'); // Ensure the order is set to pending
        $order->add_order_note(__('Order Created', 'multistep-checkout'));
        error_log('Order status set to pending for Order ID: ' . $order->get_id());
    }

    /**
     * Prevent automatic status change to Processing
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function prevent_status_change($status, $order_id, $order) {
        error_log('Prevented status change for Order ID: ' . $order_id . ' - Status remains pending.');
        $order->add_order_note(__('Order Created', 'multistep-checkout'));
        return 'pending'; // Ensure status stays pending
    }

    /**
     * Redirect to order-pay page after checkout
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_status() === 'pending') {
            $redirect_url = $order->get_checkout_payment_url();
            $order->add_order_note(__('Redirecting to order-pay page', 'multistep-checkout'));
            error_log('Redirecting to order-pay page for Order ID: ' . $order_id . ' - URL: ' . $redirect_url);

            // Redirect to order-pay page
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
