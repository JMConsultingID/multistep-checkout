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

define('MLT_CHECKOUT_VERSION', '1.0');
define('MLT_CHECKOUT_DIR', plugin_dir_path(__FILE__));
define('MLT_CHECKOUT_URL', plugin_dir_url(__FILE__));

class Multistep_Checkout {

    public function __construct() {
        // Core checkout modifications
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        
        // Remove payment step from initial checkout
        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');
        add_filter('woocommerce_available_payment_gateways', [$this, 'remove_payment_gateways_from_checkout']);
        
        // Handle order creation and redirect
        add_action('woocommerce_checkout_process', [$this, 'custom_checkout_process'], 20);
        add_action('woocommerce_checkout_order_created', [$this, 'handle_order_creation'], 10, 1);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
            add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);
        }
    }

    public function customize_checkout_fields($fields) {
        // Remove unnecessary fields
        unset($fields['shipping']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        return $fields;
    }

    public function remove_payment_gateways_from_checkout($gateways) {
        if (is_checkout() && !is_wc_endpoint_url('order-pay')) {
            return array();
        }
        return $gateways;
    }

    public function custom_checkout_process() {
        try {
            // Remove standard payment validation
            remove_action('woocommerce_checkout_process', 'woocommerce_checkout_process_payment');
            
            // Validate required fields
            $this->validate_checkout_fields();
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
        }
    }

    public function validate_checkout_fields() {
        $required_fields = array(
            'billing_first_name' => __('First name', 'woocommerce'),
            'billing_last_name'  => __('Last name', 'woocommerce'),
            'billing_email'      => __('Email address', 'woocommerce'),
            'billing_phone'      => __('Phone', 'woocommerce'),
        );

        foreach ($required_fields as $field_key => $field_name) {
            if (empty($_POST[$field_key])) {
                throw new Exception(sprintf(__('%s is a required field.', 'woocommerce'), $field_name));
            }
        }
    }

    public function handle_order_creation($order) {
        try {
            if (!$order || !is_a($order, 'WC_Order')) {
                throw new Exception('Invalid order object');
            }

            // Set order status to pending payment
            $order->update_status('pending', __('Order created, awaiting payment selection.', 'multistep-checkout'));
            
            // Clear any payment method data
            $order->set_payment_method('');
            $order->set_payment_method_title('');
            $order->save();

            // Create pay URL with necessary parameters
            $pay_url = add_query_arg(
                array(
                    'pay_for_order' => true,
                    'key'           => $order->get_order_key()
                ),
                $order->get_checkout_payment_url()
            );

            // Clear session data
            WC()->session->set('chosen_payment_method', '');
            
            // Redirect to payment page
            wp_redirect($pay_url);
            exit;

        } catch (Exception $e) {
            error_log('Multistep Checkout Error: ' . $e->getMessage());
            wc_add_notice(__('There was an error processing your order. Please try again.', 'multistep-checkout'), 'error');
        }
    }

    public function debug_checkout_data() {
        error_log('--- Checkout Debug Start ---');
        error_log('POST: ' . print_r($_POST, true));
        error_log('Session: ' . print_r(WC()->session->get_session_data(), true));
        error_log('Cart Total: ' . WC()->cart->get_total());
        error_log('--- Checkout Debug End ---');
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