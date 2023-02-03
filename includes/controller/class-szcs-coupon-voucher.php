<?php

/**
 *
 * Coupon Voucher file.
 *
 * @package SzCsCoupon
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('SzCsCouponVoucher')) {

  class SzCsCouponVoucher
  {
    /**
     * The single instance of the class.
     *
     * @var SzCsCouponVoucher
     * @since 1.1.2
     */
    protected static $_instance = null;

    /**
     * Vouchers veriable
     *
     * @var array
     */
    protected static $_vouchers = null;

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
      add_action('szcs_save_coupon', array($this, 'fetch_vouchers'));
      add_action('szcs_coupon_expired', array($this, 'mark_coupon_expired'));
      add_action('szcs_coupon_create_voucher', array($this, 'create_voucher'), 10, 3);
      add_action('szcs_coupon_create_vouchers', array($this, 'create_vouchers'), 10, 4);
    }

    private static function get_vouchers()
    {
      if (is_null(self::$_vouchers)) {
        self::$_instance->fetch_vouchers();
      }
      return self::$_vouchers;
    }

    public function fetch_vouchers()
    {
      global $wpdb;
      self::$_vouchers = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}szcs_voucher_points
        LEFT JOIN {$wpdb->prefix}szcs_voucher_batch
        ON {$wpdb->prefix}szcs_voucher_points.batch_id={$wpdb->prefix}szcs_voucher_batch.batch_id", OBJECT);
    }

    public static function get_voucher_by_post_id($post_id)
    {
      $index = array_search($post_id, array_column(self::get_vouchers(), 'post_id'));
      return $index !== false ? self::get_vouchers()[$index] : false;
    }

    public static function get_vouchers_by_post_id($post_ids)
    {
      return array_filter(self::get_vouchers(), function ($v) use ($post_ids) {
        return in_array($v->post_id, $post_ids);
      });
    }

    public static function get_vouchers_by_batch_id($batch_id)
    {

      $vouchers = array_filter(self::get_vouchers(), function ($v) use ($batch_id) {
        if ($v->batch_id === $batch_id) {
          return true;
        }
        return false;
      });

      if (count($vouchers) && (current_user_can('manage_woocommerce') || array_values($vouchers)[0]->vendor_id == get_current_user_id())) {
        return $vouchers;
      }

      return false;
    }

    public static function get_voucher($code)
    {
      $found = array_values(array_filter(self::get_vouchers(), function ($v) use ($code) {
        if ($v->voucher_no === strtoupper($code)) {
          return true;
        }
        return false;
      }));
      $active = array();

      switch (count($found)) {
        case 0:
          return false;
        case 1:
          return $found[0];
        default:
          $active = array_values(array_filter($found, function ($v) {
            if ($v->status === 'active') {
              return true;
            }
            return false;
          }));
      }
      if (count($active)) {
        return $active[0];
      }
      //$index = array_search(strtoupper($code), array_column(array_reverse(self::get_vouchers(), true), 'voucher_no'));
      return $found[0];
      //$index !== false ? self::get_vouchers()[$index] : false;
    }

    private static function code_exist($code)
    {
      return !!array_search($code, array_column(self::get_vouchers(), 'voucher_no'));
    }

    public static function get_new_code($prefix = '000')
    {
      $prefix = str_split($prefix, 3)[0];
      $shortby = 3 - strlen($prefix);
      $prefix = $shortby ? $prefix . str_repeat("0", $shortby) : $prefix;
      do {
        $bytes = random_bytes(4);
        $code = $prefix . '0' . bin2hex($bytes);
        $is_exist = self::code_exist($code);
      } while ($is_exist);

      return strtoupper($code);
    }

    public static function create_voucher($post_id, $voucher_amount, $args)
    {
      global $wpdb;

      $isExist = array_search($post_id, array_column(self::get_vouchers(), 'post_id')) !== false;

      if ($isExist) {
        $wpdb->update("{$wpdb->base_prefix}szcs_voucher_points", $args, array('post_id' => $post_id));
      } else {
        $args = self::get_voucher_schema($post_id, $voucher_amount, $args);
        unset($args['status']);
        $wpdb->insert("{$wpdb->base_prefix}szcs_voucher_points", $args);
      }

      do_action('szcs_save_coupon');
    }

    public static function create_vouchers($voucher_amount, $count, $args, $vendor = false)
    {
      global $wpdb;
      $prefix = isset($args['prefix']) ? $args['prefix'] : '000';
      $args['status'] = 'active';
      if ($vendor) {
        $wpdb->insert("{$wpdb->base_prefix}szcs_voucher_batch", array(
          'vendor_id' => $vendor
        ));
        $args['batch_id'] = $wpdb->insert_id;
        update_option('szcs_voucher_batch_id', $args['batch_id']);
      }
      for ($i = 0; $i < $count; $i++) {
        $args['voucher_no'] = self::get_new_code($prefix);
        $code_post = array(
          'post_title'    => $args['voucher_no'],
          'post_status'   => 'publish',
          'post_type'     => 'szcs_coupons_code'
        );
        $post_id = wp_insert_post($code_post);
        self::create_voucher($post_id, $voucher_amount, $args);
      }
    }

    private static function get_voucher_schema($post_id, $voucher_amount, $args)
    {
      if (!isset($args['expiry_date'])) {
        $datetime = new DateTime('tomorrow');
        $args['expiry_date'] = $datetime->format('Y-m-d');
      }

      if (!isset($args['voucher_no']) && isset($args['prefix'])) {
        $args['voucher_no'] = self::get_new_code($args['prefix']);
      }

      if (isset($args['prefix'])) {
        unset($args['prefix']);
      }

      return wp_parse_args(
        $args,
        array(
          'post_id' => $post_id,
          'voucher_no' => self::get_new_code(),
          'voucher_amount' => $voucher_amount,
          'usage_limit_per_voucher' => 1,
          'usage_limit_per_user' => 1,
          'batch_id' => null,
        )
      );
    }

    function mark_coupon_expired()
    {
      $date = date('Y-m-d');
      $expired = array_values(array_filter(self::get_vouchers(), function ($x) use ($date) {
        return $x->expiry_date < $date && $x->status === 'active';
      }));

      foreach ($expired as $voucher) {
        wp_update_post(array(
          'ID' => $voucher->post_id,
          'post_status' => 'expired',
        ));
      }
    }

    /**
     *
     * if valid return status & amount
     * else array status & message
     */
    public function validate_voucher($code, $id = '', $mark_expired = false)
    {
      $voucher = self::get_voucher($code);
      if ($voucher) {
        switch ($voucher->status) {
          case 'active':
          case 'publish':
            if ($voucher->expiry_date < date('Y-m-d')) {
              if ($mark_expired) {
                do_action('szcs_coupon_expired');
              }
              return array('error', 'Error', 'Voucher is already expired');
            }
            return array('valid', $voucher);
          case 'expired':
            return array('error', 'Error', 'Voucher is already expired');

          default:
            return array('error', 'Error', 'Voucher is not valid');
        }
      } else {
        return array('error', 'Error', 'Voucher Number is not valid');
      }
    }

    public function print()
    {
      $date = date('Y-m-d');
      //self::$_instance->fetch_vouchers();
      print_r(self::get_vouchers());
      //print_r(date("h:i:sa"));
    }
  }
}

/**
 * Returns the main instance of SzCsCouponVoucher.
 *
 * @since  1.0.3
 * @return SzCsCouponVoucher
 */
function szcs_coupon_voucher()
{
  return SzCsCouponVoucher::instance();
}

$GLOBALS['szcs_coupon_voucher'] = szcs_coupon_voucher();


global $szcs_coupon_voucher;

//$szcs_coupon_voucher->get_vouchers_by_post_id();
//wp_clear_scheduled_hook('szcs_coupons_expired');

//print_r(wp_get_scheduled_event('szcs_coupons_expired'));
