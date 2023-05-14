<?php

/**
 *
 * Coupon Transaction file.
 *
 * @package SzCsCoupon
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('SzCsCouponTransactoin')) {

  class SzCsCouponTransactoin
  {
    /**
     * The single instance of the class.
     *
     * @var SzCsCouponTransactoin
     * @since 1.0.12
     */
    protected static $_instance = null;

    /**
     * Transactions veriable
     *
     * @var array
     */
    protected static $_transactions = null;

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
      add_action('szcs_coupon_add_transaction', array($this, 'add_transaction'));
      //add_action('wp', array($this, 'schedule_expire_coupons'));
      //add_action('szcs_coupons_expired', array($this, 'mark_coupon_expired'));
    }

    private static function get_transactions()
    {
      if (is_null(self::$_transactions)) {
        self::$_instance->fetch_transactions();
      }
      return self::$_transactions;
    }

    public function fetch_transactions()
    {
      global $wpdb;
      self::$_transactions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}szcs_transaction_points", OBJECT);
    }

    public static function get_transactions_by_voucher_id($voucher_id)
    {
      return array_filter(self::get_transactions(), function ($transaction) use ($voucher_id) {
        return $transaction->voucher_id === $voucher_id;
      });
    }

    public static function get_number_of_claims_by_user($voucher_id, $user_id)
    {
      return array_filter(
        self::get_transactions(),
        function ($transaction) use ($voucher_id, $user_id) {
          return $transaction->voucher_id === $voucher_id && $transaction->user_id == $user_id;
        }
      );
    }

    public static function get_transactions_by_user_id($user_id)
    {
      return array_filter(self::get_transactions(), function ($transaction) use ($user_id) {
        return $transaction->user_id == $user_id;
      });
    }

    function add_transaction($args)
    {
      // parse_args
      $args = self::get_transaction_schema($args);

      // get global varibles
      global $wpdb, $szcs_coupon_wallet;

      $user_data = $szcs_coupon_wallet->get_data($args['user_id']);

      $user_args = array('user_id' => $args['user_id']);

      if ($user_data) { //#1 user already exist

        if (isset($args['closing_balance'])) { // directly want to set closing balance

          $user_args['wallet_points'] = (int) $args['closing_balance']; //new balance

          $diff = $args['closing_balance'] - $user_data->wallet_points; // get difference
          if ($diff >= 0) { //set diffrence either debit or credit
            $args['credit_points'] = (int) $diff;
          } else {
            $args['debit_points'] = (int) abs($diff);
          }
        } else if ($args['debit_points'] > 0) { // want to debit points
          $user_args['wallet_points'] = (int) $user_data->wallet_points - $args['debit_points']; //degrese balance by amount
        } else {
          $user_args['wallet_points'] = (int) $user_data->wallet_points + $args['credit_points']; //increase balance by amount
        }

        // update user table
        if ($args['debit_points'] > 0 || $args['credit_points'] > 0) {
          $wpdb->update("{$wpdb->prefix}szcs_user_points", $user_args, array('user_id' => $args['user_id']));
        }
      } else { //#2 new user
        if (isset($args['closing_balance'])) { // directly want to set closing balance

          $user_args['wallet_points'] = $args['closing_balance']; //set balance

          if ($args['closing_balance'] >= 0) {
            $args['credit_points'] = $args['closing_balance'];
          } else {
            $args['debit_points'] = abs($args['closing_balance']);
          }
        } else if ($args['debit_points'] > 0) {
          $user_args['wallet_points'] =  -$args['debit_points']; // set wallet balance equal to debit amount
        } else {
          $user_args['wallet_points'] = $args['credit_points']; // set wallet balance equal to credit amount
        }

        // insert into user table
        $wpdb->insert("{$wpdb->prefix}szcs_user_points", $user_args);
      }

      // set closing balance equal to wallet balance
      $args['closing_balance'] = $user_args['wallet_points'];

      // insert a new transaction
      $wpdb->insert("{$wpdb->prefix}szcs_transaction_points", $args);
    }

    private static function get_transaction_schema($args)
    {
      return wp_parse_args(
        $args,
        array(
          'user_id' => get_current_user_id(),
          'description' => '',
          'debit_points' => 0,
          'credit_points' => 0,
          'voucher_id' => null,
          'voucher_no' => null,
          'status' => null,
        )
      );
    }
  }
}

/**
 * Returns the main instance of SzCsCouponTransactoin.
 *
 * @since  1.0.12
 * @return SzCsCouponTransactoin
 */
function szcs_coupon_transaction()
{
  return SzCsCouponTransactoin::instance();
}

$GLOBALS['szcs_coupon_transaction'] = szcs_coupon_transaction();


//global $szcs_coupon_transaction;

//$szcs_coupon_voucher->print();
//wp_clear_scheduled_hook('szcs_coupons_expired');

//print_r(wp_get_scheduled_event('szcs_coupons_expired'));


// add points
// deducts points
// get number of claims per voucher
// get number of claims per user per voucher
// get transactions by a user