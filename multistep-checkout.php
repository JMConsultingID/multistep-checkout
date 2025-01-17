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

        // Ensure chosen payment method is set in session
        add_action('woocommerce_before_checkout_process', [$this, 'set_chosen_payment_method']);

        // Add hidden payment method field to checkout form
        add_action('woocommerce_review_order_before_submit', [$this, 'add_hidden_payment_method_field']);

        // Set default payment method after order is created
        add_action('woocommerce_checkout_order_created', [$this, 'set_default_payment_method']);

        // Redirect to the order pay page after order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'redirect_to_order_pay']);

        // Debugging and error logging
        add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
        add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);

        // Ensure at least one payment gateway is available
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_available_payment_gateways']);
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
     * Set chosen payment method in session
     */
    public function set_chosen_payment_method() {
        if (WC()->session) {
            WC()->session->set('chosen_payment_method', 'bacs');
            error_log('Chosen payment method set to BACS.');
        }
    }

    /**
     * Add hidden payment method field to checkout form
     */
    public function add_hidden_payment_method_field() {
        echo '<input type="hidden" name="payment_method" value="bacs">';
    }

    /**
     * Set default payment method after order is created
     *
     * @param WC_Order $order
     */
    public function set_default_payment_method($order) {
        if (!$order || !$order->get_id()) {
            error_log('Failed to set payment method. Invalid Order object or Order ID: ' . ($order ? $order->get_id() : 'NULL'));
            return;
        }

        $order->set_payment_method('bacs');
        $order->set_payment_method_title(__('Bank Transfer', 'multistep-checkout'));
        $order->add_order_note(__('Default payment method set to Bank Transfer.', 'multistep-checkout'));
        $order->save();

        error_log('Default payment method set for Order ID: ' . $order->get_id());
    }

    /**
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            error_log('Order not found for Order ID: ' . $order_id);
            return;
        }

        if ($order->get_status() !== 'pending') {
            $order->update_status('pending', __('Order updated for payment.', 'multistep-checkout'));
        }

        $redirect_url = add_query_arg(
            ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
            $order->get_checkout_payment_url()
        );

        error_log('Redirecting user to: ' . $redirect_url);

        wp_redirect($redirect_url);
        exit;
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

    /**
     * Filter available payment gateways to ensure at least one is available
     *
     * @param array $gateways
     * @return array
     */
    public function filter_available_payment_gateways($gateways) {
        if (is_checkout()) {
            $gateways = ['bacs' => $gateways['bacs']]; // Retain only the BACS gateway
        }
        return $gateways;
    }
}

// Initialize the plugin
new Multistep_Checkout();
