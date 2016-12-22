<?php
/*
	Plugin Name: WooCommerce PayU India Gateway
	Plugin URI: http://woothemes.com/woocommerce
	Description: Extends WooCommerce. Provides a <a href="http://www.payu.in/">PayU India</a> gateway for WooCommerce.
	Version: 1.6.0
	Author: Daniel Dudzic
	Author URI: http://danieldudzic.com/
	Requires at least: 3.8
	Tested up to: 3.8
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '2e56bbe1b8877193b170f472b0451fbd', '18707' );

// Init PayU IN Gateway after WooCommerce has loaded
add_action( 'plugins_loaded', 'init_payu_in_gateway', 0 );


/**
 * init_payu_in_gateway function.
 *
 * @description Initializes the gateway.
 * @access public
 * @return void
 */
function init_payu_in_gateway() {
	// If the WooCommerce payment gateway class is not available, do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	// Localization
	load_plugin_textdomain('woocommerce_payu_in', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

	require_once( plugin_basename( 'classes/payu_in.class.php' ) );

	add_filter( 'woocommerce_payment_gateways', 'add_payu_in_gateway' );

	/**
	* add_gateway()
	*
	* Register the gateway within WooCommerce.
	*
	* @since 1.0.0
	*/
	function add_payu_in_gateway($methods) {
		$methods[] = 'WC_Gateway_Payu_In'; return $methods;
	}

}

// Add the Indian Rupee to the currency list
add_filter( 'woocommerce_currencies', 'woocommerce_add_indian_rupee' );

function woocommerce_add_indian_rupee( $currencies ) {
     $currencies['INR'] = __( 'Indian Rupee', 'woocommerce' );
     return $currencies;
}

add_filter('woocommerce_currency_symbol', 'woocommerce_add_indian_rupee_currency_symbol', 10, 2);

function woocommerce_add_indian_rupee_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'INR': $currency_symbol = 'Rs.'; break;
     }
     return $currency_symbol;
}
