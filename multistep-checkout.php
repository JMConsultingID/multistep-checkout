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
        add_filter('woocommerce_checkout_fields', [$this, 'customize_billing_fields'], 15);

        add_action('wp_enqueue_scripts', [$this, 'disable_country_state_scripts'], 20);

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ensure_completed_orders_remain_completed'], 10, 3);

        // Enqueue inline CSS for checkout fields
        add_action('wp_head', [$this, 'add_inline_css']);
    }

    /**
     * Customize billing fields on the checkout page.
     *
     * @param array $fields
     * @return array
     */
    public function customize_billing_fields($fields) {
        // Reset semua field default
        unset($fields['billing']);

        // Tambahkan field yang baru
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
                'type'        => 'select',
                'label'       => __('Country', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-first'],
                'options'     => [
                    ''     => __('Select a country', 'multistep-checkout'),
                    'US'   => __('United States', 'multistep-checkout'),
                    'ID'   => __('Indonesia', 'multistep-checkout'),
                ],
            ],
            'billing_city' => [
                'type'        => 'text',
                'label'       => __('City', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-last'],
                'placeholder' => __('Enter your city', 'multistep-checkout'),
            ],
            'billing_postcode' => [
                'type'        => 'text',
                'label'       => __('Postal Code', 'multistep-checkout'),
                'required'    => true,
                'class'       => ['form-row-wide'],
                'placeholder' => __('Enter your postal code', 'multistep-checkout'),
            ],
        ];

        return $fields;
    }

    public function disable_country_state_scripts() {
        if (is_checkout()) {
            wp_dequeue_script('wc-country-select');
            wp_dequeue_script('wc-address-i18n');
        }
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
     * Add inline CSS for checkout fields
     */
    public function add_inline_css() {
        if (is_checkout()) {
            echo '<style>
                .woocommerce-billing-fields .form-row {
                    margin-bottom: 15px;
                }
                .woocommerce-billing-fields .form-row-first,
                .woocommerce-billing-fields .form-row-last {
                    width: 48%;
                    display: inline-block;
                }
                .woocommerce-billing-fields .form-row-wide {
                    width: 100%;
                    display: block;
                }
            </style>';
        }
    }
}

// Initialize the plugin
new Multistep_Checkout();
