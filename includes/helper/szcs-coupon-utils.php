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
