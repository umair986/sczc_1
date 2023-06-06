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
        <th><label for="szcs-coupon-user-id"><?php _e('Client ID', 'szcs-coupon'); ?></label></th>
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

    // place orders
    register_rest_route(
      'v1',
      '/order_create',
      array(
        'methods' => 'POST',
        'callback' => array($this, 'api_place_order'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );

    // get orders
    register_rest_route(
      'v1',
      '/orders',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'api_get_orders'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );

    // redeem coupon
    register_rest_route(
      'v1',
      '/redeem_voucher',
      array(
        'methods' => 'POST',
        'callback' => array($this, 'api_redeem_coupon'),
        'permission_callback' => array($this, 'api_authenticate'),
      )
    );
  }


  public function api_authenticate($request)
  {
    $api_key = $request->get_header('x-api-key');
    $vendor_id = $request->get_header('client-id');

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

  public function api_redeem_coupon($request)
  {

    $params = $request->get_params();
    $vendor_id = $request->get_header('client-id');

    $errors = '';

    if (!isset($params['voucher_no']) || !isset($params['customer_id'])) {
      return new WP_REST_Response(
        array(
          'status_code' => 400,
          'status' => 'error',
          'message' => __('Invalid request', 'szcs-coupon'),
        ),
        400
      );
    }

    $response = array(
      'status_code' => 200,
      'status' => 'success',
      'message' => __('Coupon redeemed successfully', 'szcs-coupon'),
    );

    $voucher_no = $params['voucher_no'];
    $customer_id = $params['customer_id'];

    // check if customer exists under this vendor
    $user_args = array(
      'role' => 'customer',
      'meta_query' => array(
        array(
          'key' => 'szcs_coupon_vendor_id',
          'value' => $vendor_id,
          'compare' => '='
        )
      ),
      'search' => $customer_id,
      'search_columns' => array('ID')
    );

    $users = get_users($user_args);

    if (empty($users)) {
      $response['status_code'] = 400;
      $response['status'] = 'error';
      $response['message'] = __('Customer not found', 'szcs-coupon');
      return new WP_REST_Response($response, 400);
    }

    global  $szcs_coupon_voucher, $szcs_coupon_wallet;


    if (empty($voucher_no)) {
      return new WP_REST_Response(
        array(
          'status_code' => 400,
          'status' => 'error',
          'message' => __('Voucher number is required', 'szcs-coupon'),
        ),
        400
      );
    } else {
      $voucher = $szcs_coupon_voucher->validate_voucher($voucher_no, '', true);
      if ($voucher[0] !== 'valid') {
        $errors = __($voucher[2], 'szcs-coupon');
      } else {
        $claim_validation = szcs_coupon_can_redeem($voucher[1]);
        if ($claim_validation[0] !== 'success') {
          $errors = __($claim_validation[2], 'szcs-coupon');
        } elseif ($voucher[1]->vendor_id != $vendor_id) {
          $errors = __('Oops! Voucher number is invalid. Please check & try again!', 'szcs-coupon');
        }
      }
    }

    if (!empty($errors)) {
      $response['status_code'] = 400;
      $response['status'] = 'error';
      $response['message'] = $errors;
      return new WP_REST_Response($response, 400);
    }

    $voucher = $voucher[1];

    do_action('szcs_coupon_add_transaction', array(
      'user_id' => $customer_id,
      'description' => "Vaucher Credited",
      'debit_points' => 0,
      'credit_points' => $voucher->voucher_amount,
      'voucher_id' => $voucher->voucher_id,
      'voucher_no' => $voucher->voucher_no,
      'status' => null,
    ));

    $updated_balance = (int) $szcs_coupon_wallet->get_balance($customer_id);

    $response['message'] = __('Yay! Your account has been credited with ' . $voucher->voucher_amount . ' of coins!', 'szcs-coupon');

    $response['data'] = array(
      'balance' => $updated_balance,
    );

    return new WP_REST_Response($response, 200);
  }

  public function api_get_orders($request)
  {

    $params = $request->get_params();
    $vendor_id = $request->get_header('client-id');

    $response = array(
      'status_code' => 200,
      'status' => 'success',
    );


    $args = array(
      'post_type' => 'shop_order',
      'post_status' => array_keys(wc_get_order_statuses()),
      'posts_per_page' => 10,
      'paged' => 1,
      'meta_key' => 'client_id',
      'meta_value' => $vendor_id,
      'meta_compare' => '=',
      'return' => 'ids'
    );

    if (isset($params['order_id'])) {
      $args['post__in'] = array($params['order_id']);
    }

    if (isset($params['status'])) {
      $args['status'] = $params['status'];
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

    if (isset($request['sortby'])) {
      $args['order'] = $request['sortby'];
    }

    $query = new WC_Order_Query($args);

    $order_data = array_map(array($this, 'format_orders'), $query->get_orders());

    $response['page'] = $args['paged'];
    $response['order_count'] = count($order_data);
    $response['orders'] = $order_data;
    // }

    return new WP_REST_Response($response,  200);
  }

  public function api_place_order($request)
  {
    $params = $request->get_params();
    $vendor_id = $request->get_header('client-id');

    if (!isset($params['customer_id']) || !isset($params['product_id']) || !isset($params['quantity']) || !isset($params['address']) || !isset($params['city']) || !isset($params['state']) || !isset($params['postal-code']) || !isset($params['country']) || !isset($params['phone'])) {
      return new WP_Error('invalid_request', __('Invalid request', 'szcs-coupon'), array('status' => 400));
    }

    $user_id = $params['customer_id'];
    $product_id = $params['product_id'];
    $quantity = $params['quantity'];



    $order_note = isset($params['order_note']) ? $params['order_note'] : '';

    $response = array(
      'status_code' => 200,
      'status' => 'success',
    );


    // chck if customer with provided id exist under vernder
    $user_args = array(
      'role' => 'customer',
      'meta_query' => array(
        array(
          'key' => 'szcs_coupon_vendor_id',
          'value' => $vendor_id,
          'compare' => '='
        )
      ),
      'search' => $user_id,
      'search_columns' => array('ID')
    );

    $user_query = get_users($user_args);

    // check for valid user
    if (!$user_query) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = 'Customer not found';
      return new WP_REST_Response($response, 404);
    }

    // check for valid product
    $product = wc_get_product($product_id);
    if (!$product) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = 'Product not found';
      return new WP_REST_Response($response, 404);
    }

    // check for valid quantity
    if ($quantity < 1) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = 'Invalid quantity';
      return new WP_REST_Response($response, 404);
    }

    // check if it is a variable product
    if ($product->is_type('variable')) {

      // if yes get the variations
      $variations = $product->get_available_variations();

      // check if the product is out of stock
      if (empty($variations)) {
        $response['status_code'] = 404;
        $response['status'] = 'error';
        $response['message'] = 'Product out of stock';
        return new WP_REST_Response($response, 404);
      }
      $variation_id = $variations[0]['variation_id'];

      // replace the product with the variation
      $product = wc_get_product($variation_id);
    }

    $stock = $product->get_stock_quantity();

    if ($stock < $quantity) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = 'Product out of stock';
      return new WP_REST_Response($response, 404);
    }

    global $szcs_coupon_wc, $szcs_coupon_wallet;

    $product_points = $szcs_coupon_wc->wc_product_get_vendor_points_amount($product, $vendor_id);

    $points = $product_points;

    $points_balance = (int) $szcs_coupon_wallet->get_balance($user_id);

    $product_price = $product->get_price();

    $order = wc_create_order();
    $order->set_customer_id($user_id);

    $order->add_product($product, $quantity, array(
      'subtotal' => ($product_price * $quantity) - $points,
      'total' => ($product_price * $quantity) - $points,
    ));
    $address = $params['address'];
    $city = $params['city'];
    $state = $params['state'];
    $postcode = $params['postal-code'];
    $country = $params['country'];
    $phone = $params['phone'];
    $email = isset($params['email']) ? $params['email'] : $user_query[0]->user_email;
    $name = isset($params['name']) ? $params['name'] : $user_query[0]->display_name;

    $order->set_billing_address_1($address);
    $order->set_billing_city($city);
    $order->set_billing_country($country);
    $order->set_billing_email($email);
    $order->set_billing_first_name($name);
    $order->set_billing_phone($phone);
    $order->set_billing_postcode($postcode);
    $order->set_billing_state($state);

    $order->set_shipping_address_1($address);
    $order->set_shipping_city($city);
    $order->set_shipping_country($country);
    $order->set_shipping_first_name($name);
    $order->set_shipping_phone($phone);
    $order->set_shipping_postcode($postcode);
    $order->set_shipping_state($state);



    $order->set_customer_note($order_note);
    $order->calculate_totals();

    $order->update_meta_data('points', $points);
    $order->update_meta_data('client_id', $vendor_id);

    if ($points > $points_balance) {
      $order->update_status('failed', 'Insufficient Coins');
      $order->save();
      $response['status_code'] = 402;
      $response['status'] = 'error';
      $response['message'] = 'Insufficient coins';
      return new WP_REST_Response($response, 404);
    }

    $order->update_status('processing');

    $order->update_meta_data('point_status', 'redeemed');

    $order_id = $order->get_id();

    $order->save();

    // wc_update_product_stock($product, $quantity, 'decrease');

    update_post_meta($order_id, '_stock_reduction_done', true);

    $response['order_id'] = $order_id;

    do_action('szcs_coupon_add_transaction', array(
      'description' => "Redeemed for order #$order_id",
      'debit_points' => $points,
      'user_id' => $user_id,
    ));

    return new WP_REST_Response($response, 200);
  }

  public function api_get_customers($request)
  {

    $vendor_id = $request->get_header('client-id');

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

    if (isset($request['customer'])) { // $resquest['user']  can be user_login or email 
      $args['search'] = $request['customer'];
      $args['search_columns'] = array('user_login', 'user_email');
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
      // 'total_customers' => count_users()['total_users'],
      'customers' => array_map(array($this, 'format_customer'), $users),
    );

    // return product data
    return new WP_REST_Response($response, $response['status_code']);
  }

  public function api_register_user($request)
  {

    $voucher_no = "";

    $vendor_id = $request->get_header('client-id');

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
    if (!isset($body->voucher_no) || !isset($body->name) || !isset($body->email) || !isset($body->username) || !isset($body->mobile)) {
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

    if (empty($errors)) {

      $name = $body->name;
      $email = $body->email;
      $username = $body->username;
      $mobile = $body->mobile;
      $password = wp_generate_password(12, false);

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
    $client_id = $request->get_header('client-id');

    $filtered_ids = $this->get_filtered_ids($client_id);

    if (!in_array($id, $filtered_ids['include']) && !empty($filtered_ids['include'])) {
      $response = array(
        'status_code' => 403,
        'status' => 'error',
        'message' => __('You are not allowed to access this product', 'szcs-coupon')
      );
      return new WP_REST_Response($response, 403);
    }

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
      'product' => empty($product) ? [] : $this->format_product($product[0], $client_id),
    );

    if (!$product) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = __('Product not found', 'szcs-coupon');
    }

    return new WP_REST_Response($response, $response['status_code']);;
  }


  public function api_get_products(WP_REST_Request $request)
  {

    $vendor_id = $request->get_header('client_id');

    $filtered_ids = $this->get_filtered_ids($vendor_id);

    $args = array(
      'post_type' => 'product',
      'post_status' => 'publish',
      'posts_per_page' => 10,
      'orderby' => 'title',
      'order' => 'ASC',
      'paged' => 1,
      'type' => array('simple', 'variable'),
      'include' => $filtered_ids['include'],
      'exclude' => $filtered_ids['exclude'],
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
      'products' => array_map(function ($product) use ($vendor_id) {
        return $this->format_product($product, $vendor_id);
      }, $products),
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

  public function format_product($product, $vendor_id)
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
            'coins_required' => $szcs_coupon_wc->wc_product_get_vendor_points_amount($variation['variation_id'], $vendor_id),
            // 'coins' => $szcs_coupon_wc->wc_product_get_points_amount($variation['variation_id'], 'product'),
            // 'category_coins' => $szcs_coupon_wc->wc_product_get_points_amount($variation['variation_id'], 'category'),
            // 'brand_coins' => $szcs_coupon_wc->wc_product_get_points_amount($variation['variation_id'], 'brand'),
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

  public function format_customer($user)
  {
    $customer = new WC_Customer($user->ID);

    $customer_data = array(
      'id' => $customer->get_id(),
      'username' => $customer->get_username(),
      'email' => $customer->get_email(),
      'display_name' => $customer->get_display_name(),
      'avatar' => get_avatar_url($customer->get_id()),
      'points' => get_user_meta($customer->get_id(), 'szcs_coupon_points', true),
      'total_orders' => $customer->get_order_count(),
    );

    return $customer_data;
  }

  public function format_orders($order_id)
  {

    $order = wc_get_order($order_id);

    $order_data = array(
      'id' => $order->get_id(),
      'custumer_id' => $order->get_customer_id(),
      'status' => $order->get_status(),
      'order_time' => $order->get_date_created()->date('Y-m-d H:i:s'),
      'items' => array(),
      'shipping_address' => array(
        'name' => $order->get_shipping_first_name(),
        'address' => $order->get_shipping_address_1(),
        'city' => $order->get_shipping_city(),
        'state' => $order->get_shipping_state(),
        'postcode' => $order->get_shipping_postcode(),
        'country' => $order->get_shipping_country(),
      ),
      'billing_address' => array(
        'name' => $order->get_billing_first_name(),
        'address' => $order->get_billing_address_1(),
        'phone' => $order->get_billing_phone(),
        'email' => $order->get_billing_email(),
        'city' => $order->get_billing_city(),
        'state' => $order->get_billing_state(),
        'postcode' => $order->get_billing_postcode(),
        'country' => $order->get_billing_country(),
      ),
      'coins' => get_post_meta($order->get_id(), 'points', true),
      'coins_status' => get_post_meta($order->get_id(), 'point_status', true),
      'total' => $order->get_total(),
    );

    foreach ($order->get_items() as $item) {

      $attributes = array();

      if ($item->get_meta('pa_color')) {
        $color = $item->get_meta('pa_color');
        $attributes['color'] = $color;
      }

      if ($item->get_meta('pa_size')) {
        $size = $item->get_meta('pa_size');
        $attributes['size'] = $size;
      }

      $item_data = array(
        'id' => $item->get_id(),
        'name' => $item->get_name(),
        'product_id' => $item->get_product_id(),
        'quantity' => $item->get_quantity(),
        'subtotal' => $item->get_subtotal(),
        'total' => $item->get_total(),
      );


      // Mark the stock reduction as done for this order
      if ($attributes) {
        $item_data['attributes'] = $attributes;
      }

      $order_data['items'][] = $item_data;
    }


    return $order_data;
  }

  protected function get_filtered_ids($vendor_id)
  {

    // get products brands by term meta
    $brands_exclude = get_terms(array(
      'taxonomy' => 'product_brand',
      'hide_empty' => false,
      'meta_query' => array(
        array(
          'key' => 'szcs_product_brand_query_field-v-' . $vendor_id,
          'value' => 'exclude',
          'compare' => '='
        ),
      ),
      'fields' => 'ids',
    ));

    $cat_include = get_terms(array(
      'taxonomy' => 'product_cat',
      'hide_empty' => true,
      'meta_query' => array(
        array(
          'key' => 'szcs_product_cat_query_field-v-' . $vendor_id,
          'value' => 'include',
          'compare' => '='
        ),
      ),
      'fields' => 'ids',
    ));

    $cat_exclude = get_terms(array(
      'taxonomy' => 'product_cat',
      'hide_empty' => true,
      'meta_query' => array(
        array(
          'key' => 'szcs_product_cat_query_field-v-' . $vendor_id,
          'value' => 'exclude',
          'compare' => '='
        ),
      ),
      'fields' => 'ids',
    ));

    $product_include1A = get_posts(
      array(
        'post_status' => 'publish',
        'post_type' => array('product', 'product_variation'),
        'fields' => 'id=>parent',
        'posts_per_page' => -1,
        'meta_query' => array(
          array(
            'key' => 'szcs_product_query_field-v-' . $vendor_id,
            'value' => 'include',
            'compare' => '='
          ),
        )
      )
    );

    $product_include1 = [];

    foreach ($product_include1A as $key => $value) {
      if ($value == 0) {
        $product_include1[] = $key;
      } else {
        $product_include1[] = $value;
      }
    }

    $product_excludeA = get_posts(
      array(
        'post_status' => 'publish',
        'post_type' => array('product', 'product_variation'),
        'fields' => 'id=>parent',
        'posts_per_page' => -1,
        'meta_query' => array(
          array(
            'key' => 'szcs_product_query_field-v-' . $vendor_id,
            'value' => 'exclude',
            'compare' => '='
          ),
        )
      )
    );

    $product_exclude = [];

    foreach ($product_excludeA as $key => $value) {
      if ($value == 0) {
        $product_exclude[] = $key;
      } else {
        $product_exclude[] = $value;
      }
    }

    $product_include2 = get_posts(
      array(
        'post_status' => 'publish',
        'post_type' => 'product',
        'fields' => 'ids',
        'posts_per_page' => -1,
        'tax_query' => array(
          array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $cat_include,
            'operator' => 'IN'
          )
        ),
      )
    );

    $product_include3 = get_posts(
      array(
        'post_status' => 'publish',
        'post_type' => array('product'),
        'fields' => 'ids',
        'posts_per_page' => -1,
        'tax_query' => array(
          array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $cat_exclude,
            'operator' => 'NOT IN'
          ),
          [
            'taxonomy' => 'product_brand',
            'field' => 'term_id',
            'terms' => $brands_exclude,
            'operator' => 'NOT IN'
          ]
        ),
      )
    );


    $product_include = array_unique(array_merge($product_include1, $product_include2, $product_include3));

    // remove excluded products from include
    $product_include = array_values(array_diff($product_include, $product_exclude));

    return [
      'include' => $product_include,
      'exclude' => $product_exclude,
    ];
  }
}


SzCsCouponRestApi::instance();
