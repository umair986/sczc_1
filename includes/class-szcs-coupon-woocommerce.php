<?php

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponWC
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponWC
   * @since 1.1.11
   */
  protected static $_instance = null;

  /**
   * Main instance
   *
   * @return class object
   */

  private static $product_id = null;
  private static $the_product = null;


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
    add_action('woocommerce_product_options_general_product_data', array($this, 'wc_product_points_field'));
    add_action('woocommerce_process_product_meta', array($this, 'wc_product_save_points'));
    add_action('woocommerce_single_product_summary', array($this, 'wc_product_display_redeemable_points'), 30);

    add_action('product_cat_add_form_fields', array($this, 'wc_cat_add_points_field'), 10, 1);
    add_action('product_cat_edit_form_fields', array($this, 'wc_cat_edit_points_field'), 10, 1);
    add_action('edited_product_cat', array($this, 'wc_cat_save_points'), 10, 1);
    add_action('create_product_cat', array($this, 'wc_cat_save_points'), 10, 1);

    add_action('product_brand_add_form_fields', array($this, 'wc_brand_add_points_field'), 10, 1);
    add_action('product_brand_edit_form_fields', array($this, 'wc_brand_edit_points_field'), 10, 1);
    add_action('edited_product_brand', array($this, 'wc_brand_save_points'), 10, 1);
    add_action('create_product_brand', array($this, 'wc_brand_save_points'), 10, 1);

    add_action('woocommerce_single_product_summary', array($this, 'wc_single_product_remove_add_to_cart_conditionally'));
    add_filter('woocommerce_loop_add_to_cart_link', array($this, 'wc_shop_loop_remove_add_to_cart_conditionally'), 25, 2);

    add_filter('woocommerce_cart_item_price', array($this, 'wc_cart_item_price'), 10, 3);
    add_action('woocommerce_before_calculate_totals', array($this, 'wc_recalculate_cart'));

    add_action('woocommerce_after_shop_loop_item_title', array($this, 'wc_product_loop_display_redeemable_points'), 11);

    add_action('woocommerce_checkout_order_processed', array($this, 'wc_order_processed'),  1, 1);
    add_action('woocommerce_before_pay_action', array($this, 'wc_order_before_action'),  1, 1);


    add_action('woocommerce_order_status_cancelled', array($this, 'wc_order_point_refund'), 10, 1);


    add_filter('manage_edit-product_cat_columns', array($this, 'register_cat_point_cloumn'));
    add_filter('manage_edit-product_brand_columns', array($this, 'register_brand_point_cloumn'));


    add_filter('manage_product_cat_custom_column', array($this, 'point_column_display'), 10, 3);
    add_filter('manage_product_brand_custom_column', array($this, 'point_column_display'), 10, 3);

    add_action('quick_edit_custom_box', array($this, 'quick_edit_category_field'), 10, 3);

    add_action('edited_edition', array($this, 'quick_edit_save_category_field'));
    add_action('edited_edition', array($this, 'quick_edit_save_brand_field'));
  }



  // Create points field for the product in product edit page
  public function wc_product_points_field()
  {
    echo '<div class="szcs_product_edit_fields">';
    woocommerce_wp_text_input(
      array(
        'id' => 'szcs_product_points_field',
        'placeholder' => '',
        'label' => __('Points (%)', 'szcs-coupon'),
        'type' => 'number',
        'custom_attributes' => array(
          'step' => 'any',
          'min' => '0',
          'max' => '100',
        )
      )
    );
    echo '</div>';
  }

  public function get_product($the_product = false)
  {
    if ($the_product) {
      return wc_get_product($the_product);
    } else {
      global $product;
      if (!empty($product)) {
        self::$the_product = $product;
      }
    }
    return self::$the_product;
  }


  public function wc_product_save_points()
  {
    $product = $this->get_product();
    $points = filter_input(INPUT_POST, 'szcs_product_points_field');
    if ($points == '' || ($points >= 0 && $points <= 100)) {
      $product->update_meta_data('szcs_product_points_field', $points);
      $product->save();
    }
  }

  public function wc_single_product_remove_add_to_cart_conditionally()
  {
    global $szcs_coupon_wallet;
    $balance_payable = $this->wc_product_get_points_amount();
    $balance = $szcs_coupon_wallet->get_balance();

    if (get_current_user_id() && $balance_payable > $balance) {
      add_action('woocommerce_single_product_summary', array($this, 'not_enough_points_product_notice'), 31);
      remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }
  }

  public function not_enough_points_product_notice()
  {
    _e('You don\'t have enough points', 'szcs-coupon');
    wc_add_notice(__("You don't have enough points to buy this product",  "szcs-coupon"), 'error');
  }


  public function wc_shop_loop_remove_add_to_cart_conditionally($add_to_cart_html)
  {
    global $szcs_coupon_wallet;
    $balance_payable = $this->wc_product_get_points_amount();
    $balance = $szcs_coupon_wallet->get_balance();

    if (get_current_user_id() && $balance_payable > $balance) {
      return '<span class="add_to_cart_button button" style="
              font-size: 1.3rem;
              line-height: 1.8rem;
              display: flex;
              align-items: center;
          ">You don\'t have enough points
          </span>';
    }
    return $add_to_cart_html;
    //echo $product->get_id();
  }

  function wc_cart_item_price($price_html, $cart_item)
  {
    $args = array('price' => $this->wc_product_get_amount_payable($cart_item['product_id']));

    if (WC()->cart->display_prices_including_tax()) {
      $product_price = wc_get_price_including_tax($cart_item['data'], $args);
    } else {
      $product_price = wc_get_price_excluding_tax($cart_item['data'], $args);
    }
    return wc_price($product_price);
  }


  function wc_recalculate_cart($cart_object)
  {
    $points = 0;

    foreach ($cart_object->get_cart() as $hash => $value) {

      $points_amount = $this->wc_product_get_points_amount($value['product_id']);
      $points += $points_amount * $value['quantity'];
      $value['data']->set_price($this->wc_product_get_amount_payable($value['product_id']));
    }
  }


  /**
   * @param mixed $the_product — Post object or post ID of the product
   * @return float|null points of the product
   */
  public function wc_product_get_points_percent($the_product = false, $type = '')
  {
    // Get product
    $product = $this->get_product($the_product);

    // if product found
    if (!empty($product)) {

      // when type is either brand or empty get brand's pionts percentage
      if ($type != 'category' || $type != 'product') {

        // get brand ids
        $brand_id =  wp_get_post_terms($product->get_id(), 'product_brand', array('fields' => 'ids'));

        // if brand ids found
        if (!empty($brand_id)) {

          // get the points of first brand in the list and assign to a variable
          $brand_points = get_term_meta($brand_id[0], 'szcs_brand_points_field', true);
        }
      }

      // when type is either brand or empty get category's pionts percentage
      if ($type != 'brand' || $type != 'product') {

        // get categories ids
        $category_ids = $product->get_category_ids();

        // if categories id found
        if (!empty($category_ids)) {

          // set category points to null for comparision
          $cat_points = null;
          foreach ($category_ids as $cat_id) {

            // loop through all the categories and get points
            $current_cat_points = get_term_meta($cat_id, 'szcs_cat_points_field', true);

            // compare with and get the highest available points
            if ($cat_points < $current_cat_points) {

              // if found hight then previous higher set current to highter
              $cat_points = $current_cat_points;
            }
          }
        }
      }

      if ($type != 'brand' || $type != 'category') {
        $product_points = $product->get_meta('szcs_product_points_field');
      }


      switch ($type) {
        case 'brand':

          // if asked for brand's points just return it or zero
          return isset($brand_points) ? $brand_points : 0;

        case 'category':

          // if asked for category's points just return it or zero
          return $cat_points != null ? $cat_points : 0;

        case 'product':

          // if asked for producat's points just return or zero
          return isset($product_points) ? $product_points : 0;

        default:

          // if not asked for any specific type
          if ($product_points >= 0) {

            // then return product points if set
            return $product_points;
          } else if ($cat_points != null) {

            // if product points not set then return category points
            return $cat_points;
          } else {

            // if product and category points are not set then return brand points, or 0 if brand points also not set
            return isset($brand_points) ? $brand_points : 0;
          }
      }
    }
    return 0;
  }

  public function wc_product_get_points_amount($the_product = false, $type = '')
  {
    $product = $this->get_product($the_product);
    if (!empty($product)) {

      $point_percent = $this->wc_product_get_points_percent($the_product, $type);

      if ($point_percent) {

        // calculate amount of the price and return
        return ($point_percent / 100) * $product->get_price();
      }
    }
    return 0;
  }

  /**
   * @param mixed $the_product — Post object or post ID of the product
   * @return float|null amount payable
   */
  public function wc_product_get_amount_payable($the_product = false)
  {

    $product = $this->get_product($the_product);

    if (!empty($product)) {
      // get custom field, percentage to be redeemed
      $points = $this->wc_product_get_points_amount($the_product);


      // get price of the product
      $price = $product->get_price();

      return $price - $points;
    }
    return 0;
  }


  public function wc_product_display_redeemable_points()
  {
    // get points of the product
    $points = $this->wc_product_get_points_amount();

    // get points of the product
    $payable = $this->wc_product_get_amount_payable();

    if ($points) {
      printf(
        '<div><label class="coupon-text">Points / Currency : </label><span class="coupon-amount"> %s</span></div>',
        esc_html($points)
      );

      // Show the amount to be paid
      printf(
        '<div><label class="coupon-text">Balance to Pay : </label><span class="amount coupon-amount"> ' . get_woocommerce_currency_symbol() . '%s</span></div>',
        esc_html($payable)
      );
    }
  }

  public function wc_product_loop_display_redeemable_points()
  {
    $points = $this->wc_product_get_points_amount();

    // get points of the product
    $payable = $this->wc_product_get_amount_payable();

    if ($points) {
      printf(
        '<div class="szcs_coupon_product_loop_points"><label class="">%s :</label><span class=""> %s</span></div>',
        __('Points / Currency', 'szcs-coupon'),
        $points
      );

      // Show the amount to be paid
      printf(
        '<div class="szcs_coupon_product_loop_balance"><label class="">%s :</label><span class="amount"> %s</span></div>',
        __('Balance to Pay', 'szcs-coupon'),
        get_woocommerce_currency_symbol() . $payable
      );
    }
  }

  //Points field in new cat page
  public function wc_cat_add_points_field()
  {
?>
    <div class="form-field">
      <label for="szcs_cat_points_field"><?php _e('Points (%)', 'szcs-coupon'); ?></label>
      <input type="number" name="szcs_cat_points_field" id="szcs_cat_points_field">
    </div>
  <?php
  }

  //Points field in edit category page
  function wc_cat_edit_points_field($term)
  {

    //getting term ID
    $term_id = $term->term_id;

    // retrieve the existing value(s) for this meta field.
    $points = get_term_meta($term_id, 'szcs_cat_points_field', true);
  ?>
    <tr class="form-field">
      <th scope="row" valign="top"><label for="szcs_cat_points_field"><?php _e('Points (%)', 'szcs-coupon'); ?></label></th>
      <td>
        <input type="number" name="szcs_cat_points_field" id="szcs_cat_points_field" value="<?php echo esc_attr($points) ? esc_attr($points) : ''; ?>">
      </td>
    </tr>
  <?php
  }

  // Save category points.
  function wc_cat_save_points($term_id)
  {
    $points = filter_input(INPUT_POST, 'szcs_cat_points_field');
    if ($points == '' || ($points >= 0 && $points <= 100)) {
      update_term_meta($term_id, 'szcs_cat_points_field', $points);
    }
  }

  //Points field in new brand page
  public function wc_brand_add_points_field()
  {
  ?>
    <div class="form-field">
      <label for="szcs_brand_points_field"><?php _e('Points (%)', 'szcs-coupon'); ?></label>
      <input type="number" name="szcs_brand_points_field" id="szcs_brand_points_field">
      <!-- <p class="description"><?php _e('', 'szcs-coupon'); ?></p> -->
    </div>
  <?php
  }

  //Points field in edit brand page
  function wc_brand_edit_points_field($term)
  {

    //getting term ID
    $term_id = $term->term_id;

    // retrieve the existing value(s) for this meta field.
    $points = get_term_meta($term_id, 'szcs_brand_points_field', true);
  ?>
    <tr class="form-field">
      <th scope="row" valign="top"><label for="szcs_brand_points_field"><?php _e('Points (%)', 'szcs-coupon'); ?></label></th>
      <td>
        <input type="number" name="szcs_brand_points_field" id="szcs_brand_points_field" value="<?php echo esc_attr($points) ? esc_attr($points) : ''; ?>">
      </td>
    </tr>
    <?php
  }

  // Save brand points.
  function wc_brand_save_points($term_id)
  {
    $points = filter_input(INPUT_POST, 'szcs_brand_points_field');
    if ($points == '' || ($points >= 0 && $points <= 100)) {
      update_term_meta($term_id, 'szcs_brand_points_field', $points);
    }
  }

  // register column for point in category table
  function register_cat_point_cloumn($columns)
  {
    $columns = array_slice($columns, 0, 2, true) +
      array('szcs_cat_points_field' => __('Points', 'szcs-coupon')) +
      array_slice($columns, 2, count($columns) - 1, true);

    return $columns;
  }

  // register column for point in brand table
  function register_brand_point_cloumn($columns)
  {
    $columns = array_slice($columns, 0, 2, true) +
      array('szcs_brand_points_field' => __('Points', 'szcs-coupon')) +
      array_slice($columns, 2, count($columns) - 1, true);

    return $columns;
  }

  // display ponts in category table and brand table
  function point_column_display($string = '', $column_name, $term_id)
  {
    if ($column_name == 'szcs_brand_points_field' || $column_name == 'szcs_cat_points_field') {
      $points = get_term_meta($term_id, $column_name, true);
      if ($points >= 0 && $points <= 100) {
        $string = $points . '%';
      } else {
        $string = '—';
      }
      return esc_html($string);
    }
  }

  function quick_edit_category_field($column_name, $page, $screen)
  {
    // If we're not iterating over our custom column, then skip
    if (($screen == 'product_cat' && $column_name == 'szcs_cat_points_field') || ($screen == 'product_brand' && $column_name == 'szcs_brand_points_field')) {
    ?>

      <fieldset>
        <div id="<?php echo esc_attr($column_name); ?>" class="inline-edit-col">
          <label>
            <span class="title"><?php _e('Points', 'szcs-coupon'); ?></span>
            <span class="input-text-wrap"><input type="number" name="<?php echo esc_attr($column_name); ?>" class="ptitle" value=""></span>
          </label>
        </div>
      </fieldset>
<?php
    }
  }

  function quick_edit_save_category_field($term_id)
  {
    if (isset($_POST['szcs_cat_points_field'])) {
      // security tip: kses
      update_term_meta($term_id, 'szcs_cat_points_field', filter_input(INPUT_POST, 'szcs_cat_points_field'));
    }
  }


  function quick_edit_save_brand_field($term_id)
  {
    if (isset($_POST['szcs_brand_points_field'])) {
      // security tip: kses
      update_term_meta($term_id, 'szcs_brand_points_field', filter_input(INPUT_POST, 'szcs_brand_points_field'));
    }
  }



  public function wc_order_processed($order_id)
  {

    // get order details
    $order = new WC_Order($order_id);

    // create $points variable
    $points = 0;
    // deduct points
    // proceed payment
    // 

    // get points needed for order
    foreach ($order->get_items() as $hash => $value) {
      $points +=  $this->wc_product_get_points_amount($value['product_id']) * $value['quantity'];
    }

    global $szcs_coupon_wallet;
    $balance = $szcs_coupon_wallet->get_balance();

    update_post_meta($order_id, 'points', $points);

    // compare points
    if ($balance < $points) {
      $order->update_status('failed', 'Insufficient Points');
      $diff = $points - $balance;
      wp_send_json(array(
        "result" => "failure",
        "messages" => "<ul class=\"woocommerce-error\" role=\"alert\"><li>You need " . $diff . " more points to place this order</li></ul>",
        "refresh" => false,
        "reload" => false
      ));
      exit;
    }

    do_action('szcs_coupon_add_transaction', array(
      'description' => "Redeemed for order #$order_id",
      'debit_points' => $points,
    ));

    update_post_meta($order_id, 'point_status', 'redeemed');
  }

  public function wc_order_before_action($order)
  {

    // get order details
    $order_id = $order->get_id();

    // create $points variable
    $points = 0;
    // deduct points
    // proceed payment
    // 

    // get points needed for order
    foreach ($order->get_items() as $hash => $value) {
      $points +=  $this->wc_product_get_points_amount($value['product_id']) * $value['quantity'];
    }

    global $szcs_coupon_wallet;
    $balance = $szcs_coupon_wallet->get_balance();

    $order->update_meta_data('points', $points);

    // compare points
    if ($balance < $points) {
      $order->update_status('failed', 'Insufficient Points');
      $order->save();
      $diff = $points - $balance;
      wc_add_notice(__('You need ' . $diff . ' more points to place this order', 'szcs-coupon'), 'error');
      return;
    }

    do_action('szcs_coupon_add_transaction', array(
      'description' => "Redeemed for order #$order_id",
      'debit_points' => $points,
    ));

    update_post_meta($order_id, 'point_status', 'redeemed');
  }


  public function wc_order_point_refund($order_id)
  {
    // get order details
    $order = new WC_Order($order_id);

    // Points paid for order
    $points =  $order->get_meta('points');

    // Points payment status
    $points_status = $order->get_meta('point_status');

    if ($points_status == 'redeemed') {
      $order->update_meta_data('point_status', 'refunded');
      do_action('szcs_coupon_add_transaction', array(
        'description' => "Refunded for order #$order_id",
        'credit_points' => $points,
      ));
      $order->save();
    }
  }
}

/**
 * Returns the main instance of SzCsCouponWC.
 *
 * @since  1.0.0
 * @return SzCsCouponWC
 */
function szcs_coupon_wc()
{
  return SzCsCouponWC::instance();
}

$GLOBALS['szcs_coupon_wc'] = szcs_coupon_wc();
