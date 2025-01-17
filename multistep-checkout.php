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

// Define plugin constants
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
        
        // Prevent WooCommerce from requiring payment method
        add_filter('woocommerce_checkout_require_payment', '__return_false', 999);
        
        // Handle order creation and redirect
        add_action('woocommerce_checkout_process', [$this, 'custom_checkout_process'], 20);
        add_action('woocommerce_checkout_order_created', [$this, 'handle_order_creation'], 10, 1);
        
        // Add support for order creation without payment
        add_filter('woocommerce_create_order', [$this, 'maybe_create_order'], 10, 2);
        
        // Handle order status changes
        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change'], 10, 4);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
            add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);
        }

        // Initialize any additional hooks for checkout customization
        $this->init_checkout_hooks();
    }

    /**
     * Initialize additional checkout hooks
     */
    private function init_checkout_hooks() {
        // Modify checkout fields display
        add_filter('woocommerce_checkout_fields', [$this, 'modify_checkout_fields'], 99);
        
        // Add custom validation
        add_action('woocommerce_after_checkout_validation', [$this, 'custom_checkout_validation'], 10, 2);
        
        // Handle AJAX actions if needed
        add_action('wp_ajax_update_order_review', [$this, 'handle_ajax_update_order_review']);
        add_action('wp_ajax_nopriv_update_order_review', [$this, 'handle_ajax_update_order_review']);
    }

    /**
     * Customize checkout fields
     */
    public function customize_checkout_fields($fields) {
        // Remove unnecessary fields
        unset($fields['shipping']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        // Customize remaining fields if needed
        $fields['billing']['billing_phone']['priority'] = 20;
        $fields['billing']['billing_email']['priority'] = 10;
        
        return $fields;
    }

    /**
     * Modify checkout fields for display
     */
    public function modify_checkout_fields($fields) {
        // Additional field modifications
        foreach ($fields as $fieldset_key => $fieldset_fields) {
            foreach ($fieldset_fields as $field_key => $field) {
                // Add custom classes or modify attributes
                $fields[$fieldset_key][$field_key]['class'][] = 'form-row-wide';
            }
        }
        
        return $fields;
    }

    /**
     * Remove payment gateways from checkout
     */
    public function remove_payment_gateways_from_checkout($gateways) {
        if (is_checkout() && !is_wc_endpoint_url('order-pay')) {
            return array();
        }
        return $gateways;
    }

    /**
     * Handle order creation without payment method
     */
    public function maybe_create_order($order_id, $checkout) {
        // Remove payment validation
        remove_action('woocommerce_checkout_order_processed', 'woocommerce_checkout_process_payment');
        
        // Unset payment method from POST data
        if (isset($_POST['payment_method'])) {
            unset($_POST['payment_method']);
        }
        
        return $order_id;
    }

    /**
     * Custom checkout process
     */
    public function custom_checkout_process() {
        try {
            // Remove standard payment validation
            remove_action('woocommerce_checkout_process', 'woocommerce_checkout_process_payment');
            
            // Clear existing notices
            wc_clear_notices();
            
            // Validate required fields
            $this->validate_checkout_fields();
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            error_log('Checkout Process Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle order creation and redirect
     */
    public function handle_order_creation($order) {
        try {
            if (!$order || !is_a($order, 'WC_Order')) {
                throw new Exception('Invalid order object');
            }

            // Set initial order status
            $order->update_status('pending', __('Order created, awaiting payment selection.', 'multistep-checkout'));
            
            // Clear payment method data
            update_post_meta($order->get_id(), '_payment_method', '');
            update_post_meta($order->get_id(), '_payment_method_title', '');
            
            // Save order
            $order->save();

            // Generate payment URL
            $pay_url = add_query_arg(
                array(
                    'pay_for_order' => true,
                    'key' => $order->get_order_key(),
                    'order_id' => $order->get_id()
                ),
                $order->get_checkout_payment_url()
            );

            // Clear session data
            WC()->session->set('chosen_payment_method', '');
            
            // Clear cart
            WC()->cart->empty_cart();
            
            // Redirect to payment page
            wp_safe_redirect($pay_url);
            exit;

        } catch (Exception $e) {
            error_log('Order Creation Error: ' . $e->getMessage());
            wc_add_notice(__('There was an error processing your order. Please try again.', 'multistep-checkout'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }

    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        $required_fields = array(
            'billing_first_name' => __('First name', 'woocommerce'),
            'billing_last_name'  => __('Last name', 'woocommerce'),
            'billing_email'      => __('Email address', 'woocommerce'),
            'billing_phone'      => __('Phone', 'woocommerce'),
            'billing_country'    => __('Country', 'woocommerce'),
            'billing_address_1'  => __('Address', 'woocommerce'),
            'billing_city'       => __('City', 'woocommerce'),
            'billing_state'      => __('State', 'woocommerce'),
            'billing_postcode'   => __('Postcode', 'woocommerce')
        );

        $missing_fields = array();
        foreach ($required_fields as $field_key => $field_name) {
            if (empty($_POST[$field_key])) {
                $missing_fields[] = $field_name;
            }
        }

        if (!empty($missing_fields)) {
            throw new Exception(sprintf(
                __('Please fill in the following fields: %s', 'woocommerce'),
                implode(', ', $missing_fields)
            ));
        }
    }

    /**
     * Custom checkout validation
     */
    public function custom_checkout_validation($data, $errors) {
        // Add any additional validation logic here
        if (!empty($data['billing_email']) && !is_email($data['billing_email'])) {
            $errors->add('validation', __('Invalid email address.', 'woocommerce'));
        }
    }

    /**
     * Handle order status changes
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        error_log(sprintf(
            'Order %d status changed from %s to %s',
            $order_id,
            $old_status,
            $new_status
        ));
    }

    /**
     * Handle AJAX order review updates
     */
    public function handle_ajax_update_order_review() {
        // Add custom AJAX handling if needed
        check_ajax_referer('update-order-review', 'security');
        
        // Your custom AJAX logic here
        
        wp_die();
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
function init_multistep_checkout() {
    new Multistep_Checkout();
}
add_action('plugins_loaded', 'init_multistep_checkout');