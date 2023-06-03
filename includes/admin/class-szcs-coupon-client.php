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
          $this->products_html($vendor);
          // } else if($active_tab == 'categories') {
          //   $this->categories_html($vendor);
          // } else if($active_tab == 'brands') {
          //   $this->brands_html($vendor);
        }
      }
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

    public function products_html($vendor)
    {
      require_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-client-products.php';
      $this->products_table = new SzCs_Coupon_Client_Products($vendor);
      $this->products_table->prepare_items();
    ?>
      <h2><?php _e('Products', 'szcs-coupon'); ?></h2>
      <form id="coupon-products" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
        <input type="hidden" name="client" value="<?php echo $_REQUEST['client'] ?>" />
        <?php $this->products_table->search_box(__('Search', 'szcs-coupon'), 'search_id'); ?>
        <?php $this->products_table->views(); ?>
        <?php $this->products_table->display(); ?>
      </form>
  <?php
    }
  }


  SzCsCouponClient::instance();
