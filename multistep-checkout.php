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
        // Hook into WooCommerce checkout fields to modify them
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');

        // Allow order creation without payment methods
        //add_filter('woocommerce_order_needs_payment', '__return_false');

        // Bypass WooCommerce payment validation on checkout
        //add_action('woocommerce_checkout_process', [$this, 'bypass_payment_validation']);

        // Set default payment method after order is created
        add_action('woocommerce_checkout_order_created', [$this, 'set_default_payment_method']);

        // Force redirect to the order pay page
        //add_action('woocommerce_checkout_process', [$this, 'force_redirect_to_order_pay']);

        add_action('woocommerce_checkout_order_processed', [$this, 'custom_checkout_redirect'], 10, 3);

        // Redirect to the order pay page after order creation
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'redirect_to_order_pay' ), 10, 1 );

        add_action('woocommerce_checkout_process', [$this, 'custom_checkout_process']);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
        add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);

        // Add hidden payment method field to checkout form
        //add_action('woocommerce_review_order_before_submit', [$this, 'add_hidden_payment_method_field']);

        // Modify order-pay page (optional customization)
        //add_action('woocommerce_receipt', [$this, 'customize_order_pay_page']);

        // Ensure payment method is valid before processing
        //add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'allow_payment_for_pending_orders'], 10, 2);

        //add_action('template_redirect', [$this, 'clear_notices_on_order_pay']);

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
     * Bypass WooCommerce payment validation on checkout
     */
    public function bypass_payment_validation() {
        // Remove default payment method validation
        remove_filter('woocommerce_checkout_process', 'woocommerce_checkout_payment_method_missing_message', 10);
        error_log('Bypassed payment validation on checkout.');
    }



    public function remove_payment_gateways_from_checkout( $available_gateways ) {
        // Unset all available gateways
        $available_gateways = array(); 
        return $available_gateways; 
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
     * Force redirect to the order pay page
     */
    public function force_redirect_to_order_pay() {
        // Clear all notices
        wc_clear_notices();
        error_log('Cleared WooCommerce notices.');

        $order_id = WC()->session->get('order_awaiting_payment');
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order && $order->get_status() === 'pending-payment') {
                // Add success notice
                wc_add_notice(__('Redirecting to payment page. Please wait...', 'multistep-checkout'), 'success');

                // Build the redirect URL
                $redirect_url = add_query_arg(
                    ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
                    $order->get_checkout_payment_url()
                );
                error_log('Forcing redirect to: ' . $redirect_url);

                // Redirect to order pay page
                wp_redirect($redirect_url);
                exit;
            }
        }
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

    
    public function custom_checkout_redirect($order_id, $posted_data, $order) {
        // Log untuk debugging
        error_log('Processing order ID: ' . $order_id);
        
        if ($order) {
            // Generate payment URL
            $payment_url = $order->get_checkout_payment_url(true);
            
            // Set session untuk mencegah error
            WC()->session->set('chosen_payment_method', '');
            
            // Redirect ke halaman pembayaran
            wp_redirect($payment_url);
            exit;
        }
    }


    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        // Example validation: Ensure first name is filled in
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('Please fill in your billing first name.', 'multistep-checkout'), 'error');
        }
    }

    /**
     * Add hidden payment method field to checkout form
     */
    public function add_hidden_payment_method_field() {
        echo '<input type="hidden">';
    }

    public function custom_checkout_process() {
        // Remove payment validation
        remove_action('woocommerce_checkout_process', 'woocommerce_checkout_process_payment');
        
        // Log untuk debugging
        error_log('Custom checkout process executed');
    }

    /**
     * Allow payment for pending orders
     *
     * @param array $statuses
     * @param WC_Order $order
     * @return array
     */
    public function allow_payment_for_pending_orders($statuses, $order) {
        if ($order->get_status() === 'pending') {
            $statuses[] = 'pending';
        }

        // Log the action
        error_log('Allowing payment for Order ID: ' . $order->get_id() . ' with status: ' . $order->get_status());

        return $statuses;
    }

    /**
     * Customize the order-pay page (optional)
     *
     * @param int $order_id
     */
    public function customize_order_pay_page($order_id) {
        echo '<p>' . __('Please select a payment method to complete your order.', 'multistep-checkout') . '</p>';
    }

    public function clear_notices_on_order_pay() {
        if (is_wc_endpoint_url('order-pay')) {
            wc_clear_notices();
        }
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