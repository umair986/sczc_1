<?php

/**
 *
 * Coupon balance details.
 *
 * @package SzCsCoupon
 */

if (!class_exists('WP_List_Table')) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Coupon transaction details wp table class.
 */
class SzCs_Coupon_Client_Products extends WP_List_Table
{


  protected $vendor = null;

  protected $products = null;

  protected $total_items = 0;

  protected $page = 1;

  /**
   * Get data.
   */

  public function __construct(WP_User $vendor)
  {
    parent::__construct([
      'singular' => __('Product', 'szcs-coupon'),
      'plural' => __('Products', 'szcs-coupon'),
      'ajax' => false
    ]);

    $this->vendor = $vendor;
  }


  private function get_data()
  {

    if ($this->products) return $this->products;

    global $wpdb, $szcs_coupon_wc;

    $args = array(
      'post_type' => 'product',
      'posts_per_page' =>  $this->get_items_per_page('users_per_page', 15),
      'paged' => $this->get_pagenum(),
      'post_status' => 'publish',
      'fields' => 'ids',
    );

    if (isset($_GET['s']) && !empty($_GET['s'])) {
      $args['s'] = $_GET['s'];
    }

    $all_products = new WP_Query($args);

    $this->total_items = $all_products->found_posts;

    $products = array();

    while ($all_products->have_posts()) {
      $all_products->the_post();
      $product = wc_get_product(get_the_ID());
      if ($product->get_type() == 'simple') {
        $data = $product->get_data();
      } else if ($product->get_type() == 'variable') {
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {
          $variation_product = wc_get_product($variation['variation_id']);
          $data = $variation_product->get_data();
        }
      }

      $data['points'] = $szcs_coupon_wc->wc_product_get_vendor_points_percent($data['id'], $this->vendor->ID);
      $data['edit_link'] = get_edit_post_link($product->get_id());
      $products[] = $data;
    }

    $this->products = $products;
    return $products;
  }

  /**
   * Get columns.
   */
  public function get_columns()
  {
    return apply_filters(
      'szcs_coupon_balance_details_columns',
      array(
        // 'cb'       => __('cb', 'szcs-coupon'),
        'id'       => __('ID', 'szcs-coupon'),
        'name' => __('Name', 'szcs-coupon'),
        'price' => __('Price', 'szcs-coupon'),
        'points' => __('Points(%)', 'szcs-coupon'),
        'modify' => __('Modify Points(%)', 'szcs-coupon'),
        'query'  => __('Query', 'szcs-coupon'),
      )
    );
  }

  /**
   * Output 'no users' message.
   *
   * @since 3.1.0
   */
  public function no_items()
  {
    esc_html_e('No products found.', 'szcs-coupon');
  }
  /**
   * Prepare the items for the table to process
   */
  function prepare_items()
  {
    $current_page = $this->get_pagenum();
    $per_page = $this->get_items_per_page('users_per_page', 15);

    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array($columns, $hidden, $sortable, 'name');

    $data = $this->get_data();
    $total_items = $this->total_items;

    usort($data, array($this, 'usort_reorder'));
    //$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);


    $this->set_pagination_args(array(
      'total_items' => $total_items,
      'per_page'    => $per_page,
    ));

    $this->items = $data;
  }

  private function usort_reorder($a, $b)
  {
    // If no sort, default to title
    $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'user_login';
    // If no order, default to asc
    $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
    // Determine sort order
    $result = strcmp($a[$orderby], $b[$orderby]);
    // Send final sort direction to usort
    return ($order === 'asc') ? $result : -$result;
  }

  /**
   * Return an associative array listing all the views that can be used
   * with this table.
   *
   * Provides a list of roles and user count for that role for easy
   * Filtersing of the user table.
   *
   * @since  1.3.8
   *
   * @global string $role
   *
   * @return array An array of HTML links, one for each view.
   */
  protected function get_viewsSKIP()
  {

    $url           = 'admin.php?page=szcs-coupon-users';

    $users_args = array(
      'role__in' => array('subscriber', 'customer'),
      'meta_query' => array(
        array(
          'key' => 'szcs_coupon_vendor_id',
          'value' => $this->vendor->ID,
          'compare' => '='
        )
      )
    );


    $total_users = count(get_users($users_args));


    $current_link            = (!empty($_REQUEST['role']) ? wp_unslash($_REQUEST['role']) : 'all');
    $current_link_attributes = ('all' === $current_link) ? ' class="current" aria-current="page"' : '';

    $role_links = array();
    /* translators: Total user */
    $role_links['all'] = "<a href='$url'$current_link_attributes>" . sprintf(_nx('Total <span class="count">(%s)</span>', 'Total <span class="count">(%s)</span>', $total_users, 'users', 'szcs-coupon'), number_format_i18n($total_users)) . '</a>';


    return $role_links;
  }

