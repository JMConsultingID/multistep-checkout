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

        // Modify billing fields
        add_filter('woocommerce_checkout_fields', [$this, 'customize_billing_fields']);
        
        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ensure_completed_orders_remain_completed'], 10, 3);
    }

    /**
     * Unset and add custom billing fields
     *
     * @param array $fields
     * @return array
     */
    public function customize_billing_fields($fields) {
        // Unset default billing fields
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_postcode']);
        unset($fields['billing']['billing_state']);

        // Add custom billing fields
        $fields['billing']['billing_first_name'] = [
            'label'    => __('First Name', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-first'],
            'priority' => 10,
        ];

        $fields['billing']['billing_last_name'] = [
            'label'    => __('Last Name', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-last'],
            'priority' => 20,
        ];

        $fields['billing']['billing_email'] = [
            'label'    => __('Email', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-first'],
            'priority' => 30,
        ];

        $fields['billing']['billing_phone'] = [
            'label'    => __('Phone Number', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-last'],
            'priority' => 40,
        ];

        $fields['billing']['billing_address_1'] = [
            'label'    => __('Address', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-wide'],
            'priority' => 50,
        ];

        $fields['billing']['billing_country'] = [
            'type'     => 'select',
            'label'    => __('Country', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-first', 'update_totals_on_change'],
            'priority' => 60,
            'options'  => array_merge(['' => __('Select Country', 'multistep-checkout')], WC()->countries->get_allowed_countries()),
        ];

        $fields['billing']['billing_state'] = [
            'type'     => 'select',
            'label'    => __('State/Region', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-last', 'update_totals_on_change'],
            'priority' => 70,
            'options'  => ['' => __('Select State', 'multistep-checkout')], // States will be dynamically loaded via JS
        ];

        $fields['billing']['billing_city'] = [
            'label'    => __('City', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-first'],
            'priority' => 80,
        ];

        $fields['billing']['billing_postcode'] = [
            'label'    => __('Postal Code', 'multistep-checkout'),
            'required' => true,
            'class'    => ['form-row-last'],
            'priority' => 90,
        ];

        return $fields;
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
            $order->update_status('completed');
        } else {
            $order->add_order_note(sprintf(__('Order Created. Order ID: #%d', 'multistep-checkout'), $order->get_id()));
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
            return;
        }

        if ($order->get_status() === 'pending') {
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
        if ($order->get_status() === 'pending') {
            return 'pending';
        }
        return $status;
    }
}

// Initialize the plugin
new Multistep_Checkout();

