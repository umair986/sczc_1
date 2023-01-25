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
    add_action('wp_ajax_szcs_change_taxonomy_points', array($this, 'save_taxonomy_points_bulk'), 10, 2);
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

  // save points for bulk edit
  public function save_taxonomy_points_bulk()
  {

    if (
      isset($_REQUEST['points'])
      && isset($_REQUEST['delete_tags'])
      && is_array($_REQUEST['delete_tags'])
      && isset($_REQUEST['taxonomy'])
      && !empty($_REQUEST['taxonomy'])
      && isset($_REQUEST['nonce'])
      && wp_verify_nonce($_REQUEST['nonce'], 'szcs-coupon-nonce')
    ) {
      $points = $_REQUEST['points'];

      // if points is less than 0 or greater than 100 send error
      if (is_numeric($points) && ($points < 0 || $points > 100)) {
        wp_send_json(array('success' => false, 'message' => __('Points must be between 0 and 100', 'szcs-coupon')));
        exit();
      }
      $term_ids = $_REQUEST['delete_tags'];
      $taxonomy = $_REQUEST['taxonomy'];

      $meta_key = '';

      if ($taxonomy == 'product_cat') {
        $meta_key = 'szcs_cat_points_field';
      } elseif ($taxonomy == 'product_brand') {
        $meta_key = 'szcs_brand_points_field';
      }


      if ($meta_key != '') {
        foreach ($term_ids as $term_id) {
          $term = get_term($term_id, $taxonomy);
          if (!is_wp_error($term)) {
            update_term_meta($term_id, $meta_key, $points);
          }
        }
        wp_send_json(array('success' => true, 'message' => 'Points updated successfully', 'points' => $points, 'term_ids' => $term_ids));
      }
    } else {
      wp_send_json(array('success' => false, 'message' => __('Something went wrong.', 'szcs-coupon')));
    }
  }
}


SzCsCouponAJAX::instance();
