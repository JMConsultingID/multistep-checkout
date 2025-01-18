<?php
/**
 * Plugin Name: Custom Checkout Flow
 * Description: Custom WooCommerce checkout flow: checkout -> order-pay -> thank you.
 * Version: 1.0
 * Author: [Your Name]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Disable payment options on checkout page
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );

// Redirect to order-pay after checkout
add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order ) {
        // Set status order to Pending
        $order->update_status( 'pending', 'Checkout completed without payment.' );
        
        // Redirect to payment page
        wp_safe_redirect( $order->get_checkout_payment_url() );
        exit;
    }
}, 20 );
