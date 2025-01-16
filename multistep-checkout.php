<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.0
 * Author: YPF
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Multistep_Checkout {

    public function __construct() {
        // Hook into WooCommerce checkout fields to modify them
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Hook into the checkout process to modify order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'redirect_to_order_pay']);

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
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        $order = wc_get_order($order_id);

        // Set order status to pending payment
        $order->update_status('pending-payment', __('Order created, waiting for payment.', 'multistep-checkout'));

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
