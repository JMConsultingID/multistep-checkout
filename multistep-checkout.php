<?php
/**
 * Plugin Name: Custom Checkout Flow
 * Description: Custom WooCommerce checkout flow: checkout -> order-pay -> thank you.
 * Version: 1.3
 * Author: [Your Name]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Disable payment options on checkout page
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );

// Force order status to Pending after checkout
add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    $order->set_status( 'pending' ); // Ensure the order is set to pending
}, 10, 2 );

// Prevent automatic status change to Processing
add_filter( 'woocommerce_payment_complete_order_status', function( $status, $order_id, $order ) {
    return 'pending'; // Ensure status stays pending
}, 10, 3 );

// Redirect to order-pay page after checkout
add_action( 'woocommerce_thankyou', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && $order->get_status() === 'pending' ) {
        // Redirect to order-pay page
        wp_safe_redirect( $order->get_checkout_payment_url() );
        exit;
    }
}, 10 );