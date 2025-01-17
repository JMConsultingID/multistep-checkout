<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.1.0
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants for plugin paths.
define('MLT_CHECKOUT_VERSION', '1.1.0');
define('MLT_CHECKOUT_DIR', plugin_dir_path(__FILE__));
define('MLT_CHECKOUT_URL', plugin_dir_url(__FILE__));

class Multistep_Checkout {

    public function __construct() {
        // Hook into WooCommerce checkout fields to modify them
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Allow order creation without payment methods
        add_filter('woocommerce_order_needs_payment', '__return_false');

        add_action('woocommerce_checkout_order_processed', [$this, 'handle_order_and_redirect'], 10, 1);

        // Validate checkout fields
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        // Debugging and error logging
        add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
        add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);
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
     * Set default payment method and redirect to the order pay page
     *
     * @param int $order_id
     */
    public function handle_order_and_redirect($order_id) {
        $order = wc_get_order($order_id);

        if (!$order || !$order->get_id()) {
            error_log('Failed to process order. Invalid Order object or Order ID: ' . ($order ? $order->get_id() : 'NULL'));
            return;
        }

        // Set default payment method
        $order->set_payment_method('bacs');
        $order->set_payment_method_title(__('Bank Transfer', 'multistep-checkout'));
        $order->add_order_note(__('Default payment method set to Bank Transfer.', 'multistep-checkout'));
        $order->save();

        error_log('Default payment method set for Order ID: ' . $order->get_id());

        // Update order status to pending
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending', __('Order created and waiting for payment.', 'multistep-checkout'));
        }

        // Redirect to the order pay page
        $redirect_url = add_query_arg(
            ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
            $order->get_checkout_payment_url()
        );

        error_log('Redirecting user to: ' . $redirect_url);

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('Please fill in your billing first name.', 'multistep-checkout'), 'error');
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
            $notices = wc_get_notices('error');
            error_log('Checkout Errors: ' . print_r($notices, true));
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
