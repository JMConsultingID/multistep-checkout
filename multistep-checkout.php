<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.1
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Multistep_Checkout {

    public function __construct() {
        // Customize checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_order_needs_payment', '__return_false');

        // Set dummy payment method
        add_action('woocommerce_checkout_create_order', [$this, 'set_dummy_payment_method']);

        // Redirect to order-pay after order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'redirect_to_order_pay']);

        // Validate checkout fields
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);
    }

    /**
     * Customize checkout fields to remove unnecessary fields.
     *
     * @param array $fields
     * @return array
     */
    public function customize_checkout_fields($fields) {
        unset($fields['shipping']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        return $fields;
    }

    /**
     * Set a dummy payment method during order creation.
     *
     * @param WC_Order $order
     */
    public function set_dummy_payment_method($order) {
        $order->set_payment_method('bacs'); // Use 'bacs' as a dummy payment method
        $order->add_order_note(__('Dummy payment method applied for multi-step checkout.', 'multistep-checkout'));
    }

    /**
     * Redirect to the order-pay page after the order is created.
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        $order = wc_get_order($order_id);

        // Ensure order status is pending payment
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending-payment', __('Order created and waiting for payment.', 'multistep-checkout'));
        }

        // Redirect to the order-pay page
        wp_safe_redirect($order->get_checkout_payment_url());
        exit;
    }

    /**
     * Validate checkout fields.
     */
    public function validate_checkout_fields() {
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('Please provide your first name.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['billing_email'])) {
            wc_add_notice(__('Please provide your email address.', 'multistep-checkout'), 'error');
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
