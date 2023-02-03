<?php

/**
 * Coupon Generator page file
 *
 * @package SzCsCoupon
 */
class SzCsCouponGenerator
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponGenerator
   * @since 1.1.10
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
    add_action('szcs_admin_menu', array($this, 'admin_menu'), 49);
    add_filter('submenu_file', array($this, 'submenu_filter'));
    add_action('admin_post_coupon-generator', array($this, 'submit_form'));
  }

  public function admin_menu($parent_slug)
  {
    add_submenu_page(
      $parent_slug,
      __('Generate Coupons', 'szcs-coupon'),
      __('Generate Coupons', 'szcs-coupon'),
      get_szcs_coupon_user_capability(),
      'szcs-coupon-generator',
      [$this, 'page_html'],
      100
    );
  }

  public function submenu_filter($submenu_file)
  {
    global $plugin_page;

    $hidden_submenus = array(
      'szcs-coupon-generator' => true,
    );

    // Select another submenu item to highlight (optional).
    if ($plugin_page && isset($hidden_submenus[$plugin_page])) {
      $submenu_file = 'edit.php?post_type=szcs_coupons_code';
    }

    // Hide the submenu.
    foreach ($hidden_submenus as $submenu => $unused) {
      remove_submenu_page('szcs-coupon-users', $submenu);
    }

    return $submenu_file;
  }

  public function submit_form()
  {
    do_action('szcs_coupon_create_vouchers', $_POST["voucher_amount"], $_POST["number_of_coupons"], array(
      'prefix' => $_POST["prefix"],
      'expiry_date' => $_POST["expiry_date"],
      'usage_limit_per_voucher' => $_POST["usage_limit_per_voucher"],
      'usage_limit_per_user' => $_POST["usage_limit_per_user"],
    ), $_POST["vendor"]);
    szcs_redirect(admin_url(add_query_arg(array('page' => 'szcs-coupon-generator'), 'admin.php')));
  }

  /**
   * Display coupon generator page
   */
  public function page_html()
  {
    // check user capabilities
    if (!current_user_can('manage_options')) {
      return;
    }

    $get_vendors = get_users(array(
      'role' => 'vendor',
      'orderby' => 'display_name',
      'order' => 'ASC',
      'fields' => array('ID', 'display_name')
    ));

?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
      <form id="coupon-generator" method="post" action="<?php echo admin_url('admin-post.php') ?>">
        <br />
        <table class="form-table szcs_coupon" role="presentation">
          <tbody>
            <tr>
              <th scope="row">
                <label for="prefix">Coupon name start from</label>
              </th>
              <td>
                <input type="text" class="regular-text" name="prefix" id="prefix" value="" maxlength="3" placeholder="ABC">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="voucher_amount">Coupon amount</label>
              </th>
              <td>
                <input type="number" class="regular-text" name="voucher_amount" id="voucher_amount" value="" placeholder="0" required>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="expiry_date">Coupon expiry date</label>
              </th>
              <td>
                <input type="date" class="regular-text" name="expiry_date" id="expiry_date" min="<?php echo date('Y-m-d'); ?>" value="" placeholder="YYYY-MM-DD" required>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="usage_limit_per_voucher">Usage limit per coupon</label>
              </th>
              <td>
                <input type="number" class="regular-text" name="usage_limit_per_voucher" id="usage_limit_per_voucher" value="" placeholder="1" pattern="[1-9]{1,3}" required>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="usage_limit_per_user">Usage limit per user</label>
              </th>
              <td>
                <input type="number" class="regular-text" name="usage_limit_per_user" id="usage_limit_per_user" value="" placeholder="1" pattern="[1-9]{1,3}" required>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="number_of_coupons">Number of coupon</label>
              </th>
              <td>
                <input type="number" class="regular-text" name="number_of_coupons" id="number_of_coupons" value="" placeholder="1" pattern="[1-9]{1,3}" required>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="vendor">Vendor</label>
              </th>
              <td>
                <select name="vendor" id="vendor">
                  <option value="">Select vendor</option>
                  <?php foreach ($get_vendors as $vendor) : ?>
                    <option value="<?php echo $vendor->ID; ?>"><?php echo $vendor->display_name; ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>

          </tbody>

        </table>
        <p class="submit">
          <input type="hidden" name="action" value="coupon-generator">
          <?php wp_nonce_field('coupon-generate', 'szcs_coupon_nonce'); ?>
          <input type="submit" name="submit" id="submit" class="button button-primary" value="Generate Coupons">
          <?php if ($back_id = get_option('szcs_voucher_batch_id')) : ?>
            <span style="padding-left: 1rem;">Coupons generated successfully </span> <a href="#" class="" data-batch-id="<?= $back_id; ?>" data-target="szcs-export-batch">Export Now</a>
        <p class="description">You can always export already genarated coupons either from Coupons->Coupons page or Coupons->Export page</p>
        <?php update_option('szcs_voucher_batch_id', '') ?>
      <?php endif; ?>
      </p>
      </form>
    </div>
<?php
  }
}

SzCsCouponGenerator::instance();
