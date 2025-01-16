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
        add_filter('woocommerce_cart_needs_payment', '__return_false', 10, 2);

        // Allow order creation without payment methods
        add_filter('woocommerce_order_needs_payment', '__return_false', 10, 2);

        // Automatically set a temporary payment method
        add_action('woocommerce_checkout_create_order', [$this, 'set_order_payment_method'], 10, 2);

        // Redirect to payment page after order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'handle_order_redirect'], 10, 2);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        // Modify order-pay page (optional customization)
        add_action('woocommerce_receipt', [$this, 'customize_order_pay_page']);
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
     * Set a temporary payment method for the order
     *
     * @param WC_Order $order
     */
    public function set_order_payment_method($order) {
        $payment_method = 'bacs'; // Set dummy payment method (Bank Transfer in this case)
        $order->set_payment_method($payment_method);
        $order->set_payment_method_title(__('Bank Transfer (Dummy)', 'multistep-checkout'));
        $order->add_order_note(__('Temporary payment method applied for multi-step checkout.', 'multistep-checkout'));
    }

    /**
     * Redirect to the payment page after order creation
     *
     * @param int $order_id
     * @param array $posted_data
     */
    public function handle_order_redirect($order_id, $posted_data) {
        if (!$order_id) {
            return;
        }

        // Ensure we have a valid order object
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Set order status to pending payment
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending-payment', __('Order created and waiting for payment.', 'multistep-checkout'));
        }

        // Clear session and cart
        WC()->session->set('order_awaiting_payment', $order_id);
        WC()->cart->empty_cart();

        // Redirect to payment page
        $pay_url = $order->get_checkout_payment_url(true);
        if (!empty($pay_url)) {
            wp_safe_redirect($pay_url);
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
