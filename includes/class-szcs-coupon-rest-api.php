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
          <span class="" id="szcs_coupon_user_id"><?= $user->ID ?></span>
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

  public function api_get_product_by_id($request)
  {
    $id = $request['id'];
    $product = wc_get_product($id);
    $response = array(
      'status_code' => 200,
      'status' => 'success',
      'product' => empty($product) ? [] : $this->format_product($product),
    );

    if (!$product) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = __('Product not found', 'szcs-coupon');
    }

    return $response;
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
      'products' => array_map(array($this, 'format_product'), $products),
    );

    // check if product exists
    if (empty($products)) {
      $response['status_code'] = 404;
      $response['status'] = 'error';
      $response['message'] = __('No products found', 'szcs-coupon');
    }

    // return product data
    wp_send_json($response);
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
}


SzCsCouponRestApi::instance();
