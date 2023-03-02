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
class SzCs_Coupon_Batch_Details extends WP_List_Table
{


  /**
   * Get data.
   */

  private function get_data()
  {
    global $wpdb;
    $users_table = $wpdb->prefix . 'users';
    $batch_table = $wpdb->prefix . 'szcs_voucher_batch';
    $voucher_table = $wpdb->prefix . 'szcs_voucher_points';
    if (current_user_can('manage_woocommerce')) {
      $users_query = "SELECT COUNT($batch_table.batch_id) as count,  $batch_table.*, $voucher_table.* ,  $users_table.user_login FROM $batch_table LEFT JOIN $voucher_table ON $batch_table.batch_id = $voucher_table" . ".batch_id LEFT JOIN $users_table ON $batch_table.vendor_id = $users_table.ID GROUP BY $voucher_table.batch_id ORDER BY $batch_table.batch_id DESC";
    } else {
      $users_query = "SELECT COUNT($batch_table.batch_id) as count,  $batch_table.*, $voucher_table.* ,  $users_table.user_login FROM $batch_table LEFT JOIN $voucher_table ON $batch_table.batch_id = $voucher_table" . ".batch_id LEFT JOIN $users_table ON $batch_table.vendor_id = $users_table.ID WHERE $batch_table.vendor_id = " . get_current_user_id() . " GROUP BY $voucher_table.batch_id ORDER BY $batch_table.batch_id DESC";
    }
    $results = $wpdb->get_results($users_query, ARRAY_A);
    return $results;
  }

  /**
   * Get columns.
   */
  public function get_columns()
  {
    return apply_filters(
      'szcs_coupon_balance_details_columns',
      array(
        //      'cb'       => __('cb', 'szcs-coupon'),
        'batch_id'       => __('ID', 'szcs-coupon'),
        'username' => __('Vendor', 'szcs-coupon'),
        'voucher_amount' => __('Coupon Amount', 'szcs-coupon'),
        'count' => __('Count', 'szcs-coupon'),
        'create_date' => __('Creation Date', 'szcs-coupon'),
        'actions'  => __('Actions', 'szcs-coupon'),
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
    esc_html_e('No Vouchers found.', 'szcs-coupon');
  }
  /**
   * Prepare the items for the table to process
   */
  function prepare_items()
  {
    $per_page = $this->get_items_per_page('users_per_page', 15);
    $current_page = $this->get_pagenum();

    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array($columns, $hidden, $sortable);

    $data = $this->get_data();
    $total_items = count($data);

    usort($data, array($this, 'usort_reorder'));
    $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);


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
    global $role;

    $wp_roles = wp_roles();

    $url           = 'admin.php?page=szcs-coupon';
    $users_of_blog = count_users();

    $total_users = $users_of_blog['total_users'];
    $avail_roles = &$users_of_blog['avail_roles'];
    unset($users_of_blog);

    $current_link            = (!empty($_REQUEST['role']) ? wp_unslash($_REQUEST['role']) : 'all');
    $current_link_attributes = ('all' === $current_link) ? ' class="current" aria-current="page"' : '';

    $role_links = array();
    /* translators: Total user */
    $role_links['all'] = "<a href='$url'$current_link_attributes>" . sprintf(_nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_users, 'users', 'szcs-coupon'), number_format_i18n($total_users)) . '</a>';
    foreach ($wp_roles->get_names() as $this_role => $name) {
      if (!isset($avail_roles[$this_role])) {
        continue;
      }

      $current_link_attributes = '';
      $current_link_attributes = ($current_link === $this_role ? ' class="current" aria-current="page"' : '');

      $name = translate_user_role($name);
      /* translators: User role name with count */
      $name                     = sprintf(__('%1$s <span class="count">(%2$s)</span>', 'szcs-coupon'), $name, number_format_i18n($avail_roles[$this_role]));
      $role_links[$this_role] = "<a href='" . esc_url(add_query_arg('role', $this_role, $url)) . "'$current_link_attributes>$name</a>";
    }

    if (!empty($avail_roles['none'])) {

      $current_link_attributes = '';

      if ('none' === $role) {
        $current_link_attributes = ' class="current" aria-current="page"';
      }

      $name = __('No role', 'szcs-coupon');
      /* translators: User role name with count */
      $name               = sprintf(__('%1$s <span class="count">(%2$s)</span>', 'szcs-coupon'), $name, number_format_i18n($avail_roles['none']));
      $role_links['none'] = "<a href='" . esc_url(add_query_arg('role', 'none', $url)) . "'$current_link_attributes>$name</a>";
    }

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
    if (wp_get_current_user()->roles[0] === 'vendor') {
      return array(
        'username'
      );
    }

    return array();
  }

  /**
   * Get bulk options.
   */
  protected function get_bulk_actions()
  {
    $actions = apply_filters(
      'szcs_coupon_batch_details_bulk_actions',
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
      'username' => array('user_login', false),
      'display_name' => array('display_name', false),
      'voucher_amount' => array('voucher_amount', false),
      'create_date' => array('create_date', false),
      'count' => array('count', true),
    );
    return apply_filters('szcs_coupon_batch_details_sortable_columns', $sortable_columns);
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
      case 'batch_id':
        return $item[$column_name] ? $item['user_login'] . '_' . $item[$column_name] : '';
      case 'display_name':
      case 'voucher_amount':
      case 'create_date':
      case 'count':
        return $item[$column_name] ? $item[$column_name] : '';
      case 'username':
        return $item['user_login'];
      case 'wallet_points':
        return number_format($item[$column_name] ? $item[$column_name] : 0, 2);
      case 'actions':
        return '<p><a href="' . admin_url('#') . '" class="button" data-batch-id="' . $item['batch_id'] . '" data-target="szcs-export-batch" title="export"  style="width: auto;display: inline-flex;align-items: center;justify-content: center;gap: 5px;"><span class="text">Export</span> <span class="dashicons dashicons-share-alt2"></span></a></p>';
        //      case 'cb':
        //      return '<input type="checkbox" />';
      default:
        return apply_filters('szcs_coupon_balance_details_column_default', print_r($item, true), $column_name, $item);
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
