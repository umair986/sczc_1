<?php

/**
 * Users balance details page file
 *
 * @package SzCsCoupon
 */
class SzCsCouponExport
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponExport
   * @since 1.1.1
   */
  protected static $_instance = null;

  protected $batch_details_table = null;

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
    add_action('szcs_admin_menu', array($this, 'admin_menu'), 20);
  }

  public function admin_menu($parent_slug)
  {

    $szcs_coupon_menu_page_hook = add_submenu_page(
      $parent_slug,
      __('Export Coupons', 'szcs-coupon'),
      __('Export', 'szcs-coupon'),
      'export_vouchers',
      'szcs-coupon-export',
      [$this, 'page_html'],
    );
    add_action("load-$szcs_coupon_menu_page_hook", array($this, 'add_szcs_coupon_details'));
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
    include_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-batch-details.php';
    $this->batch_details_table = new SzCs_Coupon_Batch_Details();
    $this->batch_details_table->prepare_items();
  }

  /**
   * Display user coupon users detail page
   */
  public function page_html()
  {
?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
      <?php $this->batch_details_table->views(); ?>
      <form id="coupon-users" method="post">
        <?php $this->batch_details_table->search_box(__('Search', 'szcs-coupon'), 'search_id'); ?>
        <?php $this->batch_details_table->display(); ?>
      </form>
    </div>
<?php
  }
}

SzCsCouponExport::instance();
