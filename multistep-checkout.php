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

        // Customize billing fields
        add_filter('woocommerce_checkout_fields', [$this, 'customize_billing_fields']);

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ensure_completed_orders_remain_completed'], 10, 3);

        // Validate custom fields
        add_action('woocommerce_checkout_process', [$this, 'validate_billing_fields']);

        // Save custom fields to order metadata
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_custom_fields']);

        // Display custom fields in admin order page
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_custom_fields_in_admin']);
    }

    /**
     * Customize billing fields on the checkout page.
     *
     * @param array $fields
     * @return array
     */
    public function customize_billing_fields($fields) {
        // Unset all default billing fields
        unset($fields['billing']);

        // Add custom billing fields
        $fields['billing'] = [
            'billing_first_name' => [
                'type'        => 'text',
                'label'       => __('First Name', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-first'],
                'placeholder' => __('Enter your first name', 'multistep-checkout'),
            ],
            'billing_last_name' => [
                'type'        => 'text',
                'label'       => __('Last Name', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-last'],
                'placeholder' => __('Enter your last name', 'multistep-checkout'),
            ],
            'billing_email' => [
                'type'        => 'email',
                'label'       => __('Email', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-first'],
                'placeholder' => __('Enter your email address', 'multistep-checkout'),
            ],
            'billing_phone' => [
                'type'        => 'tel',
                'label'       => __('Phone Number', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-last'],
                'placeholder' => __('Enter your phone number', 'multistep-checkout'),
            ],
            'billing_address_1' => [
                'type'        => 'text',
                'label'       => __('Address', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-wide'],
                'placeholder' => __('Enter your address', 'multistep-checkout'),
            ],
            'billing_country' => [
                'type'        => 'country',
                'label'       => __('Country', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-first', 'address-field'],
            ],
            'billing_state' => [
                'type'        => 'state',
                'label'       => __('State/Region', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-last', 'address-field'],
            ],
            'billing_city' => [
                'type'        => 'text',
                'label'       => __('City', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-first'],
                'placeholder' => __('Enter your city', 'multistep-checkout'),
            ],
            'billing_postcode' => [
                'type'        => 'text',
                'label'       => __('Postal Code', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-last'],
                'placeholder' => __('Enter your postal code', 'multistep-checkout'),
            ],
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
     * Validate custom fields on the checkout page
     */
    public function validate_billing_fields() {
        if (empty($_POST['billing_first_name'])) {
            wc_add_notice(__('First name is required.', 'multistep-checkout'), 'error');
        }

        if (empty($_POST['billing_email'])) {
            wc_add_notice(__('Email is required.', 'multistep-checkout'), 'error');
        }
    }

    /**
     * Save custom fields to order metadata
     *
     * @param int $order_id
     */
    public function save_custom_fields($order_id) {
        if (!empty($_POST['billing_first_name'])) {
            update_post_meta($order_id, '_billing_first_name', sanitize_text_field($_POST['billing_first_name']));
        }

        if (!empty($_POST['billing_email'])) {
            update_post_meta($order_id, '_billing_email', sanitize_email($_POST['billing_email']));
        }
    }

    /**
     * Display custom fields in the admin order page
     *
     * @param WC_Order $order
     */
    public function display_custom_fields_in_admin($order) {
        echo '<p><strong>' . __('First Name', 'multistep-checkout') . ':</strong> ' . esc_html(get_post_meta($order->get_id(), '_billing_first_name', true)) . '</p>';
        echo '<p><strong>' . __('Email', 'multistep-checkout') . ':</strong> ' . esc_html(get_post_meta($order->get_id(), '_billing_email', true)) . '</p>';
    }
}

// Initialize the plugin
new Multistep_Checkout();
