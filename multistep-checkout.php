<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

class Multistep_Checkout {

    public function __construct() {
        // Remove default payment fields and methods
        add_action('init', [$this, 'remove_payment_methods']);
        
        // Modify checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        
        // Bypass payment requirements
        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_order_needs_payment', '__return_true');
        
        // Handle order creation process
        add_action('woocommerce_checkout_process', [$this, 'modify_checkout_process']);
        add_action('woocommerce_checkout_create_order', [$this, 'set_order_payment_method']);
        add_action('woocommerce_checkout_order_processed', [$this, 'handle_order_redirect'], 10, 3);
        
        // Allow pending payment orders
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'add_pending_to_valid_order_statuses']);
        
        // Disable payment validation
        add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');
    }

    public function remove_payment_methods() {
        remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
        remove_action('woocommerce_checkout_process', 'woocommerce_checkout_process_payment');
    }

    public function customize_checkout_fields($fields) {
        // Remove payment fields
        unset($fields['payment']);
        
        // Customize billing fields as needed
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        return $fields;
    }

    public function modify_checkout_process() {
        // Basic validation
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('First name is required.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['billing_last_name'])) {
            wc_add_notice(__('Last name is required.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['billing_email'])) {
            wc_add_notice(__('Email address is required.', 'multistep-checkout'), 'error');
        }
    }

    public function set_order_payment_method($order) {
        // Set a temporary payment method
        $order->set_payment_method('');
        $order->set_payment_method_title('');
        $order->set_status('pending', 'Order created via multi-step checkout.');
    }

    public function handle_order_redirect($order_id, $posted_data, $order) {
        if (!$order_id) {
            return;
        }

        // Ensure we have a valid order object
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Clear session and cart
        WC()->session->set('order_awaiting_payment', $order_id);
        WC()->cart->empty_cart();

        // Get the pay page URL
        $pay_url = $order->get_checkout_payment_url(true);

        // Redirect to payment page
        if (!empty($pay_url)) {
            wp_redirect($pay_url);
            exit;
        }
    }

    public function add_pending_to_valid_order_statuses($statuses) {
        if (!in_array('pending', $statuses)) {
            $statuses[] = 'pending';
        }
        return $statuses;
    }
}

// Initialize the plugin
function init_multistep_checkout() {
    if (class_exists('WooCommerce')) {
        new Multistep_Checkout();
    }
}

add_action('plugins_loaded', 'init_multistep_checkout');

// Add custom CSS for multi-step display (optional)
add_action('wp_head', function() {
    if (is_checkout()) {
        ?>
        <style>
            .woocommerce-checkout-payment {
                display: none !important;
            }
            /* Add your multi-step styling here */
        </style>
        <?php
    }
});