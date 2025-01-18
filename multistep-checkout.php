<?php
/**
 * Plugin Name: Custom Checkout Flow
 * Description: Custom WooCommerce checkout flow: checkout -> order-pay -> thank you, with status adjustments for free orders.
 * Version: 1.5
 * Author: [Your Name]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Disable payment options on checkout page
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );

// Set order status based on total
add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    if ( $order->get_total() == 0 ) {
        // Automatically set free orders to completed
        $order->set_status( 'completed' );
    } else {
        // Set paid orders to pending
        $order->set_status( 'pending' );
    }
}, 10, 2 );

// Redirect after checkout based on order total
add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );

    if ( $order->get_total() == 0 ) {
        // Free orders should go directly to Thank You page
        return;
    }

    if ( $order->get_status() === 'pending' ) {
        // Redirect paid orders to order-pay page
        wp_safe_redirect( $order->get_checkout_payment_url() );
        exit;
    }
}, 10 );

// Ensure payment complete status respects free orders
add_filter( 'woocommerce_payment_complete_order_status', function( $status, $order_id, $order ) {
    if ( $order->get_total() == 0 ) {
        // Free orders should always be completed
        return 'completed';
    }
    return $status; // Default behavior for paid orders
}, 10, 3 );
