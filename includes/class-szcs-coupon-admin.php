<?php

/**
 * Coupon main admin menu file
 *
 * @package SzCsCoupon
 */
class SzCsCouponAdmin
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponAdmin
   * @since 1.1.10
   */
  protected static $_instance = null;

  /**
   * Main instance
   *
   * @return class object
   */

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
    add_action('admin_menu', array($this, 'admin_menu'), 50);
    add_action('admin_enqueue_scripts', array($this, 'admin_scripts'), 10);
  }

  /**
   * Init admin menu
   */
  public function admin_menu()
  {
    $slug = 'szcs-coupon-users';
    add_menu_page(
      __('Coupons', 'szcs-coupon'),
      __('Coupons', 'szcs-coupon'),
      get_szcs_coupon_user_capability(),
      $slug,
      '',
      'dashicons-tickets-alt',
      25
    );
    do_action('szcs_admin_menu', $slug);
  }



  /**
   * Register and enqueue admin styles and scripts
   *
   * @global type $post
   */
  public function admin_scripts()
  {
    $screen = get_current_screen();
    wp_register_style('szcs_coupons_admin', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/css/admin/szcs-coupon.css', array(), SZCS_COUPON_PLUGIN_VERSION, 'all');
    wp_register_script('szcs_coupons_admin', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/js/admin/szcs-coupon.js', array('jquery'), SZCS_COUPON_PLUGIN_VERSION, false);
    wp_localize_script('szcs_coupons_admin', 'SZCS_VARS', array('siteurl' => get_option('siteurl'), 'couponGeneratorUrl' => admin_url('admin.php?page=szcs-coupon-generator')));
    $screen_id = $screen ? $screen->id : '';
    if (in_array($screen_id, array('szcs_coupons_code', 'edit-szcs_coupons_code', 'coupons_page_szcs-coupon-generator'), true)) {
      wp_enqueue_script('szcs_coupons_admin');
      wp_enqueue_style('szcs_coupons_admin');
    }
  }
}
SzCsCouponAdmin::instance();
