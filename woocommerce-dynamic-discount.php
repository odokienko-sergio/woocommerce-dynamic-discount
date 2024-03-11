<?php
/**
 * Plugin Name: WooCommerce Dynamic Discount
 * Plugin URI: http://example.com/woocommerce-dynamic-discount
 * Description: Apply dynamic discounts in WooCommerce based on the number of products in the cart.
 * Version: 1.0.0
 * Author: Serhii Odokiienko
 * Author URI: http://example.com
 * Text Domain: woocommerce-dynamic-discount
 */

if (!defined('WPINC')) {
	die;
}

$class_file_path = plugin_dir_path(__FILE__) . 'includes/class-wdd-wc-dynamic-discount.php';

if (file_exists($class_file_path)) {
	include_once $class_file_path;
	
	if (class_exists('WDD_WC_Dynamic_Discount')) {
		function wdd_run_wc_dynamic_discount() {
			$plugin = new WDD_WC_Dynamic_Discount();
			$plugin->run();
		}
		wdd_run_wc_dynamic_discount();
	} else {
		error_log('WDD_WC_Dynamic_Discount class does not exist after including the file.');
	}
} else {
	error_log('WDD_WC_Dynamic_Discount file does not exist: ' . $class_file_path);
}
