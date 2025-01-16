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

    private $dummy_payment_method_id = 'multistep_checkout_dummy'; // Unique ID

    public function __construct() {
        // Hook into WooCommerce checkout fields to modify them
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);

        // Remove payment options from checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Allow order creation without payment methods
        add_filter('woocommerce_order_needs_payment', '__return_false');

        // Automatically set a dummy payment method for bypassing payment validation
        add_action('woocommerce_checkout_create_order', [$this, 'set_dummy_payment_method']);

        // Bypass payment validation during checkout
        add_filter('woocommerce_payment_complete_order_status', [$this, 'bypass_payment_status'], 10, 3);

        // Hook into the checkout process to modify order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'redirect_to_order_pay']);

        // Ensure form validation works as intended
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);

        // Modify order-pay page (optional customization)
        add_action('woocommerce_receipt', [$this, 'customize_order_pay_page']);

        // Ensure payment method is valid before processing
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'allow_payment_for_pending_orders'], 10, 2);

        // Add action to handle payment method selection
        add_action('wp_ajax_select_payment_method', [$this, 'handle_payment_method_selection']);
        add_action('wp_ajax_nopriv_select_payment_method', [$this, 'handle_payment_method_selection']);
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
     * @param WC_Order $order
     */
    public function set_dummy_payment_method($order) {
        $order->set_payment_method($this->dummy_payment_method_id);
        $order->add_order_note(__('Payment method temporarily set for multi-step checkout.', 'multistep-checkout')); 
    }

    /**
     * Redirect to the order pay page after creating the order
     *
     * @param int $order_id
     */
    public function redirect_to_order_pay($order_id) {
        $order = wc_get_order($order_id);

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
        return $statuses;
    }

    /**
     * Bypass payment status validation
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function bypass_payment_status($status, $order_id, $order) {
        if ($order->get_payment_method() === $this->dummy_payment_method_id) {
            return 'pending';
        }
        return $status;
    }

    /**
     * Customize the order-pay page (optional)
     *
     * @param int $order_id
     */
    public function customize_order_pay_page($order_id) {
        $order = wc_get_order($order_id);
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

        ?>
        <p><?php _e('Please select a payment method to complete your order.', 'multistep-checkout'); ?></p>
        <form id="select-payment-method-form" method="post">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            <ul>
                <?php foreach ($available_gateways as $gateway_id => $gateway) : ?>
                    <li>
                        <input type="radio" name="payment_method" value="<?php echo $gateway_id; ?>">
                        <label for="<?php echo $gateway_id; ?>"><?php echo $gateway->get_title(); ?></label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="submit"><?php _e('Select Payment Method', 'multistep-checkout'); ?></button>
        </form>
        <?php

        // Enqueue necessary scripts and styles
        wp_enqueue_script('multistep-checkout-order-pay', plugins_url('assets/js/order-pay.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('multistep-checkout-order-pay', 'multistep_checkout_ajax_url', admin_url('admin-ajax.php'));
    }

    public function handle_payment_method_selection() {
        check_ajax_referer('multistep_checkout_nonce', 'security');

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        if ($order_id > 0 && !empty($payment_method)) {
            $order = wc_get_order($order_id);

            if ($order) {
                $order->set_payment_method($payment_method);
                $order->save();

                // Redirect to the checkout payment page
                wp_redirect($order->get_checkout_payment_url());
                exit;
            }
        }

        wp_send_json_error(__('Invalid request.', 'multistep-checkout'));
    }
}

// Initialize the plugin
new Multistep_Checkout();