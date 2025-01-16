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
        // Remove default payment fields and methods
        add_action('init', [$this, 'remove_payment_methods']);
        
        // Modify checkout fields and process
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        add_action('woocommerce_checkout_process', [$this, 'modify_checkout_process']);
        
        // Handle order creation and payment
        add_action('woocommerce_checkout_create_order', [$this, 'set_order_payment_method']);
        add_action('woocommerce_checkout_order_processed', [$this, 'handle_order_redirect'], 10, 3);
        
        // Bypass payment validations
        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_order_needs_payment', '__return_true');
        add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');
        
        // Allow pending payment orders
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'add_pending_to_valid_order_statuses']);
        
        // Modify checkout form elements
        add_action('woocommerce_review_order_before_payment', [$this, 'add_coupon_form']);
        add_filter('woocommerce_order_button_text', [$this, 'change_place_order_button_text']);
        add_action('woocommerce_checkout_before_order_review', [$this, 'add_order_review_heading']);
        add_action('woocommerce_checkout_after_customer_details', [$this, 'add_custom_checkout_buttons']);
        
        // Add checkout steps
        add_action('woocommerce_before_checkout_form', [$this, 'add_checkout_steps'], 5);
        
        // Add custom CSS and JS
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_scripts']);
    }

    public function remove_payment_methods() {
        remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
        remove_action('woocommerce_checkout_process', 'woocommerce_checkout_process_payment');
    }

    public function customize_checkout_fields($fields) {
        // Remove unnecessary fields
        unset($fields['payment']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        return $fields;
    }

    public function modify_checkout_process() {
        // Validate required fields
        $required_fields = [
            'billing_first_name' => __('First name', 'woocommerce'),
            'billing_last_name'  => __('Last name', 'woocommerce'),
            'billing_email'      => __('Email address', 'woocommerce'),
            'billing_phone'      => __('Phone', 'woocommerce'),
            'billing_address_1'  => __('Address', 'woocommerce'),
            'billing_city'       => __('City', 'woocommerce'),
            'billing_postcode'   => __('Postcode', 'woocommerce'),
            'billing_country'    => __('Country', 'woocommerce')
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                wc_add_notice(sprintf(__('%s is required.', 'woocommerce'), $label), 'error');
            }
        }
    }

    public function set_order_payment_method($order) {
        $order->set_payment_method('');
        $order->set_payment_method_title('');
        $order->set_status('pending', 'Order created via multi-step checkout.');
    }

    public function handle_order_redirect($order_id, $posted_data, $order) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Clear session and cart
        WC()->session->set('order_awaiting_payment', $order_id);
        WC()->cart->empty_cart();

        $pay_url = $order->get_checkout_payment_url(true);
        if (!empty($pay_url)) {
            wp_redirect($pay_url);
            exit;
        }
    }

    public function add_pending_to_valid_order_statuses($statuses) {
        if (!in_array('pending', $statuses)) {
            $statuses[] = 'pending';
        }
        return $statuses;
    }

    public function add_coupon_form() {
        if (wc_coupons_enabled()) {
            ?>
            <div class="coupon-form-wrapper">
                <h3><?php esc_html_e('Have a coupon?', 'woocommerce'); ?></h3>
                <div class="coupon-form">
                    <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" />
                    <button type="button" class="button" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
                </div>
            </div>
            <?php
        }
    }

    public function change_place_order_button_text($button_text) {
        return __('Create Order', 'woocommerce');
    }

    public function add_order_review_heading() {
        echo '<h3>' . __('Your order', 'woocommerce') . '</h3>';
    }

    public function add_custom_checkout_buttons() {
        ?>
        <div class="custom-checkout-buttons">
            <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Create Order"><?php esc_html_e('Create Order', 'woocommerce'); ?></button>
        </div>
        <?php
    }

    public function add_checkout_steps() {
        ?>
        <div class="multistep-checkout-steps">
            <div class="checkout-step active">
                <span class="step-number">1</span>
                <span class="step-title"><?php esc_html_e('Billing Details', 'woocommerce'); ?></span>
            </div>
            <div class="checkout-step">
                <span class="step-number">2</span>
                <span class="step-title"><?php esc_html_e('Review & Pay', 'woocommerce'); ?></span>
            </div>
            <div class="checkout-step">
                <span class="step-number">3</span>
                <span class="step-title"><?php esc_html_e('Payment', 'woocommerce'); ?></span>
            </div>
        </div>
        <?php
    }

    public function enqueue_custom_scripts() {
        if (!is_checkout()) {
            return;
        }

        // Enqueue custom CSS
        wp_add_inline_style('woocommerce-layout', $this->get_custom_css());

        // Enqueue custom JS
        wp_add_inline_script('woocommerce', $this->get_custom_js());
    }

    private function get_custom_css() {
        return "
            .woocommerce-checkout-payment {
                display: none !important;
            }
            
            .multistep-checkout-steps {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                padding: 20px 0;
                border-bottom: 1px solid #eee;
            }
            
            .checkout-step {
                flex: 1;
                text-align: center;
                padding: 10px;
                position: relative;
            }
            
            .checkout-step.active {
                color: #2271b1;
            }
            
            .step-number {
                display: inline-block;
                width: 30px;
                height: 30px;
                line-height: 30px;
                border-radius: 50%;
                background: #f0f0f0;
                margin-right: 10px;
            }
            
            .checkout-step.active .step-number {
                background: #2271b1;
                color: white;
            }
            
            .coupon-form-wrapper {
                background: #f8f8f8;
                padding: 20px;
                border-radius: 4px;
                margin: 20px 0;
            }
            
            .coupon-form input {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .coupon-form button {
                width: 100%;
            }
            
            .custom-checkout-buttons {
                margin: 20px 0;
            }
            
            #place_order {
                width: 100%;
                padding: 15px;
                font-size: 16px;
                background-color: #2271b1;
                color: white;
                font-weight: bold;
            }
            
            #place_order:hover {
                background-color: #185a8c;
            }
        ";
    }

    private function get_custom_js() {
        return "
            jQuery(document).ready(function($) {
                // Handle coupon application
                $('button[name=\"apply_coupon\"]').on('click', function(e) {
                    e.preventDefault();
                    var coupon_code = $('#coupon_code').val();
                    if (coupon_code) {
                        $('.woocommerce-error, .woocommerce-message').remove();
                        $.ajax({
                            type: 'POST',
                            url: wc_checkout_params.ajax_url,
                            data: {
                                action: 'apply_coupon',
                                security: wc_checkout_params.apply_coupon_nonce,
                                coupon_code: coupon_code
                            },
                            success: function(response) {
                                $('.woocommerce-checkout').before(response);
                                $(document.body).trigger('update_checkout');
                                $('#coupon_code').val('');
                            }
                        });
                    }
                });
            });
        ";
    }
}

// Initialize the plugin
function init_multistep_checkout() {
    if (class_exists('WooCommerce')) {
        new Multistep_Checkout();
    }
}

add_action('plugins_loaded', 'init_multistep_checkout');