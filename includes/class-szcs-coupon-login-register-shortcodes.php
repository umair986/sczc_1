<?php

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponForms
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponForms
   * @since 1.1.25
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

    //add_action('login_form_login', array($this, 'redirect_to_custom_lostpassword'));
    add_action('init', array($this, 'post_login'));
    add_action('init', array($this, 'post_register'));
    add_action('init', array($this, 'post_forgot_pass'));
    add_action('init', array($this, 'validate_pass_key'));
    add_action('init', array($this, 'post_reset'));
    add_shortcode('szcs_coupon_login_form', array($this, 'login_shortcode'));
    //add_action('login_form_rp', array($this, 'redirect_to_custom_lostpassword'));
    //add_action('wp', array($this, 'members_only'));
  }

  public function validate_pass_key()
  {

    if (isset($_GET['type']) && isset($_GET['key']) && isset($_GET['id']) && $_GET['type'] == 'forgot_password') {
      $user = $this->check_password_reset_key($_GET['key'], $_GET['id']);
      if (!$user) {
        $_GET['key'] = 'invalidKey';
      }
    }
  }

  public function check_password_reset_key($rp_key, $rp_id)
  {
    $userdata               = get_userdata(absint($rp_id));
    $rp_login               = $userdata ? $userdata->user_login : '';
    $user                   = check_password_reset_key($rp_key, $rp_login);

    if (is_wp_error($user)) {
      wc_add_notice(__('This link is invalid or has already been used. Please reset your password again if needed.', 'szcs-coupon'), 'error');
      return false;
    }
    return $user;
  }

  public function post_reset()
  {
    if ($_POST && isset($_REQUEST['action']) && isset($_REQUEST['key']) && isset($_REQUEST['id']) && $_REQUEST['action'] == 'user_login_reset' && wp_verify_nonce($_REQUEST['szcs-coupon_wpnonce'], 'szcs-coupon-reset')) {
      $pass = $_REQUEST['password'];
      $cpass = $_REQUEST['cpassword'];

      if ($pass == $cpass) {
        $key = $_REQUEST['key'];
        $id = $_REQUEST['id'];
        $user = $this->check_password_reset_key($key, $id);
        if (!$user) {
          $_GET['key'] = 'invalidKey';
          return;
        }
        wp_set_password($pass, $id);
        wc_add_notice(__('Password reset successfully.', 'szcs-coupon'), 'success');
        $_GET['type'] = 'login';
      }
    }
  }

  public function post_login()
  {
    if ($_POST && isset($_REQUEST['action']) && $_REQUEST['action'] == 'user_login' && wp_verify_nonce($_REQUEST['szcs-coupon_wpnonce'], 'szcs-coupon-login')) {

      global $wpdb;

      //We shall SQL escape all inputs  
      $username = $wpdb->escape($_REQUEST['username']);
      $password = $wpdb->escape($_REQUEST['password']);
      $remember = false;
      //$remember = $wpdb->escape($_REQUEST['rememberme']);

      if ($remember) $remember = "true";
      else $remember = "false";

      $login_data = array();
      $login_data['user_login'] = $username;
      $login_data['user_password'] = $password;
      $login_data['remember'] = $remember;

      $user_verify = wp_signon($login_data, true);

      if (is_wp_error($user_verify)) {
        foreach ($user_verify->get_error_messages() as $err) {
          wc_add_notice(__($err, 'szcs-coupon'), 'error');
        }
      } else {

        // on success if redirect url set
        if (isset($_GET['r'])) {
          wp_redirect($_GET['r']);
        } else {

          // if redirect url not set
          wp_redirect(home_url());
        }
      }
    }
  }

  public function post_register()
  {

    if ($_POST && isset($_REQUEST['action']) && $_REQUEST['action'] == 'user_login_register' && wp_verify_nonce($_REQUEST['szcs-coupon_wpnonce'], 'szcs-coupon-register')) {

      global $wpdb;
      //We shall SQL escape all inputs  

      global $szcs_coupon_voucher;

      $voucher_no = $wpdb->escape($_REQUEST['voucher']);
      $username = $wpdb->escape($_REQUEST['username']);
      $password = $wpdb->escape($_REQUEST['password']);
      $email = $wpdb->escape($_REQUEST['email']);
      $name = $wpdb->escape($_REQUEST['fullname']);

      $name = explode(' ', $name, 2);
      $error = false;

      $voucher = $szcs_coupon_voucher->validate_voucher($voucher_no, '', true);
      if ($voucher[0] !== 'valid') {
        wc_add_notice(__($voucher[2], 'szcs-coupon'), $voucher[0]);
        $error = true;
      }

      if (!is_email($email)) {
        wc_add_notice(__('Please enter a valid email address', 'szcs-coupon'), 'error');
        $error = true;
      } else if (email_exists($email)) {
        wc_add_notice(__('Email already exist', 'szcs-coupon'), 'error');
        $error = true;
      }
      if (username_exists($username)) {
        wc_add_notice(__('Username already exist', 'szcs-coupon'), 'error');
        $error = true;
      }
      if (!$error) {

        $user_data = array(
          'user_login'    => $username,
          'user_email'    => $email,
          'user_pass'     => $password,
          'first_name'    => $name[0],
          'last_name'     => count($name) > 1 ? $name[1] : '',
          'nickname'      => $name[0],
        );
        $user_id = wp_insert_user($user_data);
        wp_new_user_notification($user_id, $password);

        $voucher = $voucher[1];

        do_action('szcs_coupon_add_transaction', array(
          'user_id' => $user_id,
          'description' => "Voucher $voucher->voucher_id claimed by user $user_id",
          'debit_points' => 0,
          'credit_points' => $voucher->voucher_amount,
          'voucher_id' => $voucher->voucher_id,
          'voucher_no' => $voucher->voucher_no,
          'status' => null,
        ));

        wc_add_notice(__('Account created successfully', 'szcs-coupon'), 'success');
        if (isset($_GET['r'])) {
          $query_args['r'] = $_GET['r'];
        }
        $query_args['type'] = 'login';

        $login_url = home_url(add_query_arg($query_args), 'auth');

        wp_redirect($login_url);
        exit;

        return $user_id;
      }
    }
  }

  public function post_forgot_pass()
  {
    if ($_POST && isset($_REQUEST['action']) && $_REQUEST['action'] == 'user_login_forgot' && wp_verify_nonce($_REQUEST['szcs-coupon_wpnonce'], 'szcs-coupon-forgot')) {
      $login = isset($_POST['username']) ? sanitize_user(wp_unslash($_POST['username'])) : ''; // WPCS: input var ok, CSRF ok.

      if (empty($login)) {

        wc_add_notice(__('Enter a username or email address.', 'woocommerce'), 'error');

        return false;
      } else {
        // Check on username first, as customers can use emails as usernames.
        $user_data = get_user_by('login', $login);
      }
      // If no user found, check if it login is email and lookup user based on email.
      if (!$user_data && is_email($login)) {
        $user_data = get_user_by('email', $login);
      }

      $errors = new WP_Error();

      do_action('lostpassword_post', $errors, $user_data);

      if ($errors->get_error_code()) {
        wc_add_notice($errors->get_error_message(), 'error');

        return false;
      }

      if (!$user_data) {
        wc_add_notice(__('Invalid username or email.', 'szcs-coupon'), 'error');

        return false;
      }

      // Redefining user_login ensures we return the right case in the email.
      $user_login = $user_data->user_login;

      do_action('retrieve_password', $user_login);

      $allow = apply_filters('allow_password_reset', true, $user_data->ID);

      if (!$allow) {

        wc_add_notice(__('Password reset is not allowed for this user', 'szcs-coupon'), 'error');

        return false;
      } elseif (is_wp_error($allow)) {

        wc_add_notice($allow->get_error_message(), 'error');

        return false;
      }

      // Get password reset key (function introduced in WordPress 4.4).
      $key = get_password_reset_key($user_data);

      // Send email notification.
      WC()->mailer(); // Load email classes.
      do_action('woocommerce_reset_password_notification', $user_login, $key);
      wc_add_notice(__('Password reset link sent, please check your email', 'szcs-coupon'), 'notice');

      return true;
    }
  }

  // Redirect users who arent logged in...
  public function login_shortcode($args)
  {

    if (is_user_logged_in()) {
      // on success if redirect url set
      if (isset($_GET['r'])) {
        $url = urldecode($_GET['r']);
      } else {
        // if redirect url not set
        $url = home_url();
      }
      // redirect through js since header already sent
      return '<script> window.location.href = "' . $url . '" </script>';
    }





    $args = wp_parse_args(
      $args,
      array(
        'forgot_url' => 'forgot',
        'forgot_label' => 'Forgot Password?',
        'register_url' => 'register',
        'register_label' => 'Click here to register',
        'logo_url' => '',
        'login_banner' => plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/img/login.svg',
        'forgot_pass_banner' => plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/img/forgot_pass.svg',
        'register_banner' => plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/img/add_user.svg',
        'font-family-1' => "'Playfair Display', serif",
        'font-family-2' => "'Roboto', sans-serif",
        'theme-color-dark' => '#2d416f',
        'theme-color-light' => '#c4d4fc',
        'theme-color-light-bg' => '#dbe5ff',
        'theme-second-color' => '#F34C24',
      )
    );

    // Change form and background image based on request
    if (isset($_GET['type'])) {
      switch ($_GET['type']) {
        case 'forgot_password':
          if (isset($_GET['key']) && isset($_GET['id']) && $_GET['key'] != 'invalidKey') {
            $form  = $this->get_reset_pass_form();
            $args['side_banner'] = $args['login_banner'];
          } else {
            $form = $this->get_forgot_pass_form();
            $args['side_banner'] = $args['forgot_pass_banner'];
          }
          break;
        case 'register':
          $form  = $this->get_register_form();
          $args['side_banner'] = $args['register_banner'];
          break;
        default:
          $form  = $this->get_login_form();
          $args['side_banner'] = $args['login_banner'];
      }
    } else {
      $args['side_banner'] = $args['login_banner'];
      $form  = $this->get_login_form();
    }

    $showForm = true;

    $output =  '<div class="szcs-coupon-auth-page" ';
    $output .= 'style="';
    $output .= '--szcs-coupon-font-playfair:' . $args['font-family-1'] . '; ';
    $output .= '--szcs-coupon-font-roboto: ' . $args['font-family-2'] .  '; ';
    $output .= '--szcs-coupon-theme-color-dark: ' . $args['theme-color-dark'] . '; ';
    $output .= '--szcs-coupon-theme-color-light: ' . $args['theme-color-light'] . '; ';
    $output .= '--szcs-coupon-theme-second-color: ' . $args['theme-second-color'] . '; ';
    $output .= '--szcs-coupon-theme-color-light-bg: ' . $args['theme-color-light-bg'] . '; "';
    $output .=  '><main class="content">';
    $output .=  '<div class="design" style="background-image: url(' . $args['side_banner'] . ');"></div>';
    $output .=  '<section class="form-aria">';
    if ($args['logo_url']) {
      $output .=  '<div class="logo-wrapper"><img src="' . $args['logo_url'] . '" alt=""></div>';
    }
    if (function_exists('wc_print_notices')) {
      $notice =  wc_print_notices(true);
    }
    if (function_exists('wc_print_notices') && isset($_GET['type']) && $_GET['type'] === 'forgot_password' && !isset($_GET['key']) && !isset($_GET['id'])) {
      if ($notice) {
        $showForm = false;
      } else {
        wc_add_notice(__('Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'szcs-coupon'), 'notice');
        $notice =  wc_print_notices(true);
      }
    }

    $output .= $notice;
    $output .=  '<div class="forms">';

    $output .= $showForm ? $form : '';

    $output .=  '</div>';
    $output .=  '</div></section></main></div>';
    $output .=  '<script> removeUrlParameter("key"); removeUrlParameter("id"); </script>';
    return $output;
  }



  public function get_login_form()
  {

    // Login form
    $output =  '<div class="login">';

    $output .= '<h1>' . __('Login', 'szce-coupon') . '</h1>';
    $output .=  '<form method="post" action="">';

    // Username field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Username or Email',
      'id'        => 'username',
      'autofocus' => true,
      'required'  => true,
    ));

    // Password
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Password',
      'id'        => 'password',
      'type'        => 'password',
      'required'  => true,
    ));

    $query_args = array();

    if (isset($_GET['r'])) {
      $query_args['r'] = urlencode($_GET['r']);
    }
    $query_args['type'] = 'forgot_password';
    $forgot_pass_url = home_url(add_query_arg($query_args), 'auth');

    // Forgot password link
    $output .= '<div class="forgot-pass"><a href="' . $forgot_pass_url . '">Forgot Password?</a></div>';

    // WP Nonce
    $output .= wp_nonce_field('szcs-coupon-login', 'szcs-coupon_wpnonce', true, false);
    $output .=  '<input type="hidden" name="action" value="user_login">';

    // Submit button
    $output .= '<button type="submit">Login</button>';

    $output .=  '</form>';
    $query_args['type'] = 'register';
    $register_url = home_url(add_query_arg($query_args), 'auth');
    $output .=  '<div class="switch-form"><span>Don\'t have an account? </span><a href="' . $register_url . '">Create account</a></div>';
    return $output;
  }


  public function get_forgot_pass_form()
  {

    // Login form
    $output =  '<div class="login">';

    $output .= '<h1>' . __('Forgot Password', 'szce-coupon') . '</h1>';
    $output .=  '<form method="post" action="">';

    // Username field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Username or Email',
      'id'        => 'username',
      'autofocus' => true,
      'required'  => true,
    ));
    $query_args = array();
    if (isset($_GET['r'])) {
      $query_args['r'] = urlencode($_GET['r']);
    }
    $query_args['type'] = 'login';
    $login_url = home_url(add_query_arg($query_args), 'auth');

    // Forgot password link
    $output .= '<div class="forgot-pass"><a href="' . $login_url . '">Back to Login?</a></div>';

    // WP Nonce
    $output .= wp_nonce_field('szcs-coupon-forgot', 'szcs-coupon_wpnonce', true, false);
    $output .=  '<input type="hidden" name="action" value="user_login_forgot">';

    // Submit button
    $output .= '<button type="submit">Get New Password</button>';

    $output .=  '</form>';

    $query_args['type'] = 'register';
    $register_url = home_url(add_query_arg($query_args), 'auth');
    $output .=  '<div class="switch-form"><span>Don\'t have an account? </span><a href="' . $register_url . '">Create account</a></div>';
    return $output;
  }

  public function get_reset_pass_form()
  {

    // Login form
    $output =  '<div class="login">';

    $output .= '<h1>' . __('Reset Password', 'szce-coupon') . '</h1>';
    $output .=  '<form method="post" action="">';

    // Password field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'New Password',
      'id'        => 'password',
      'type'      => 'password',
      'autofocus' => true,
      'required'  => true,
    ));

    // Confor field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Confirm Password',
      'id'        => 'cpassword',
      'type'      => 'password',
      'required'  => true,
    ));

    $output .= '<input type="hidden" name="key" value="' . $_GET['key'] . '">';
    $output .= '<input type="hidden" name="id" value="' . $_GET['id'] . '">';

    $query_args = array();
    $query_args['type'] = 'login';
    $login_url = home_url(add_query_arg($query_args), 'auth');

    // Forgot password link
    $output .= '<div class="forgot-pass"><a href="' . $login_url . '">Back to Login?</a></div>';

    // WP Nonce
    $output .= wp_nonce_field('szcs-coupon-reset', 'szcs-coupon_wpnonce', true, false);
    $output .=  '<input type="hidden" name="action" value="user_login_reset">';

    // Submit button
    $output .= '<button type="submit">Set New Password</button>';

    $output .=  '</form>';

    return $output;
  }


  public function get_register_form()
  {

    // Login form
    $output =  '<div class="signup">';

    $output .= '<h1>' . __('Create an Account', 'szce-coupon') . '</h1>';
    $output .=  '<form method="post" action="">';

    // Voucher field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Voucher No',
      'id'        => 'voucher',
      'autofocus' => true,
      'required'  => true,
    ));

    // Name field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Name',
      'id'        => 'fullname',
      'autofocus' => true,
      'required'  => true,
    ));

    // Mobile No field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Mobile No',
      'id'        => 'mobile',
      'type'      => 'tel',
      'autofocus' => true,
      'required'  => true,
    ));

    // Email field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Email',
      'id'        => 'email',
      'type'      => 'email',
      'autofocus' => true,
      'required'  => true,
    ));

    // Username field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Username',
      'id'        => 'username',
      'required'  => true,
    ));


    // Password field
    $output .= szcs_coupon_create_field(array(
      'label'     => 'Password',
      'id'        => 'password',
      'type'        => 'password',
      'required'  => true,
    ));


    $query_args = array();
    if (isset($_GET['r'])) {
      $query_args['r'] = urlencode($_GET['r']);
    }
    $query_args['type'] = 'login';
    $login_url = home_url(add_query_arg($query_args), 'auth');


    // WP Nonce
    $output .= wp_nonce_field('szcs-coupon-register', 'szcs-coupon_wpnonce', true, false);
    $output .=  '<input type="hidden" name="action" value="user_login_register">';

    // Submit button
    $output .= '<button type="submit">Register</button>';

    $output .=  '</form>';

    $query_args['type'] = 'login';
    $login_url = home_url(add_query_arg($query_args), 'auth');
    $output .=  '<div class="switch-form"><span>Already have an account? </span><a href="' . $login_url . '">Login</a></div>';
    return $output;
  }
}


SzCsCouponForms::instance();
