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
        // Hook into WooCommerce checkout fields to modify them
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Set dummy payment method after order is processed
        add_action('woocommerce_checkout_order_processed', [$this, 'set_dummy_payment_method']);

        // Redirect to the order pay page after order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'redirect_to_order_pay'], 20);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        // Modify order-pay page (optional customization)
        add_action('woocommerce_receipt', [$this, 'customize_order_pay_page']);

        // Ensure payment method is valid before processing
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'allow_payment_for_pending_orders'], 10, 2);
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
     * Automatically set a dummy payment method to bypass validation
     *
     * @param int $order_id
     */
    public function set_dummy_payment_method($order_id) {
        $order = wc_get_order($order_id);

        if (!$order || !$order->get_id()) {
            error_log('Failed to set payment method. Invalid Order object or Order ID: ' . ($order ? $order->get_id() : 'NULL'));
            return;
        }

        $payment_method = 'bacs'; // Use a valid payment method ID as a placeholder
        $order->set_payment_method($payment_method);
        $order->add_order_note(__('Payment method set to BACS as placeholder.', 'multistep-checkout'));

        // Log the action
        error_log('Dummy payment method set for Order ID: ' . $order->get_id());
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

        // Set order status to pending payment
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending-payment', __('Order created, waiting for payment.', 'multistep-checkout'));
        }

        // Redirect to the order pay page
        wp_redirect($order->get_checkout_payment_url());
        exit;
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
}

// Initialize the plugin
new Multistep_Checkout();
