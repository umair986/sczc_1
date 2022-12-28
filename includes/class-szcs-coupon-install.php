<?php

/**
 * SzCsCoupon plugin installation file
 *
 * @package SzCsCoupon
 */

if (!defined('ABSPATH')) {
  exit;
}
/**
 * SzCsCoupon_Install Class
 */
class SzCsCoupon_Install
{
  /**
   * Plugin install
   *
   * @return void
   */
  public static function install()
  {
    if (!is_blog_installed()) {
      return;
    }
    self::create_tables();
  }
  /**
   * Plugins table creation
   *
   * @global object $wpdb
   */
  private static function create_tables()
  {
    global $wpdb;
    $wpdb->hide_errors();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta(self::get_schema());
  }

  /**
   * Plugin table schema
   *
   * @global object $wpdb
   * @return string
   */
  public static function get_schema()
  {
    global $wpdb;
    $collate = '';

    if ($wpdb->has_cap('collation')) {
      $collate = $wpdb->get_charset_collate();
    }
    $tables = [
      "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}szcs_transaction_points (
            trans_point_id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            description text NOT NULL,
            debit_points float NOT NULL DEFAULT 0,
            credit_points float NOT NULL DEFAULT 0,
            closing_balance float NOT NULL,
            voucher_id int(11) NULL,
            voucher_no varchar(255) NULL,
            order_dateTime datetime NOT NULL DEFAULT current_timestamp(),
            status varchar(50) NULL,
            PRIMARY KEY (trans_point_id)
          ) $collate",
      "CREATE TABLE {$wpdb->base_prefix}szcs_user_points (
            user_point_id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            wallet_points float NOT NULL,
            status varchar(255) NOT NULL DEFAULT 'active',
            PRIMARY KEY (user_point_id)
          ) $collate",
      "CREATE TABLE {$wpdb->base_prefix}szcs_voucher_points (
            voucher_id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            voucher_no varchar(255) NOT NULL,
            voucher_amount int(11) NOT NULL,
            create_date date NOT NULL DEFAULT current_timestamp(),
            expiry_date date NOT NULL,
            usage_limit_per_voucher int(11) NOT NULL DEFAULT 1,
            usage_limit_per_user int(11) NOT NULL DEFAULT 1,
            status varchar(50) NOT NULL DEFAULT 'active',
            PRIMARY KEY (voucher_id)
          ) $collate;"
    ];

    return $tables;
  }
}
