<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.3
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Multistep_Checkout {

    public function __construct() {
        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Set default payment method and update order during checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_default_payment_method']);

        // Ensure the order status is set to pending before processing payment
        add_action('woocommerce_payment_complete_order_status', [$this, 'force_pending_status'], 10, 3);

        // Debugging and error logging
        add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
        add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);

        // Log status changes
        add_action('woocommerce_order_status_changed', [$this, 'log_status_change'], 10, 3);
    }

    /**
     * Set default payment method for the order
     *
     * @param int $order_id
     */
    public function set_default_payment_method($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            error_log('Order not found for Order ID: ' . $order_id);
            return;
        }

        // Set default payment method
        if (!$order->get_payment_method()) {
            $order->set_payment_method('bacs'); // Example payment method
            $order->set_payment_method_title(__('Bank Transfer', 'multistep-checkout'));
            $order->save();
            error_log('Default payment method set for Order ID: ' . $order_id);
        }

        // Update order status to pending if not already set
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending', __('Order set to pending.', 'multistep-checkout'));
            error_log('Order status updated to pending for Order ID: ' . $order_id);
        }
    }

    /**
     * Force order status to pending if completed automatically
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function force_pending_status($status, $order_id, $order) {
        if ($order->get_status() !== 'pending') {
            error_log('Order ID ' . $order_id . ' status overridden to pending.');
            return 'pending';
        }
        return $status;
    }

    /**
     * Log order status changes
     *
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     */
    public function log_status_change($order_id, $old_status, $new_status) {
        error_log('Order ID ' . $order_id . ' status changed from ' . $old_status . ' to ' . $new_status);
    }

    /**
     * Debug checkout data
     */
    public function debug_checkout_data() {
        error_log('--------- Checkout Debug Start ---------');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Session data: ' . print_r(WC()->session->get_session_data(), true));
        error_log('Cart total: ' . WC()->cart->get_total());
        error_log('--------- Checkout Debug End ---------');
    }

    /**
     * Log checkout errors
     */
    public function log_checkout_errors() {
        if (wc_notice_count('error') > 0) {
            $notices = wc_get_notices('error');
            error_log('Checkout Errors: ' . print_r($notices, true));
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
