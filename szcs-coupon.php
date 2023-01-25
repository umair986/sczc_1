<?php

/**
 * @package SzCsCoupon
 */
/*
Plugin Name: Coupon
Description: A plugin for managing points system.
Version: 1.0.46
Requires at least: 5.0
Tested up to: 6.1
Requires PHP: 5.2
WC requires at least: 3.0
WC tested up to: 7.1
Author: szmazhr
Author URI: https://github.com/szmazhr
License: GPLv2 or later
Text Domain: szcs-coupon
Domain Path: /languages/

*/

// Make sure we don't expose any info if called directly
if (!defined('ABSPATH')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Define SZCS_COUPON_PLUGIN_FILE.
if (!defined('SZCS_COUPON_PLUGIN_FILE')) {
	define('SZCS_COUPON_PLUGIN_FILE', __FILE__);
}

// Define SZCS_COUPON_ABSPATH.
if (!defined('SZCS_COUPON_ABSPATH')) {
	define('SZCS_COUPON_ABSPATH', plugin_dir_path(__FILE__));
}

// Define SZCS_COUPON_PLUGIN_VERSION.
if (!defined('SZCS_COUPON_PLUGIN_VERSION')) {
	define('SZCS_COUPON_PLUGIN_VERSION', '1.0.46');
}

// include dependencies file.
if (!class_exists('SzCsCoupon_Dependencies')) {
	include_once SZCS_COUPON_ABSPATH . '/includes/class-szcs-coupon-dependencies.php';
}

// Include the main class.
if (!class_exists('SzCsCoupon')) {
	include_once SZCS_COUPON_ABSPATH . '/includes/class-szcs-coupon.php';
}
/**
 * Returns the main instance of SzCsCoupon.
 *
 * @since  1.0.0
 * @return SzCsCoupon
 */
function szcs_coupon()
{
	return SzCsCoupon::instance();
}

$GLOBALS['szcs_coupon'] = szcs_coupon();
