<?php
/**
 * Plugin Name: Custom Checkout Flow
 * Description: Custom WooCommerce checkout flow: checkout -> order-pay -> thank you.
 * Version: 1.1
 * Author: [Your Name]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Disable payment options on checkout page
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
add_filter( 'woocommerce_cart_needs_payment_before_order_pay', '__return_false' );

// Remove payment validation error
add_action( 'woocommerce_after_checkout_validation', function( $data, $errors ) {
    if ( isset( $errors->errors['no_payment_method'] ) ) {
        unset( $errors->errors['no_payment_method'] );
    }
}, 10, 2 );

// Set order status to Pending
add_action( 'woocommerce_checkout_create_order', function( $order ) {
    $order->set_status( 'pending' );
}, 20 );

// Redirect to order-pay after checkout
add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order ) {
        // Redirect to payment page
        wp_safe_redirect( $order->get_checkout_payment_url() );
        exit;
    }
}, 20 );

// Remove payment gateways on checkout page
add_filter( 'woocommerce_available_payment_gateways', function( $available_gateways ) {
    if ( is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
        return []; // Remove all gateways on the checkout page
    }
    return $available_gateways;
} );
