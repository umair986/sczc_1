<?php

/**
 * Coupon settings page file
 *
 * @package SzCsCoupon
 */
class SzCsCouponClient
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponClient
   * @since 1.3.0
   */
  protected static $_instance = null;

  protected $balance_details_table = null;

  protected $products_table = null;

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
    add_action('szcs_admin_menu', array($this, 'admin_menu'), 40);
    add_action('wp_ajax_szcs_coupon_client_update_products', array($this, 'szcs_coupon_client_update_products'));
    add_action('wp_ajax_szcs_coupon_client_update_product_cat', array($this, 'szcs_coupon_client_update_product_cat'));
    add_action('wp_ajax_szcs_coupon_client_update_product_brand', array($this, 'szcs_coupon_client_update_product_brand'));
  }

  public function admin_menu($parent_slug)
  {
    add_submenu_page(
      $parent_slug,
      __('Clients coin management', 'szcs-coupon'),
      __('Client', 'szcs-coupon'),
      get_szcs_coupon_user_capability(),
      'szcs-coupon-client',
      [$this, 'page_html'],
    );
  }


  /**
   * Display coupon settings page
   */
  public function page_html()
  {
    // check user capabilities
    if (!current_user_can('manage_options')) {
      return;
    }

    $vendor_id = isset($_GET['client']) ? sanitize_text_field($_GET['client']) : '';
    $vendor = null;

    if (!empty($vendor_id)) {
      // get user by id if role is vendor
      $vendor = get_userdata($vendor_id);

      // redirect if user is not vendor
      if (!in_array('vendor', $vendor->roles)) {
        wp_redirect(admin_url('admin.php?page=szcs-coupon-client'));
        exit;
      }
    }


?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?>
        <?php if ($vendor) : ?>
          <p><?php echo $vendor->display_name; ?> <a href="<?php echo admin_url('admin.php?page=szcs-coupon-client') ?>" style="text-decoration: none;"><span class="dashicons dashicons-edit"></span></a></p>
        <?php endif; ?>
      </h1>
      <?php if (empty($vendor)) {
        $this->select_client_html();
      } else {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        $tabs = [
          [
            'page' => 'szcs-coupon-client',
            'slug' => 'users',
            'label' => __('Users', 'szcs-coupon'),
          ],
          [
            'page' => 'szcs-coupon-client',
            'slug' => 'products',
            'label' => __('Products', 'szcs-coupon'),
          ],
          [
            'page' => 'szcs-coupon-client',
            'slug' => 'categories',
            'label' => __('Categories', 'szcs-coupon'),
          ],
          [
            'page' => 'szcs-coupon-client',
            'slug' => 'brands',
            'label' => __('Brands', 'szcs-coupon'),
          ]
        ];
      ?>
        <h2 class="nav-tab-wrapper">
          <?php foreach ($tabs as $tab) : ?>
            <?php
            $link = add_query_arg(
              array(
                'page' => $tab['page'],
                'tab' => $tab['slug'],
                'client' => $_GET['client']
              ),
              admin_url('admin.php')
            );

            ?>
            <a href="<?php echo $link; ?>" data-target="#tab-<?php echo $tab['slug']; ?>" class="nav-tab <?php echo $active_tab === $tab['slug'] ? 'nav-tab-active' : ''; ?>">
              <?php echo $tab['label']; ?>
            </a>
          <?php endforeach; ?>
        </h2>
      <?php
        if ($active_tab == 'users') {
          $this->users_html($vendor);
        } else if ($active_tab == 'products') {
          require_once SZCS_COUPON_ABSPATH . 'templates/clients-product-page.php';
        } else if ($active_tab == 'categories') {
          require_once SZCS_COUPON_ABSPATH . 'templates/clients-product-cat-page.php';
        } else if ($active_tab == 'brands') {
          require_once SZCS_COUPON_ABSPATH . 'templates/clients-product-brand-page.php';
        }
      } ?>

    </div>
  <?php
  }

  private function select_client_html()
  {
    $get_vendors = get_users(array(
      'role' => 'vendor',
      'orderby' => 'display_name',
      'order' => 'ASC',
      'fields' => array('ID', 'display_name')
    ));
  ?>
    <table class="form-table szcs_coupon" role="presentation">
      <tbody>
        <tr>
          <th scope="row">
            <label for="vendor">Select Client to continue</label>
          </th>
          <td>
            <select name="vendor" id="vendor">
              <option value="" disabled hidden selected>Select client</option>
              <?php foreach ($get_vendors as $vendor) : ?>
                <option value="<?php echo $vendor->ID; ?>"><?php echo $vendor->display_name; ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>

      </tbody>

    </table>
    <script type="text/javascript">
      jQuery(function($) {
        $('#vendor').change(function() {
          if ($(this).val() != '') {
            window.location.href = '<?php echo admin_url('admin.php?page=szcs-coupon-client&tab=users&client='); ?>' + $(this).val();
          }
        })
      })
    </script>
  <?php
  }

  public function users_html($vendor)
  {
    require_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-client-users-balance-details.php';
    $this->balance_details_table = new SzCs_Coupon_Client_User_Balance_Details($vendor);
    $this->balance_details_table->prepare_items();
  ?>
    <h2><?php _e('Customers', 'szcs-coupon'); ?></h2>
    <form id="coupon-users" method="get">
      <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
      <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
      <input type="hidden" name="client" value="<?php echo $_REQUEST['client'] ?>" />
      <?php $this->balance_details_table->search_box(__('Search', 'szcs-coupon'), 'search_id'); ?>
    </form>
    <?php // $this->balance_details_table->views(); 
    ?>
    <?php $this->balance_details_table->display(); ?>

<?php

  }

  public function szcs_coupon_client_update_products()
  {
    $vendor = $_POST['vendor'];
    $data = $_POST['data'];
    $products = array();
    $res = array();
    global $szcs_coupon_wc;
    foreach ($data as $item) {
      if (preg_match('/^product-points\[(\d+)\]$/', $item['name'], $matches)) {
        $products[$matches[1]]['product-points'] = ltrim($item['value'], '0');
      } else if (preg_match('/^query\[(\d+)\]$/', $item['name'], $matches)) {
        $products[$matches[1]]['query'] = $item['value'];
      }
    }
    // update_post_meta(54189, 'szcs_product_points_field-v-' . $this->vendor->ID, 70);
    // delete_post_meta(54189, 'szcs_product_points_field-v-' . $this->vendor->ID);
    // update_post_meta($item['id'], 'szcs_product_query_field-v-' . $this->vendor->ID, $item['query']);
    // delete_post_meta($item['id'], 'szcs_product_query_field-v-' . $this->vendor->ID);
    foreach ($products as $product_id => $product) {
      if (isset($product['product-points'])) {
        $szcs_coupon_wc->wc_product_set_vendor_points_percent($product_id, $vendor, $product['product-points']);
      }
      if (isset($product['query']) && ($product['query'] === 'include' || $product['query'] === 'exclude')) {
        update_post_meta($product_id, 'szcs_product_query_field-v-' . $vendor, $product['query']);
      } else {
        delete_post_meta($product_id, 'szcs_product_query_field-v-' . $vendor);
      }
      $res[$product_id] = $szcs_coupon_wc->wc_product_get_vendor_points_percent($product_id, $vendor);
    }
    wp_send_json_success($res);
  }

  public function szcs_coupon_client_update_product_cat()
  {
    $vendor = $_POST['vendor'];
    $data = $_POST['data'];
    $cats = array();
    $res = array();
    global $szcs_coupon_wc;
    foreach ($data as $item) {
      if (preg_match('/^product-cat-points\[(\d+)\]$/', $item['name'], $matches)) {
        $cats[$matches[1]]['product-cat-points'] = ltrim($item['value'], '0');
      } else if (preg_match('/^query\[(\d+)\]$/', $item['name'], $matches)) {
        $cats[$matches[1]]['query'] = $item['value'];
      }
    }
    foreach ($cats as $cat_id => $cat) {
      if (isset($cat['product-cat-points'])) {
        $szcs_coupon_wc->wc_product_cat_set_vendor_points_percent($cat_id, $vendor, $cat['product-cat-points']);
      }
      if (isset($cat['query']) && ($cat['query'] === 'include' || $cat['query'] === 'exclude')) {
        update_term_meta($cat_id, 'szcs_product_cat_query_field-v-' . $vendor, $cat['query']);
      } else {
        delete_term_meta($cat_id, 'szcs_product_cat_query_field-v-' . $vendor);
      }
      $res[$cat_id] = $szcs_coupon_wc->wc_product_cat_get_vendor_points_percent($cat_id, $vendor);
    }
    wp_send_json_success($res);
  }
  public function szcs_coupon_client_update_product_brand()
  {
    $vendor = $_POST['vendor'];
    $data = $_POST['data'];
    $brands = array();
    $res = array();
    global $szcs_coupon_wc;
    foreach ($data as $item) {
      if (preg_match('/^product-brand-points\[(\d+)\]$/', $item['name'], $matches)) {
        $brands[$matches[1]]['product-brand-points'] = ltrim($item['value'], '0');
      } else if (preg_match('/^query\[(\d+)\]$/', $item['name'], $matches)) {
        $brands[$matches[1]]['query'] = $item['value'];
      }
    }
    foreach ($brands as $brand_id => $brand) {
      if (isset($brand['product-brand-points'])) {
        $szcs_coupon_wc->wc_product_brand_set_vendor_points_percent($brand_id, $vendor, $brand['product-brand-points']);
      }
      if (isset($brand['query']) && ($brand['query'] === 'include' || $brand['query'] === 'exclude')) {
        update_term_meta($brand_id, 'szcs_product_brand_query_field-v-' . $vendor, $brand['query']);
      } else {
        delete_term_meta($brand_id, 'szcs_product_brand_query_field-v-' . $vendor);
      }
      $res[$brand_id] = $szcs_coupon_wc->wc_product_brand_get_vendor_points_percent($brand_id, $vendor);
    }
    wp_send_json_success($res);
  }
}


SzCsCouponClient::instance();
