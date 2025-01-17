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

// Define constants for plugin paths.
define('MLT_CHECKOUT_VERSION', '1.0');
define('MLT_CHECKOUT_DIR', plugin_dir_path(__FILE__));
define('MLT_CHECKOUT_URL', plugin_dir_url(__FILE__));

class Multistep_Checkout {

    public function __construct() {
        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Set default payment method after order is created
        add_action('woocommerce_checkout_order_created', [$this, 'set_default_payment_method']);

        // Redirect to the order pay page after order creation
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'redirect_to_order_pay' ), 10, 1 );

        add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
        add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);
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

        $payment_method = ''; // Use a valid payment method ID as a placeholder
        $order->set_payment_method($payment_method);
        $order->set_payment_method_title('');    
        $order->add_order_note(__('Default payment method.', 'multistep-checkout'));
        $order->save();

        // Log the action
        error_log('Default payment method set to BACS for Order ID: ' . $order->get_id());
    }


    /**
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        // Log the order ID for debugging
        error_log('Redirecting to order-pay. Order ID: ' . $order_id);

        $order = wc_get_order($order_id);

        if (!$order) {
            error_log('Order not found for Order ID: ' . $order_id);
            return;
        }


        $order->update_status('pending', __('Order created, waiting for payment.', 'multistep-checkout'));


        // Build the redirect URL with 'pay_for_order' and 'key'
        $redirect_url = add_query_arg(
            ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
            $order->get_checkout_payment_url()
        );

        error_log('Redirecting user to: ' . $redirect_url);

        // Perform the redirect
        wp_redirect($redirect_url);
        exit;
    }


    public function debug_checkout_data() {
        error_log('--------- Checkout Debug Start ---------');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Session data: ' . print_r(WC()->session->get_session_data(), true));
        error_log('Cart total: ' . WC()->cart->get_total());
        error_log('--------- Checkout Debug End ---------');
    }


    public function log_checkout_errors() {
        if (wc_notice_count('error') > 0) {
            $notices = WC()->session->get('wc_notices', array());
            error_log('Checkout Errors: ' . print_r($notices, true));
        }
    }

}

// Initialize the plugin
new Multistep_Checkout();