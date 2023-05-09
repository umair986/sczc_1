<?php

use ElementorPro\Modules\Popup\DisplaySettings\Triggers;

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponOption
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponOption
   * @since 1.0.25
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
    add_action('wp', array($this, 'members_only'));
  }

  // Redirect users who arent logged in...
  public function members_only()
  {

    global $wp;

    $options = get_option('szcs-coupon_options');


    if (isset($options['szcs-coupon-member-only']) && $options['szcs-coupon-member-only'] == 1) {

      $exclude_pages = isset($options['szcs-coupon-member-exclude']) ? explode("\n", $options['szcs-coupon-member-exclude']) : array();

      $skip_login = false;

      $searchString = trim($wp->request, '/');

      if ($searchString == '') {
        $searchString = 'home';
      }

      foreach ($exclude_pages as $element) {

        // Check if the element starts with the search string
        if (strpos($element, $searchString) === 0) {
          // Do something with the matching element
          $skip_login = true;
        }
      }
      if ((!is_user_logged_in() && !isset($_GET['login']) || (isset($_GET['login']) && $_GET['login'] != 'true')) && !$skip_login) {
        // redirect to same url with login=true
        wp_redirect(home_url(add_query_arg(array('login' => 'true'), $wp->request)));
        exit;
      }
    }
    if (is_user_logged_in() && isset($_GET['login'])) {
      // redirect to same url without login=true
      wp_redirect(home_url(remove_query_arg('login', $wp->request)));
      exit;
    }
    /*
    if (isset($options['szcs-coupon-member-only']) && $options['szcs-coupon-member-only'] == 1) {
      // Check to see if user in not logged in and not on the login page
      if (

        !is_user_logged_in() && // user is not logged in 
        !in_array(trim($_SERVER["REQUEST_URI"], '/'), ['login', 'admin', 'auth']) && // requested url is not admin, login or auth
        !str_starts_with(trim($_SERVER["REQUEST_URI"]), '/auth/?') && // requested url is not auth with some query args
        !str_starts_with(trim($_SERVER["REQUEST_URI"]), '/auth?')
      ) {
        global $wp;
        $current_url = home_url($wp->request);



        if (str_starts_with(trim($_SERVER["REQUEST_URI"]), '/my-account/lost-password/') && isset($_GET['key']) &&  isset($_GET['id'])) {
          $query_args = array(
            'key' => $_GET['key'],
            'id' => $_GET['id'],
            'type' => 'forgot_password',
          );
        } else {
          $query_args = array(
            'r' => urlencode(trim($_SERVER["REQUEST_URI"]) == '/my-account/lost-password/' ?  home_url() : $current_url),
            'type' =>  trim($_SERVER["REQUEST_URI"]) == '/my-account/lost-password/' ? 'forgot_password' : 'login'
          );
        }
        //https: //www.myfreebucks.cubosquare.com/my-account/lost-password/?key=pNiLsPUWQXOX2glJ8upv&id=3
        // Redirect to login page
        wp_redirect(home_url(add_query_arg($query_args, 'auth')));
        exit;
      }
    } */
  }
}


SzCsCouponOption::instance();
