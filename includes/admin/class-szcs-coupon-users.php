<?php

/**
 * Users balance details page file
 *
 * @package SzCsCoupon
 */
class SzCsCouponUsers
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponUsers
   * @since 1.1.10
   */
  protected static $_instance = null;

  protected $balance_details_table = null;

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
    add_action('szcs_admin_menu', array($this, 'admin_menu'), 0);
  }

  public function admin_menu($parent_slug)
  {
    if (wp_get_current_user()->roles[0] === 'vendor') return;
    $szcs_coupon_users_menu_page_hook = add_submenu_page(
      $parent_slug,
      __('Users Balance details', 'szcs-coupon'),
      __('User Details', 'szcs-coupon'),
      get_szcs_coupon_user_capability(),
      $parent_slug,
      [$this, 'page_html'],
    );
    add_action("load-$szcs_coupon_users_menu_page_hook", array($this, 'add_szcs_coupon_details'));
  }


  /**
   * Coupon details page initialization
   */
  public function add_szcs_coupon_details()
  {
    $option = 'per_page';
    $args   = array(
      'label'   => 'Number of items per page:',
      'default' => 15,
      'option'  => 'users_per_page',
    );
    add_screen_option($option, $args);
    include_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-balance-details.php';
    $this->balance_details_table = new SzCs_Coupon_Balance_Details();
    $this->balance_details_table->prepare_items();
  }

  /**
   * Display user coupon users detail page
   */
  public function page_html()
  {
?>

    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
      <form id="coupon-users" method="get" action="">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />

        <?php if (isset($_REQUEST['role'])) : ?>
          <input type="hidden" name="role" value="<?php echo $_REQUEST['role'] ?>" />
        <?php endif; ?>

        <?php $this->balance_details_table->search_box(__('Search', 'szcs-coupon'), 'search_id'); ?>
        <?php $this->balance_details_table->views(); ?>
        <?php $this->balance_details_table->display(); ?>
      </form>
    </div>
<?php
  }
}

SzCsCouponUsers::instance();
