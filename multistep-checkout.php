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

        // Allow order creation without payment methods
        add_filter('woocommerce_order_needs_payment', '__return_false');

        // Override payment validation during checkout
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'allow_payment_for_all_statuses'], 10, 2);

        // Automatically set COD (Cash on Delivery) payment method for bypassing payment validation
        add_action('woocommerce_checkout_create_order', [$this, 'set_cod_payment_method']);

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
     * Allow payment for all order statuses
     *
     * @param array $statuses
     * @param WC_Order $order
     * @return array
     */
    public function allow_payment_for_all_statuses($statuses, $order) {
        $statuses[] = 'pending';
        return $statuses;
    }

    /**
     * Automatically set COD payment method to bypass validation
     *
     * @param WC_Order $order
     */
    public function set_cod_payment_method($order) {
        if (!$order || !$order->get_id()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Multistep Checkout] Failed to set payment method: Invalid Order.');
            }
            return;
        }

        $payment_method = 'cod'; // Use COD as the default payment method
        $order->set_payment_method($payment_method);

        // Add order note and log for debugging
        $note = __('Payment method set to COD as placeholder.', 'multistep-checkout');
        $order->add_order_note($note);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Multistep Checkout] Order ID: ' . $order->get_id() . ' - ' . $note);
        }
    }


    /**
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        if (!$order_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Multistep Checkout] Redirect failed: Invalid Order ID.');
            }
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Multistep Checkout] Redirect failed: Order not found for ID ' . $order_id);
            }
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
