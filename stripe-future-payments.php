<?php
/**
 * Plugin Name: Stripe Future Payments
 * Plugin URI: https://github.com/DevWael/stripe-future-payments
 * Description: Capture credit card payments later on order completion
 * Author: Ahmad Wael
 * Author URI: https://github.com/devwael
 * Version: 1.0
 * Text Domain: sfp
 * Domain Path: /languages
 */

require plugin_dir_path( __FILE__ ) . 'helpers.php';

/**
 * Stop the original capture functionality of the stripe woocommerce plugin from being hooked on order processing and order complete events.
 */
add_action(
	'plugins_loaded',
	function() {
		sfp_remove_class_filter( 'woocommerce_order_status_processing', 'WC_Stripe_Order_Handler', 'capture_payment' );
		sfp_remove_class_filter( 'woocommerce_order_status_completed', 'WC_Stripe_Order_Handler', 'capture_payment' );
	},
	20
);

add_action( 'woocommerce_order_status_completed', 'sfp_order_release_payment' );
/**
 * Charge the card upon order completion.
 *
 * @param int $order_id order id.
 */
function sfp_order_release_payment( $order_id ) {
	if ( class_exists( 'WC_Stripe_Pre_Orders_Compat' ) && class_exists( 'WooCommerce' ) ) {
		$order = wc_get_order( $order_id );
		if ( 'stripe' === $order->get_payment_method() ) {
			$charge   = $order->get_transaction_id(); // get the stripe transaction id.
			$captured = $order->get_meta( '_stripe_charge_captured', true ); // check if the payment is already captured or not.
			if ( $charge && 'no' === $captured ) {
				$payment_process = new WC_Stripe_Pre_Orders_Compat();
				$payment_process->process_pre_order_release_payment( $order );
			}
		}
	}
}
