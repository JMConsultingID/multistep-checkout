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
        // Disable payment options on checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ensure_completed_orders_remain_completed'], 10, 3);
        
        // Customize checkout fields layout
        add_filter('woocommerce_checkout_fields', [$this, 'custom_checkout_fields_layout']);
        add_filter('woocommerce_form_field_args', [$this, 'custom_checkout_field_args'], 10, 3);
    }
    /**
     * Set order status based on total at checkout
     *
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function set_order_status_based_on_total($order_id, $posted_data, $order) {
        if ($order->get_total() == 0) {
            // If order total is 0, set status to completed
            $order->update_status('completed');
        } else {
            // If order total > 0, set status to pending
            $order->add_order_note( sprintf( __( 'Order Created. Order ID: #%d', 'multistep-checkout' ), $order->get_id() ) );
            $order->update_status('pending');
        }
        error_log('Order ID ' . $order_id . ' status updated based on total: ' . $order->get_total());
    }

    /**
     * Redirect to appropriate page after checkout
     *
     * @param int $order_id
     */
    public function redirect_after_checkout($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if ($order->get_total() == 0) {
            // If order total is 0, let WooCommerce handle the flow to Thank You page
            return;
        }

        if ($order->get_status() === 'pending') {
            // Redirect unpaid orders to order-pay page
            $redirect_url = $order->get_checkout_payment_url();
            $order->add_order_note(__('Redirecting to order-pay page', 'multistep-checkout'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Ensure completed orders remain completed
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function ensure_completed_orders_remain_completed($status, $order_id, $order) {
        // Only adjust orders that are not already completed
        if ($order->get_status() === 'pending') {
            return 'pending'; // Keep pending for unpaid orders
        }
        return $status; // Return default status for other cases
    }

     /**
     * Customize checkout fields layout
     *
     * @param array $fields
     * @return array
     */
    public function custom_checkout_fields_layout($fields) {
        foreach ($fields['billing'] as $key => $field) {
            $fields['billing'][$key]['class'] = ['form-group', 'custom-billing-field']; // Add custom classes
            if (in_array($key, ['billing_first_name', 'billing_last_name'])) {
                $fields['billing'][$key]['class'][] = 'form-row-half'; // Half-width for first name and last name
            }
        }
        return $fields;
    }

    /**
     * Customize checkout field arguments
     *
     * @param array $args
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function custom_checkout_field_args($args, $key, $value) {
        if (in_array($key, ['billing_country', 'billing_state'])) {
            $args['class'][] = 'form-row-half'; // Half-width for dropdowns
        }
        return $args;
    }
}

// Initialize the plugin
new Multistep_Checkout();

/* Inline CSS */
function multistep_checkout_inline_styles() {
    echo '<style>
        .woocommerce-billing-fields {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .custom-billing-field {
            width: 100%;
            position: relative;
        }

        .custom-billing-field.form-row-half {
            width: calc(50% - 10px);
        }

        .woocommerce-billing-fields input,
        .woocommerce-billing-fields select {
            width: 100%;
            padding: 10px 15px;
            font-size: 16px;
            border: 1px solid #444;
            background: #1a1a1a;
            color: #fff;
            border-radius: 5px;
        }

        .woocommerce-billing-fields label {
            font-size: 14px;
            color: #ddd;
            display: block;
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .woocommerce-billing-fields select {
            background-color: #222;
        }

        body.woocommerce-checkout {
            background: #121212;
            color: #fff;
        }

        body.woocommerce-checkout input::placeholder {
            color: #aaa;
        }
    </style>';
}
add_action('wp_head', 'multistep_checkout_inline_styles');