  /**
   * Output extra table controls.
   *
   * @since 1.3.8
   *
   * @param string $which Whether this is being invoked above ("top")
   *                      or below the table ("bottom").
   */


  protected function extra_tablenavSKIP($which)
  {
    if ('top' === $which) {
      /* translators: SzCsCommerce currency */
      echo (sprintf("<label class='alignleft actions bulkactions'>%s(%s): <input name='amount' type='number' step='0.01' id='amount'></input></label>", esc_html__('Amount', 'szcs-coupon'), get_woocommerce_currency_symbol())); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo (sprintf("<label class='alignleft actions bulkactions'>%s: <input name='description' type='text' id='description'></input></label>", esc_html__('Description', 'szcs-coupon')));
    }
    do_action('szcs_coupon_users_list_extra_tablenav', $which);
  }

  /**
   * Define which columns are hidden
   *
   * @return Array
   */
  public function get_hidden_columns()
  {
    return array('id');
  }

  /**
   * Get bulk options.
   */
  protected function get_bulk_actions()
  {
    $actions = apply_filters(
      'szcs_coupon_balance_details_bulk_actions',
      array(
        //        'export'     => __('Export', 'szcs-coupon'),
        //        'debit'      => __('Debit', 'szcs-coupon'),
        //        'delete_log' => __('Delete Log', 'szcs-coupon'),
      )
    );
    return $actions;
  }

  /**
   * Define the sortable columns
   *
   * @return Array
   */
  public function get_sortable_columns()
  {

    $sortable_columns = array(
      'name' => array('name', false),
      'price' => array('price', false),
      'points' => array('points', false),
    );
    return apply_filters('szcs_coupon_client-products_details_sortable_columns', $sortable_columns);
  }

  /**
   * Define what data to show on each column of the table
   *
   * @param  Array  $item        Data.
   * @param  String $column_name - Current column name.
   *
   * @return Mixed
   */
  public function column_default($item, $column_name)
  {
    switch ($column_name) {
      case 'id':
        return $item[$column_name];
      case 'points':

        // update_term_meta(787, 'szcs_brand_points_field-v-' . $this->vendor->ID, 90);
        // delete_term_meta(787, 'szcs_brand_points_field-v-' . $this->vendor->ID);
        // update_term_meta(164, 'szcs_cat_points_field-v-' . $this->vendor->ID, 80);
        // delete_term_meta(164, 'szcs_cat_points_field-v-' . $this->vendor->ID);
        // update_post_meta(54189, 'szcs_product_points_field-v-' . $this->vendor->ID, 70);
        // delete_post_meta(54189, 'szcs_product_points_field-v-' . $this->vendor->ID);
        return '<span class="points" id="szcs-product-' . $item['id'] . '-point">' . $item[$column_name] . '</span>';
      case 'name':;
        return '<a href="' . $item['edit_link'] . '"><strong>' . $item[$column_name] . '</strong></a>';
      case 'price':
        return wc_price($item[$column_name]);
      case 'modify':

        $points = get_post_meta($item['id'], 'szcs_product_points_field-v-' . $this->vendor->ID, true);
        // add an input field to modify points for tmaxlength="3"
        return sprintf('<input type="number" class="product-points" name="product-points[%s]" value="%s" min="0" max="100" />', $item['id'], $points);

      case 'query':

        // make a select box with options to include or exclude
        $query = get_post_meta($item['id'], 'szcs_product_query_field-v-' . $this->vendor->ID, true);
        $options = array(
          'default' => __('Default', 'szcs-coupon'),
          'include' => __('Include', 'szcs-coupon'),
          'exclude' => __('Exclude', 'szcs-coupon'),
        );
        $select = '<select name="query[' . $item['id'] . ']">';
        foreach ($options as $key => $value) {
          $select .= '<option value="' . $key . '" ' . selected($query, $key, false) . '>' . $value . '</option>';
        }
        $select .= '</select>';
        return $select;

        //case 'cb':
        //return '<input type="checkbox" />';
      default:

        return '<pre>' . apply_filters('szcs_coupon_balance_details_column_default', print_r($item, true), $column_name, $item) . '</pre>';
    }
  }
  /**
   * Display column checkbox.
   *
   * @param array $item Item.
   */
  protected function column_cb($item)
  {
    return sprintf('<input type="checkbox" name="users[]" value="%s" />', $item['ID']);
  }
}
