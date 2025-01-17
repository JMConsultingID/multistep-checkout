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
        // Modify checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        
        // Handle order creation and redirect
        add_action('woocommerce_checkout_order_processed', [$this, 'process_order_and_redirect'], 10, 3);
        
        // Debug logs
        add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
        add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);
        
        // Add custom validation
        add_action('woocommerce_checkout_process', [$this, 'custom_checkout_validation']);
    }

    /**
     * Customize checkout fields
     */
    public function customize_checkout_fields($fields) {
        // Remove payment fields from checkout
        unset($fields['payment']);
        
        return $fields;
    }

    /**
     * Custom validation before order creation
     */
    public function custom_checkout_validation() {
        if (WC()->cart->is_empty()) {
            wc_add_notice(__('Your cart is empty!', 'multistep-checkout'), 'error');
        }

        // Add any additional custom validation here
    }

    /**
     * Process order and redirect to payment page
     *
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function process_order_and_redirect($order_id, $posted_data, $order) {
        if (!$order) {
            error_log('Order not found for Order ID: ' . $order_id);
            return;
        }

        try {
            // Set order status to pending payment
            $order->update_status('pending', __('Order created and awaiting payment.', 'multistep-checkout'));

            // Set a temporary payment method if needed
            if (!$order->get_payment_method()) {
                $order->set_payment_method('pending');
                $order->set_payment_method_title(__('Payment Pending', 'multistep-checkout'));
                $order->save();
            }

            // Generate payment URL
            $payment_url = $order->get_checkout_payment_url(true);

            // Log the redirect URL
            error_log('Redirecting to payment page: ' . $payment_url);

            // Ensure WooCommerce notices are cleared
            wc_clear_notices();

            // Safe redirect
            wp_safe_redirect($payment_url);
            exit;

        } catch (Exception $e) {
            error_log('Error processing order ' . $order_id . ': ' . $e->getMessage());
            wc_add_notice(
                __('There was an error processing your order. Please try again.', 'multistep-checkout'),
                'error'
            );
        }
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
            $notices = WC()->session->get('wc_notices', array());
            error_log('Checkout Errors: ' . print_r($notices, true));
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();