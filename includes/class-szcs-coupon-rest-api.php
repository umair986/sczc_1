<?php

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponRestApi
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponRestApi
   * @since 1.2.0
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
    add_filter('rest_url_prefix', array($this, 'change_api_root'));
    add_action('szcs_coupon_after_user_profile_fields', array($this, 'api_key_field'), 10, 1);

    add_action('wp_ajax_szcs-coupon-api-generate', array($this, 'ajax_generate_api_key'));

    add_action('rest_api_init', array($this, 'register_api_routes'));
  }

  // change api root
  public function change_api_root($prefix)
  {
    $options = get_option('szcs-coupon_options');
    $root = isset($options['szcs-coupon-rest-api-root']) ? sanitize_title($options['szcs-coupon-rest-api-root']) : '';
    return $root ? $root : $prefix;
  }

  // show api-key
  public function api_key_field($user)
  {
    if ($user->roles[0] == 'vendor') {
      $api_key = get_user_meta($user->ID, 'szcs_coupon_api_key', true);
      if (!$api_key) {
        $api_key = $this->generate_api_key($user->ID);
      }
?>

      <!-- field for user id -->

      <tr>
        <th><label for="szcs-coupon-user-id"><?php _e('Vendor ID', 'szcs-coupon'); ?></label></th>
        <td>
          <!-- <input type="text" name="szcs-coupon-user-id" id="szcs-coupon-user-id" value="<?php echo $user->ID; ?>" class="regular-text" readonly> -->
          <span class="" id="szcs_coupon_user_id"><?php echo $user->ID; ?></span>
        </td>
      </tr>

      <tr>
        <th><label for="szcs-coupon-api-key"><?php _e('API Key', 'szcs-coupon'); ?></label></th>
        <td>
          <div class="szcs-api-key">
            <input type="text" name="szcs-coupon-api-key" id="szcs-coupon-api-key" value="<?php echo $api_key; ?>" class="regular-text" readonly>
            <button type="button" id="copy_szcs-coupon-api-key" class="button js_copy-szcs-coupon-api-key">Copy</button>
          </div>
          <button type="button" id="szcs-coupon-api-generate" data-user-id="<?= $user->ID ?>" class="button js_generate_api">Regenerate</button>
          <p class="description"><?php _e('API Key for REST API', 'szcs-coupon'); ?></p>
        </td>
      </tr>

<?php
    }
  }

  // generate api key
  public function generate_api_key($user_id)
  {
    $api_key = wp_generate_password(32, false);
    update_user_meta($user_id, 'szcs_coupon_api_key', $api_key);
    return $api_key;
  }

  // ajax generate api key
  public function ajax_generate_api_key()
  {

    if (!is_user_logged_in()) {
      wp_send_json_error(
        array(
          'message' => __('You are not logged in', 'szcs-coupon')
        )
      );
    }

    if (!wp_verify_nonce($_POST['nonce'], 'szcs-coupon-nonce')) {
      wp_send_json_error(
        array(
          'message' => __('Invalid nonce', 'szcs-coupon')
        )
      );
    }


    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if (!$user_id) {
      wp_send_json_error(
        array(
          'message' => __('Invalid user id', 'szcs-coupon')
        )
      );
    }

    if (!current_user_can('edit_user', $user_id)) {
      wp_send_json_error(
        array(
          'message' => __('You are not allowed to edit this user', 'szcs-coupon')
        )
      );
    }

    $user = get_user_by('id', $user_id);

    if ($user->roles[0] != 'vendor') {
      wp_send_json_error(
        array(
          'message' => __('This user does not support api functionality', 'szcs-coupon')
        )
      );
    }

    if ($user) {
      $api_key = $this->generate_api_key($user_id);
      wp_send_json_success(
        array(
          'api_key' => $api_key,
          'message' => __('API Key generated successfully', 'szcs-coupon')
        )
      );
    } else {
      wp_send_json_error(
        array(
          'message' => __('User not found', 'szcs-coupon')
        )
      );
    }
  }


  function register_api_routes()
  {
    register_rest_route(
      'v1',
      '/products',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'api_get_products'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );
    register_rest_route(
      'v1',
      '/product/(?P<id>\d+)',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'api_get_product_by_id'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );
    register_rest_route(
      'v1',
      '/customer/register',
      array(
        'methods' => 'POST',
        'callback' => array($this, 'api_register_user'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );

    // get customers
    register_rest_route(
      'v1',
      '/customers',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'api_get_customers'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );
  }

  public function api_authenticate($request)
  {
    $api_key = $request->get_header('x-api-key');
    $vendor_id = $request->get_header('vendor-id');

    // Check if the authentication header is present
    if (!empty($api_key) || !empty($vendor_id)) {

      $api_key_from_db = get_user_meta($vendor_id, 'szcs_coupon_api_key', true);

      // Perform custom authentication logic here
      if ($api_key == $api_key_from_db) {
        return true;
      }
    }
    return new WP_Error('unauthorized', __('Sorry, you are not authorized to access this resource.', 'szcs-coupon'), array('status' => 401));
  }

  public function api_get_customers($request)
  {
    // $args = array(
    //   'post_type' => 'product',
    //   'post_status' => 'publish',
    //   'posts_per_page' => 10,
    //   'orderby' => 'title',
    //   'order' => 'ASC',
    //   'paged' => 1,
    // );

    // if (isset($request['category'])) {
    //   $args['category'] = $request['category'];
    // }

    // if (isset($request['limit'])) {
    //   $args['posts_per_page'] = $request['limit'];
    // }

    // if (isset($request['page'])) {
    //   $args['paged'] = $request['page'];
    // }

    // if (isset($request['orderby'])) {
    //   $args['orderby'] = $request['orderby'];
    // }

    // if (isset($request['order'])) {
    //   $args['order'] = $request['order'];
    // }

    // $products = wc_get_products($args);



    // $response = array(
    //   'status_code' => 200,
    //   'status' => 'success',
    //   'page' => $args['paged'],
    //   'product_count' => count($products),
    //   'total_products' => wp_count_posts('product')->publish,
    //   'products' => array_map(array($this, 'format_product'), $products),
    // );

    // // check if product exists
    // if (empty($products)) {
    //   $response['status_code'] = 404;
    //   $response['status'] = 'error';
    //   $response['message'] = __('No products found', 'szcs-coupon');
    // }

    $vendor_id = $request->get_header('vendor-id');

    $args = array(
      'role' => 'customer',
      'orderby' => 'registered',
      'order' => 'DESC',
      'number' => 10,
      'paged' => 1,
      'meta_query' => array(
        array(
          'key' => 'szcs_coupon_vendor_id',
          'value' => $vendor_id,
          'compare' => '=',
        ),
      ),
    );

    if (isset($request['limit'])) {
      $args['number'] = $request['limit'];
    }

    if (isset($request['page'])) {
      $args['paged'] = $request['page'];
    }

    $users = get_users($args);

    $response = array(
      'status_code' => 200,
      'status' => 'success',
      'page' => $args['paged'],
      'customer_count' => count($users),
      'total_customers' => count_users()['total_users'],
      'customers' => array_map(array($this, 'format_customer'), $users),
      'customers_raw' => $users,
    );

    // return product data
    return new WP_REST_Response($response, $response['status_code']);
  }

  public function api_register_user($request)
  {

    $voucher_no = "";

    $vendor_id = $request->get_header('vendor-id');

    $body = $request->get_body();



    if (empty($body)) {
      $response = array(
        'status_code' => 400,
        'status' => 'error',
        'message' => __('Invalid request', 'szcs-coupon')
      );
      return new WP_REST_Response($response, 400);
    }

    $body = json_decode($body);

    // if missing any required field
    if (!isset($body->voucher_no) || !isset($body->name) || !isset($body->email) || !isset($body->username) || !isset($body->mobile) || !isset($body->password)) {
      $response = array(
        'status_code' => 400,
        'status' => 'error',
        'message' => __('Invalid request', 'szcs-coupon')
      );
      return new WP_REST_Response($response, 400);
    }

    $response = array();

    $errors = array();

    global $wpdb, $szcs_coupon_voucher;

    if (empty($body->voucher_no)) {
      $errors[] = __('Voucher code is required', 'szcs-coupon');
    } else {

      $voucher_no = $wpdb->escape($body->voucher_no);
      $voucher = $szcs_coupon_voucher->validate_voucher($voucher_no, '', true);
      if ($voucher[0] !== 'valid') {
        $errors[] = __($voucher[2], 'szcs-coupon');
      } else {
        $claim_validation = szcs_coupon_can_redeem($voucher[1]);
        if ($claim_validation[0] !== 'success') {
          $errors[] = __($claim_validation[2], 'szcs-coupon');
        } elseif ($voucher[1]->vendor_id != $vendor_id) {
          $errors[] = __('Oops! Voucher number is invalid. Please check & try again!', 'szcs-coupon');
        }
      }
    }

    if (empty($body->name)) {
      $errors[] = __('Name is required', 'szcs-coupon');
    }

    if (empty($body->email)) {
      $errors[] = __('Email is required', 'szcs-coupon');
    } elseif (!is_email($body->email)) {
      $errors[] = __('Email is invalid', 'szcs-coupon');
    } elseif (email_exists($body->email)) {
      $errors[] = __('Email already exists', 'szcs-coupon');
    }

    if (empty($body->username)) {
      $errors[] = __('Username is required', 'szcs-coupon');
    } elseif (username_exists(sanitize_user($body->username))) {
      $errors[] = __('Username already exists', 'szcs-coupon');
    }

    if (empty($body->mobile)) {
      $errors[] = __('Mobile is required', 'szcs-coupon');
    } elseif (!preg_match('/^[1-9]{1}[0-9]{9}$/', $body->mobile)) {
      $errors[] = __('Mobile is invalid', 'szcs-coupon');
    }

    if (empty($body->password)) {
      $errors[] = __('Password is required', 'szcs-coupon');
    }

    if (empty($errors)) {

      $name = $body->name;
      $email = $body->email;
      $username = $body->username;
      $mobile = $body->mobile;
      $password = $body->password;

      $user_id = wp_create_user($username, $password, $email);

      if (!is_wp_error($user_id)) {
        wp_update_user(
          array(
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
            'role' => 'customer'
          )
        );

        update_user_meta($user_id, 'billing_phone', $mobile);
        // update_user_meta($user_id, 'digits_phone', '+91' . $mobile);
        // update_user_meta($user_id, 'digt_countrycode', '+91');
        // update_user_meta($user_id, 'digits_phone_no', $mobile);
        update_user_meta($user_id, 'szcs-voucher', $voucher_no);
        update_user_meta($user_id, 'szcs_coupon_vendor_id', $vendor_id);

        $voucher = $voucher[1];

        do_action('szcs_coupon_add_transaction', array(
          'user_id' => $user_id,
          'description' => "Vaucher Credited",
          'debit_points' => 0,
          'credit_points' => $voucher->voucher_amount,
          'voucher_id' => $voucher->voucher_id,
          'voucher_no' => $voucher->voucher_no,
          'status' => null,
        ));
      } else {
        $errors[] = __($user_id->get_error_message(), 'szcs-coupon');
      }
    }

    if (!empty($errors)) {
      $response = array(
        'status_code' => 400,
        'status' => 'error',
        'message' => $errors
      );
    } else {
      $response = array(
        'status_code' => 200,
        'status' => 'success',
        'message' => __('Customer created successfully', 'szcs-coupon'),
        'user_id' => $user_id,
      );
    }

    return new WP_REST_Response($response, $response['status_code']);
  }

  public function api_get_product_by_id($request)
  {
    $id = $request['id'];

    $args = array(
      'post_type' => 'product',
      'post_status' => 'publish',
      'posts_per_page' => 1,
      'p' => $id,
    );

    $product = wc_get_products($args);

    // $product = wc_get_product($id);
    $response = array(
      'status_code' => 200,
      'status' => 'success',
      'product' => empty($product) ? [] : $this->format_product($product[0]),
    );

    if (!$product) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = __('Product not found', 'szcs-coupon');
    }

    return new WP_REST_Response($response, $response['status_code']);;
  }


  public function api_get_products($request)
  {
    $args = array(
      'post_type' => 'product',
      'post_status' => 'publish',
      'posts_per_page' => 10,
      'orderby' => 'title',
      'order' => 'ASC',
      'paged' => 1,
    );

    if (isset($request['category'])) {
      $args['category'] = $request['category'];
    }

    if (isset($request['limit'])) {
      $args['posts_per_page'] = $request['limit'];
    }

    if (isset($request['page'])) {
      $args['paged'] = $request['page'];
    }

    if (isset($request['orderby'])) {
      $args['orderby'] = $request['orderby'];
    }

    if (isset($request['order'])) {
      $args['order'] = $request['order'];
    }

    $products = wc_get_products($args);



    $response = array(
      'status_code' => 200,
      'status' => 'success',
      'page' => $args['paged'],
      'product_count' => count($products),
      'total_products' => wp_count_posts('product')->publish,
      'products' => array_map(array($this, 'format_product'), $products),
    );

    // check if product exists
    if (empty($products)) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = __('No products found', 'szcs-coupon');
    }

    // return product data
    return new WP_REST_Response($response, $response['status_code']);
  }

  public function format_product($product)
  {


    if (is_a($product, 'WC_Product')) {
      $product_data = array(
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'description' => $product->get_description(),
        'price' => $product->get_price(),
        'status' => $product->get_status(),
        'categories' => array(),
        // 'tags' => array(),
        'images' => array(),
      );

      global $szcs_coupon_wc;

      if ($product->get_type() == 'variable') {

        $product_data['variations'] = array();

        foreach ($product->get_available_variations() as $variation) {
          $variation_data = array(
            'id' => $variation['variation_id'],
            'sku' => $variation['sku'],
            'attributes' => array(),
            'description' => $variation['variation_description'],
            'price' => $variation['display_price'],
            'stock_quantity' => $variation['max_qty'],
            'in_stock' => $variation['is_in_stock'],
            'image' => array(
              'src' => wp_get_attachment_image_src($variation['image_id'], 'full')[0],
            ),
            'coins' => $szcs_coupon_wc->wc_product_get_points_amount($variation['variation_id'], 'product'),
            'category_coins' => $szcs_coupon_wc->wc_product_get_points_amount($variation['variation_id'], 'category'),
            'brand_coins' => $szcs_coupon_wc->wc_product_get_points_amount($variation['variation_id'], 'brand'),
          );

          foreach ($variation['attributes'] as $attribute => $value) {
            $attribute_term = get_term_by('slug', $value, str_replace('attribute_', '', $attribute));
            $variation_data['attributes'][] = array(
              'id' => $attribute_term->term_id,
              'taxonomy' => str_replace('pa_', '', $attribute_term->taxonomy),
              'name' => $attribute_term->name,
              'option' => $attribute_term->slug,
            );
          }

          $product_data['variations'][] = $variation_data;
        }
      } else {
        $product_data['stock_quantity'] = $product->get_stock_quantity();
        $product_data['in_stock'] = $product->is_in_stock();
        $product_data['coins'] = $szcs_coupon_wc->wc_product_get_points_amount($product);
        $product_data['category_coins'] = $szcs_coupon_wc->wc_product_get_points_amount($product, 'category');
        $product_data['brand_coins'] = $szcs_coupon_wc->wc_product_get_points_amount($product, 'brand');
      }

      foreach ($product->get_category_ids() as $category_id) {
        $category = get_term($category_id, 'product_cat');
        $product_data['categories'][] = array(
          'id' => $category->term_id,
          'name' => $category->name,
        );
      }

      // foreach ($product->get_tag_ids() as $tag_id) {
      //   $tag = get_term($tag_id, 'product_tag');
      //   $product_data['tags'][] = array(
      //     'id' => $tag->term_id,
      //     'name' => $tag->name,
      //   );
      // }

      foreach ($product->get_gallery_image_ids() as $image_id) {
        $product_data['images'][] = array(
          'src' => wp_get_attachment_image_src($image_id, 'full')[0],
        );
      }

      // product_brand
      $product_brand = get_the_terms($product->get_id(), 'product_brand');
      if ($product_brand) {
        $product_data['brand'] = array(
          'id' => $product_brand[0]->term_id,
          'name' => $product_brand[0]->name,
        );
      }

      return $product_data;
    } else {
      return new WP_Error('my_custom_endpoint_error', 'Product not found', array('status' => 404));
    }
  }

  public function format_customer($customer)
  {
    $customer_data = array(
      'id' => $customer->get_id(),
      'username' => $customer->get_username(),
      'email' => $customer->get_email(),
      'first_name' => $customer->get_first_name(),
      'last_name' => $customer->get_last_name(),
      'display_name' => $customer->get_display_name(),
      'role' => $customer->get_role(),
      'avatar' => get_avatar_url($customer->get_id()),
      'points' => get_user_meta($customer->get_id(), 'szcs_coupon_points', true),
      'total_spent' => $customer->get_total_spent(),
      'total_orders' => $customer->get_order_count(),
      'total_products' => $customer->get_total_products(),
      'total_reviews' => $customer->get_total_reviews(),
      'total_downloads' => $customer->get_total_downloads(),
      'total_refunds' => $customer->get_total_refunds(),
      'total_refunded' => $customer->get_total_refunded(),
      'total_tax' => $customer->get_total_tax(),
      'total_shipping' => $customer->get_total_shipping(),
      'total_discount' => $customer->get_total_discount(),
      'total_discount_tax' => $customer->get_total_discount_tax(),
      'total_cart_tax' => $customer->get_total_cart_tax(),
      'total_fees' => $customer->get_total_fees(),
      'total_coupons' => $customer->get_total_coupons(),
      'total_sales' => $customer->get_total_sales(),
      'total_credits' => $customer->get_total_credits(),
      'total_credits_used' => $customer->get_total_credits_used(),
      'total_credits_earned' => $customer->get_total_credits_earned(),
      'total_credits_refunded' => $customer->get_total_credits_refunded(),
      'total_credits_refunded_tax' => $customer->get_total_credits_refunded_tax(),
      'total_credits_tax' => $customer->get_total_credits_tax(),
      'total_credits_shipping' => $customer->get_total_credits_shipping(),
      'total_credits_discount' => $customer->get_total_credits_discount(),
    );

    return $customer_data;
  }
}


SzCsCouponRestApi::instance();
