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

// Redirect to order-pay after checkout
add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( $order && $order->get_status() === 'pending' ) {
        // Redirect to order-pay page
        wp_safe_redirect( $order->get_checkout_payment_url() );
        exit;
    }
}, 10 );

// Ensure orders are set to Pending status
add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    $order->set_status( 'pending' ); // Set the status to Pending
}, 10, 2 );
