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
class SzCs_Coupon_Client_Products_Cat extends WP_List_Table
{


  protected $vendor = null;

  protected $products_cat = array();

  protected $total_items = 0;

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

    if (count($this->products_cat)) return $this->products_cat;

    global $szcs_coupon_wc;

    $terms = array();


    if (isset($_GET['s']) && !empty($_GET['s'])) {
      $args = array(
        'taxonomy' => 'product_cat',
        'search' => $_GET['s'],
        'orderby' => 'name',
        'order' => 'ASC',
        'exclude' => '1', // exclude uncategorized
        'hide_empty' => false,
      );
      $product_cats = get_terms($args);

      foreach ($product_cats as $product_cat) {
        $terms[] = array(
          'id' => $product_cat->term_id,
          'name' => $product_cat->name,
          'slug' => $product_cat->slug,
          'count' => $product_cat->count,
          'points' => $szcs_coupon_wc->wc_product_cat_get_vendor_points_percent($product_cat->term_id, $this->vendor->ID),
          'edit_link' => admin_url() . 'term.php?taxonomy=product_cat&tag_ID=' . $product_cat->term_id . '&post_type=product',
          'count_link' => admin_url() . 'edit.php?post_type=product&product_cat=' . $product_cat->slug,
        );
      }

      $this->products_cat = $terms;

      return $terms;
    }


    // get product_cat not uncategorized ordered by name
    $args = array(
      'taxonomy' => 'product_cat',
      'parent' => 0,
      'orderby' => 'name',
      'order' => 'ASC',
      'exclude' => '1', // exclude uncategorized
      'hide_empty' => false,
    );


    $product_cats = get_terms($args);

    foreach ($product_cats as $product_cat) {
      $terms[] = array(
        'id' => $product_cat->term_id,
        'name' => $product_cat->name,
        'slug' => $product_cat->slug,
        'count' => $product_cat->count,
        'points' => $szcs_coupon_wc->wc_product_cat_get_vendor_points_percent($product_cat->term_id, $this->vendor->ID),
        'edit_link' => admin_url() . 'term.php?taxonomy=product_cat&tag_ID=' . $product_cat->term_id . '&post_type=product',
        'count_link' => admin_url() . 'edit.php?post_type=product&product_cat=' . $product_cat->slug,
      );

      // get sub product_cat ordered by name
      $args = array(
        'taxonomy' => 'product_cat',
        'parent' => $product_cat->term_id,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
      );

      $sub_product_cats = get_terms($args);

      foreach ($sub_product_cats as $sub_product_cat) {
        $terms[] = array(
          'id' => $sub_product_cat->term_id,
          'name' => '&mdash; ' . $sub_product_cat->name,
          'slug' => $sub_product_cat->slug,
          'count' => $sub_product_cat->count,
          'points' => $szcs_coupon_wc->wc_product_cat_get_vendor_points_percent($sub_product_cat->term_id, $this->vendor->ID),
          'edit_link' => admin_url() . 'term.php?taxonomy=product_cat&tag_ID=' . $sub_product_cat->term_id . '&post_type=product',
          'count_link' => admin_url() . 'edit.php?post_type=product&product_cat=' . $sub_product_cat->slug,
        );
      }
    }

    $this->products_cat = $terms;

    $this->total_items = count($this->products_cat);

    return $terms;
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
        'count' => __('Count', 'szcs-coupon'),
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

    $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);


    $this->set_pagination_args(array(
      'total_items' => $total_items,
      'per_page'    => $per_page,
    ));

    $this->items = $data;
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
        $points = !is_numeric($item[$column_name]) ? '&mdash;' : $item[$column_name];
        return '<span class="points" id="szcs-product_cat-' . $item['id'] . '-point">' . $points . '</span>';
      case 'name':;
        return '<a href="' . $item['edit_link'] . '"><strong>' . $item[$column_name] . '</strong></a>';
      case 'price':
        return wc_price($item[$column_name]);
      case 'modify':

        $points = get_term_meta($item['id'], 'szcs_product_cat_points_field-v-' . $this->vendor->ID, true);
        // add an input field to modify points for tmaxlength="3"
        return sprintf('<input type="number" class="product-points" name="product-cat-points[%s]" value="%s" min="0" max="100" />', $item['id'], $points);

      case 'query':

        // make a select box with options to include or exclude
        $query = get_term_meta($item['id'], 'szcs_product_cat_query_field-v-' . $this->vendor->ID, true);
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
      case 'count':
        return '<a href="' . $item['count_link'] . '">' . $item[$column_name] . '</a>';

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
