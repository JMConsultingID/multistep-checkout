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

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ensure_completed_orders_remain_completed'], 10, 3);

        // Enqueue inline CSS for checkout fields
        add_action('wp_head', [$this, 'add_inline_css']);

        // Ensure country and state scripts are loaded
        add_action('wp_enqueue_scripts', [$this, 'enable_country_state_scripts'], 20);
    }

    public function customize_billing_fields($fields) {
        // Unset all default billing fields
        $fields['billing'] = [];

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
                'class'       => ['form-row-first'],
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

    public function set_order_status_based_on_total($order_id, $posted_data, $order) {
        if ($order->get_total() == 0) {
            $order->update_status('completed');
        } else {
            $order->add_order_note(sprintf(__('Order Created. Order ID: #%d', 'multistep-checkout'), $order->get_id()));
            $order->update_status('pending');
        }
    }

    public function redirect_after_checkout($order_id) {
        if (!$order_id) return;

        $order = wc_get_order($order_id);

        if ($order->get_total() == 0) return;

        if ($order->get_status() === 'pending') {
            $redirect_url = $order->get_checkout_payment_url();
            $order->add_order_note(__('Redirecting to order-pay page', 'multistep-checkout'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public function ensure_completed_orders_remain_completed($status, $order_id, $order) {
        if ($order->get_status() === 'pending') {
            return 'pending';
        }
        return $status;
    }

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

    public function enable_country_state_scripts() {
        if (is_checkout()) {
            wp_enqueue_script('wc-country-select');
            wp_enqueue_script('wc-address-i18n');
        }
    }
}

new Multistep_Checkout();
