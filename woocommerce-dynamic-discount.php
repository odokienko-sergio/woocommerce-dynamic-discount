<?php
/**
 * Plugin Name: WooCommerce Dynamic Discount
 * Plugin URI: http://example.com/woocommerce-dynamic-discount
 * Description: Apply dynamic discounts in WooCommerce based on the number of products in the cart.
 * Version: 1.0.0
 * Author: Serhii Odokiienko
 * Author URI: http://example.com
 * WC requires at least: 3.0
 * WC tested up to: 5.5
 * Text Domain: woocommerce-dynamic-discount
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$class_file_path = plugin_dir_path( __FILE__ ) . 'includes/class-wc-dynamic-discount.php';
if ( ! class_exists( 'WC_Dynamic_Discount' ) && file_exists( $class_file_path ) ) {
	include_once $class_file_path;
}

function run_wc_dynamic_discount() {
	$plugin = new WC_Dynamic_Discount();
	$plugin->run();
}
run_wc_dynamic_discount();
