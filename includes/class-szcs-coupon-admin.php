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
    /*
    add action to Initialize main admin menu in the dashboard
    */
    add_action('admin_menu', array($this, 'admin_menu'), 50);

    /*
    add action to enqueue admin styles and scripts
    */
    add_action('admin_enqueue_scripts', array($this, 'admin_scripts'), 10);
  }

  /**
   * Initialize main admin menu in the dashboard
   */
  public function admin_menu()
  {


    $slug =  current_user_can('manage_woocommerce') ? 'szcs-coupon-users' : 'szcs-coupon-export';
    add_menu_page(
      __('Coupons', 'szcs-coupon'),
      __('Coupons', 'szcs-coupon'),
      'export_vouchers',
      $slug,
      '',
      'dashicons-tickets-alt',
      25
    );

    /*
    do action after main admin menu in the dashboard initialized
    */
    do_action('szcs_admin_menu', $slug);
  }



  /**
   * Register and enqueue admin styles and scripts
   *
   */
  public function admin_scripts()
  {

    $screen = get_current_screen();
    $screen_id = $screen ? $screen->id : '';

    $customVar = array(
      'siteurl' => get_option('siteurl'),
    );

    if (is_admin()) {
      $customVar = array(
        'siteurl' => get_option('siteurl'),
        'couponGeneratorUrl' => admin_url('admin.php?page=szcs-coupon-generator'),
        'screenId' => $screen_id,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('szcs-coupon-nonce'),
        'autoSelectCategory' => get_option('szcs-coupon_options')['szcs-coupon-auto-parent-category'] == '1' ? true : false,
      );
    }

    wp_register_style('szcs_coupons_admin', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/css/admin/szcs-coupon.css', array(), SZCS_COUPON_PLUGIN_VERSION, 'all');
    wp_register_script('szcs_coupons_admin', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/js/admin/szcs-coupon.js', array('jquery'), SZCS_COUPON_PLUGIN_VERSION, false);
    wp_localize_script('szcs_coupons_admin', 'SZCS_VARS', $customVar);
    if (in_array($screen_id, array('coupons_page_szcs-coupon-client', 'szcs_coupons_code', 'edit-szcs_coupons_code', 'coupons_page_szcs-coupon-generator', 'edit-product_cat', 'edit-product_brand', 'product', 'coupons_page_szcs-coupon-export', 'toplevel_page_szcs-coupon-export', 'edit-product', 'user-edit'), true)) {
      wp_enqueue_script('szcs_coupons_admin');
      wp_enqueue_style('szcs_coupons_admin');
    }
  }
}
SzCsCouponAdmin::instance();
