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

        // Enqueue Bootstrap CSS and JS
        add_action('wp_enqueue_scripts', [$this, 'enqueue_bootstrap']);

        // Replace default WooCommerce checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'unset_default_checkout_fields']);

        // Render custom checkout form
        add_action('woocommerce_checkout_billing', [$this, 'render_custom_billing_form']);

        // Process custom billing form data
        add_action('woocommerce_checkout_process', [$this, 'validate_custom_billing_form']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_custom_billing_data']);
    }

    /**
     * Enqueue Bootstrap CSS and JS
     */
    public function enqueue_bootstrap() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css', [], '5.3.0-alpha3');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0-alpha3', true);
    }

    /**
     * Unset default WooCommerce checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function unset_default_checkout_fields($fields) {
        unset($fields['billing']);
        return $fields;
    }

    /**
     * Render custom billing form
     */
    public function render_custom_billing_form() {
        include plugin_dir_path(__FILE__) . 'templates/custom-billing-form.php';
    }

    /**
     * Validate custom billing form data
     */
    public function validate_custom_billing_form() {
        if (empty($_POST['first_name'])) {
            wc_add_notice(__('Please enter your first name.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['last_name'])) {
            wc_add_notice(__('Please enter your last name.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['email']) || !is_email($_POST['email'])) {
            wc_add_notice(__('Please enter a valid email address.', 'multistep-checkout'), 'error');
        }
        if (empty($_POST['phone'])) {
            wc_add_notice(__('Please enter your phone number.', 'multistep-checkout'), 'error');
        }
    }

    /**
     * Save custom billing form data to order meta
     *
     * @param int $order_id
     */
    public function save_custom_billing_data($order_id) {
        if (!empty($_POST['first_name'])) {
            update_post_meta($order_id, '_billing_first_name', sanitize_text_field($_POST['first_name']));
        }
        if (!empty($_POST['last_name'])) {
            update_post_meta($order_id, '_billing_last_name', sanitize_text_field($_POST['last_name']));
        }
        if (!empty($_POST['email'])) {
            update_post_meta($order_id, '_billing_email', sanitize_email($_POST['email']));
        }
        if (!empty($_POST['phone'])) {
            update_post_meta($order_id, '_billing_phone', sanitize_text_field($_POST['phone']));
        }
        if (!empty($_POST['address'])) {
            update_post_meta($order_id, '_billing_address_1', sanitize_text_field($_POST['address']));
        }
        if (!empty($_POST['country'])) {
            update_post_meta($order_id, '_billing_country', sanitize_text_field($_POST['country']));
        }
        if (!empty($_POST['city'])) {
            update_post_meta($order_id, '_billing_city', sanitize_text_field($_POST['city']));
        }
        if (!empty($_POST['postal_code'])) {
            update_post_meta($order_id, '_billing_postcode', sanitize_text_field($_POST['postal_code']));
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
}

// Initialize the plugin
new Multistep_Checkout();
