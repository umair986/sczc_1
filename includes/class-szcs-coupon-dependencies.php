<?php

/**
 * Coupon plugin dependency file
 *
 * @package SzCsCoupon
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}
if (!class_exists('SzCsCoupon_Dependencies')) {
  /**
   * Coupon dependency class.
   */
  class SzCsCoupon_Dependencies
  {
    /**
     * Active plugins veriable
     *
     * @var array
     */
    private static $active_plugins;
    /**
     * Class constructor.
     *
     * @return void
     */
    public static function init()
    {
      self::$active_plugins = (array) get_option('active_plugins', array());
      if (is_multisite()) {
        self::$active_plugins = array_merge(self::$active_plugins, get_site_option('active_sitewide_plugins', array()));
      }
    }

    /**
     * Check woocommerce exist
     *
     * @return Boolean
     */
    public static function woocommerce_active_check()
    {
      if (!self::$active_plugins) {
        self::init();
      }
      return in_array('woocommerce/woocommerce.php', self::$active_plugins, true) || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins);
    }

    /**
     * Check if woocommerce active
     *
     * @return Boolean
     */
    public static function is_woocommerce_active()
    {
      return self::woocommerce_active_check();
    }

    /**
     * Check woocommerce exist
     *
     * @return Boolean
     */
    public static function szcs_coupon_active_check()
    {
      if (!self::$active_plugins) {
        self::init();
      }
      return in_array('szcs-coupon/szcs-coupon.php', self::$active_plugins, true) || array_key_exists('szcs-coupon/szcs-coupon.php', self::$active_plugins);
    }

    /**
     * Check if SzCs Coupon active
     *
     * @return Boolean
     */
    public static function is_szcs_coupon_active()
    {
      return self::szcs_coupon_active_check();
    }
  }
}
