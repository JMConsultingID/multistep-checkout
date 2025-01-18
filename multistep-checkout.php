<?php
/**
 * Plugin Name: Custom Checkout Flow
 * Description: Custom WooCommerce checkout flow: checkout -> order-pay -> thank you.
 * Version: 1.4
 * Author: [Your Name]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Disable payment options on checkout page
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );

// Set order status to pending only for paid orders
add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    if ( $order->get_total() > 0 ) {
        $order->set_status( 'pending' ); // Paid orders start as pending
    }
}, 10, 2 );

// Redirect to appropriate page after checkout
add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );

    if ( $order->get_total() == 0 ) {
        // If order total is 0, let WooCommerce handle the flow to Thank You page
        return;
    }

    if ( $order->get_status() === 'pending' ) {
        // Redirect paid orders to order-pay page
        wp_safe_redirect( $order->get_checkout_payment_url() );
        exit;
    }
}, 10 );

// Ensure completed orders remain completed
add_filter( 'woocommerce_payment_complete_order_status', function( $status, $order_id, $order ) {
    // Only adjust orders that are not already completed
    if ( $order->get_status() === 'pending' ) {
        return 'pending'; // Keep pending for unpaid orders
    }

    return $status; // Return default status for other cases
}, 10, 3 );
