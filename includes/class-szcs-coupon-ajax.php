<?php

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponAJAX
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponAJAX
   * @since 1.0.27
   */
  protected static $_instance = null;

  /**
   * Main instance
   *
   * @return class object
   */

  protected static $_message = array();

  public static function instance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * Class constructor
   */
  public function __construct()
  {
    add_action('wp_ajax_szcs-coupon-export', array($this, 'export_coupons'));
  }

  public function export_coupons()
  {
    if (is_admin()) {
      if (isset($_REQUEST['post'])) {
        global $szcs_coupon_voucher;
        $vouchers = $szcs_coupon_voucher->get_vouchers_by_post_id($_REQUEST['post']);

        wp_send_json(array_map(function ($v) {
          unset($v->post_id);
          return $v;
        }, $vouchers));
      } else {
        wp_send_json('Please select voucher(s) to export.', 400);
      }
    };
  }
}


SzCsCouponAJAX::instance();
