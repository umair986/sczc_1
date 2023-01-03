<?php

/**
 * Utility file for this plugin.
 *
 * @package SzCsCoupon
 */


if (!function_exists('get_szcs_coupon_user_capability')) {
  /**
   * Coupon user admin capability.
   *
   * @return string
   */
  function get_szcs_coupon_user_capability()
  {
    return apply_filters('szcs_coupon_user_capability', 'manage_woocommerce');
  }
}

if (!function_exists('szcs_redirect')) {
  function szcs_redirect($url, $args = array())
  {
    wp_redirect(esc_url_raw(add_query_arg(
      $args,
      $url
    )));
  }
}

if (!function_exists('szcs_coupon_create_field')) {
  function szcs_coupon_create_field($args)
  {

    if (!isset($args['label'], $args['id'])) return;

    $args = wp_parse_args($args, array(
      'name' => $args['id'],
      'type' => 'text',
      'autofocus' => false,
      'required' => false
    ));

    $output = '<div class="input-field">';
    $output .= '<input type="' . $args['type'] . '" name="' . $args['name'] . '" id="' . $args['id'] . '" placeholder="' . __($args['label'], "szcs-coupon") . '" ';
    $output .= $args['autofocus'] ? ' autofocus' : '';
    $output .= $args['required'] ? ' required' : '';
    $output .= '><label for="' . $args['id'] .  '">' . __($args['label'], "szcs-coupon") . '</label>';
    $output .= '</div>';
    return $output;
  }
}

if (!function_exists('szcs_coupon_can_redeem')) {
  function szcs_coupon_can_redeem($voucher, $user_id = '')
  {
    if (!$user_id && !is_admin() && is_user_logged_in()) {
      $user_id = get_current_user_id();
    }

    // get szcs_coupon_transaction class instance
    global $szcs_coupon_transaction;

    // get number of claims
    $claims_count = count($szcs_coupon_transaction->get_transactions_by_voucher_id($voucher->voucher_id));

    // check if voucher is already claimed
    if ($claims_count >= $voucher->usage_limit_per_voucher) {
      return array('error', 'Error', 'This voucher is already claimed, please try another');
    }

    if ($user_id) {
      // check if user has already claimed this voucher
      $claim_by_user_count = count($szcs_coupon_transaction->get_number_of_claims_by_user($voucher->voucher_id, $user_id));
      if ($claim_by_user_count >= $voucher->usage_limit_per_user) {
        return array('error', 'Error', 'You have already claimed this voucher, please try another');
      }
    }

    return array('success', 'Success', 'Voucher can be claimed');
  }
}
