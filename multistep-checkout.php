<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

class Multistep_Checkout {

    public function __construct() {
        // Core checkout modifications
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        
        // Remove payment step from initial checkout
        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');
        add_filter('woocommerce_available_payment_gateways', [$this, 'remove_payment_gateways_from_checkout']);
        
        // Critical filters for order creation without payment
        add_filter('woocommerce_checkout_require_payment', '__return_false', 999);
        add_filter('woocommerce_order_needs_payment', '__return_false');
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'add_pending_to_valid_statuses'], 10, 2);
        
        // Remove default WooCommerce order validation
        add_filter('woocommerce_can_reduce_order_stock', '__return_false');
        
        // Handle checkout process
        add_action('woocommerce_checkout_process', [$this, 'custom_checkout_process'], 1);
        add_action('woocommerce_checkout_create_order', [$this, 'setup_new_order'], 10, 2);
        add_action('woocommerce_checkout_order_created', [$this, 'handle_order_creation'], 999);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('woocommerce_before_checkout_process', [$this, 'debug_checkout_data']);
            add_action('woocommerce_checkout_process', [$this, 'log_checkout_errors'], 1);
        }
    }

    public function customize_checkout_fields($fields) {
        // Remove unnecessary fields
        unset($fields['shipping']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        return $fields;
    }

    public function remove_payment_gateways_from_checkout($gateways) {
        if (is_checkout() && !is_wc_endpoint_url('order-pay')) {
            return array();
        }
        return $gateways;
    }

    public function add_pending_to_valid_statuses($statuses, $order) {
        if (!in_array('pending', $statuses)) {
            $statuses[] = 'pending';
        }
        return $statuses;
    }

    public function custom_checkout_process() {
        try {
            // Remove payment validation
            remove_all_actions('woocommerce_checkout_process');
            
            // Clear existing notices
            wc_clear_notices();
            
            // Validate fields
            $this->validate_checkout_fields();
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            error_log('Checkout Process Error: ' . $e->getMessage());
        }
    }

    public function setup_new_order($order, $data) {
        // Set empty payment method
        $order->set_payment_method('');
        $order->set_payment_method_title('');
        
        // Set order status
        $order->set_status('pending', 'Order created via multistep checkout.');
        
        // Save customer data
        $this->save_customer_data($order);
        
        return $order;
    }

    private function save_customer_data($order) {
        if (!empty($_POST['billing_email'])) {
            $order->set_customer_id(email_exists($_POST['billing_email']));
        }
        
        // Save billing email as customer note
        $order->set_customer_note(sprintf(
            'Customer email: %s',
            sanitize_email($_POST['billing_email'])
        ));
    }

    public function handle_order_creation($order) {
        try {
            if (!$order || !is_a($order, 'WC_Order')) {
                throw new Exception('Invalid order object');
            }

            // Ensure proper status and meta
            $order->update_status('pending', __('Awaiting payment selection.', 'multistep-checkout'));
            update_post_meta($order->get_id(), '_created_via', 'multistep_checkout');

            // Generate payment URL with necessary parameters
            $pay_url = add_query_arg(
                array(
                    'pay_for_order' => true,
                    'key' => $order->get_order_key(),
                    'order_id' => $order->get_id(),
                    'from_multistep' => 1
                ),
                $order->get_checkout_payment_url()
            );

            // Clear session and cart
            WC()->session->set('chosen_payment_method', '');
            WC()->cart->empty_cart();

            // Log success
            error_log(sprintf('Successfully created order %d. Redirecting to: %s', 
                $order->get_id(), 
                $pay_url
            ));

            // Redirect to payment page
            wp_safe_redirect($pay_url);
            exit;

        } catch (Exception $e) {
            error_log('Order Creation Error: ' . $e->getMessage());
            wc_add_notice(__('There was an error processing your order. Please try again.', 'multistep-checkout'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }

    public function validate_checkout_fields() {
        $required_fields = array(
            'billing_first_name' => __('First name', 'woocommerce'),
            'billing_last_name'  => __('Last name', 'woocommerce'),
            'billing_email'      => __('Email address', 'woocommerce'),
            'billing_phone'      => __('Phone', 'woocommerce'),
            'billing_country'    => __('Country', 'woocommerce'),
            'billing_address_1'  => __('Address', 'woocommerce'),
            'billing_city'       => __('City', 'woocommerce'),
            'billing_state'      => __('State', 'woocommerce'),
            'billing_postcode'   => __('Postcode', 'woocommerce')
        );

        $missing_fields = array();
        foreach ($required_fields as $field_key => $field_name) {
            if (empty($_POST[$field_key])) {
                $missing_fields[] = $field_name;
            }
        }

        if (!empty($missing_fields)) {
            throw new Exception(sprintf(
                __('Please fill in the following fields: %s', 'woocommerce'),
                implode(', ', $missing_fields)
            ));
        }

        // Validate email format
        if (!empty($_POST['billing_email']) && !is_email($_POST['billing_email'])) {
            throw new Exception(__('Invalid email address.', 'woocommerce'));
        }
    }

    public function debug_checkout_data() {
        error_log('--------- Checkout Debug Start ---------');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Session data: ' . print_r(WC()->session->get_session_data(), true));
        error_log('Cart total: ' . WC()->cart->get_total());
        error_log('--------- Checkout Debug End ---------');
    }

    public function log_checkout_errors() {
        if (wc_notice_count('error') > 0) {
            $notices = WC()->session->get('wc_notices', array());
            error_log('Checkout Errors: ' . print_r($notices, true));
        }
    }
}

// Initialize the plugin
function init_multistep_checkout() {
    new Multistep_Checkout();
}
add_action('plugins_loaded', 'init_multistep_checkout');