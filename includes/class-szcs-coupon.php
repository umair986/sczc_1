<?php

/**
 * Main coupon class file
 * @package SzCsCoupon
 */
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}
/**
 * Main coupon calss
 */
final class SzCsCoupon
{

  /**
   * The single instance of the class.
   *
   * @var SzCsCoupon
   * @since 1.0.0
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
    if (SzCsCoupon_Dependencies::is_woocommerce_active()) {
      $this->includes();
      $this->init_hooks();

      do_action('szcs_coupon_loaded');
    } else {
      add_action('admin_notices', array($this, 'admin_notices'), 15);
    }
  }

  /**
   * Check request
   *
   * @param string $type Type.
   * @return bool
   */
  private function is_request($type)
  {
    switch ($type) {
      case 'admin':
        return is_admin();

        /*  case 'ajax':
        return defined('DOING_AJAX');
      case 'cron':
        return defined('DOING_CRON');
        */
      case 'frontend':
        return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
    }
  }

  /**
   * Load plugin files
   */
  public function includes()
  {
    include_once SZCS_COUPON_ABSPATH . 'includes/helper/szcs-coupon-utils.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-install.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-code-post-type.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/controller/class-szcs-coupon-voucher.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/controller/class-szcs-coupon-transactions.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-wallet.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-woocommerce.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-page-template.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-options.php';
    include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-login-register-shortcodes.php';
    if ($this->is_request('admin')) {
      include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-admin.php';
      include_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-users.php';
      include_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-export.php';
      include_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-settings.php';
      include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-generator.php';
      include_once SZCS_COUPON_ABSPATH . 'includes/class-szcs-coupon-ajax.php';
    }
  }

  /**
   * Plugin init
   */
  function init_hooks()
  {
    register_activation_hook(SZCS_COUPON_PLUGIN_FILE, array('SzCsCoupon_Install', 'install'));
    add_action('wp_enqueue_scripts', array($this, 'scripts'), 15);
    add_filter('plugin_action_links_' . plugin_basename(SZCS_COUPON_PLUGIN_FILE), array($this, 'add_plugin_page_settings_link'));
  }


  /**
   * Display admin notice
   */
  public function admin_notices()
  {
?>
    <div class="error">
      <p>
        <?php echo esc_html_e('SzCs Coupon requires', 'szcs-coupon'); ?>
        <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> <?php echo esc_html_e('plugins to be active!', 'szcs-coupon'); ?>;
      </p>
    </div>
<?php
  }


  function add_plugin_page_settings_link($links)
  {
    $links[] = '<a href="' .
      admin_url('admin.php?page=szcs-coupon-settings') .
      '">' . __('Settings') . '</a>';
    return $links;
  }


  /**
   * Register and enqueue styles and scripts
   *
   * @global type $post
   */
  public function scripts()
  {

    wp_register_style('jquery-datatable', 'https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css', [], false, 'all');
    wp_register_script('jquery-datatable', 'https://cdn.datatables.net/1.13.1/js/jquery.dataTables.js', ['jquery']);

    wp_register_script('szcs-jquery-datatable', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/js/szcs-coupon-table.js', ['jquery', 'jquery-datatable'], SZCS_COUPON_PLUGIN_VERSION, true);
    wp_register_script('szcs-coupon-functions', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/js/szcs-coupon.js', [], SZCS_COUPON_PLUGIN_VERSION, false);

    wp_register_script('szcs-coupon-single', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/js/szcs-coupon-single.js', [], SZCS_COUPON_PLUGIN_VERSION, true);

    wp_register_style('szcs_coupons', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/css/szcs-coupon.css', array(), SZCS_COUPON_PLUGIN_VERSION, 'all');
    wp_register_style('szcs_coupons-login', plugin_dir_url(SZCS_COUPON_PLUGIN_FILE) . 'assets/css/szcs-coupon-login.css', array(), SZCS_COUPON_PLUGIN_VERSION, 'all');

    wp_enqueue_style('szcs_coupons');

    wp_enqueue_script('szcs-coupon-functions');


    wp_enqueue_script('szcs-coupon-single');

    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'szcs_coupon_login_form')) {
      wp_enqueue_style('szcs_coupons-login');
      wp_dequeue_style('bakan-css');
      wp_dequeue_style('bootstrap');
    }
  }
}
