<?php

/**
 * SzCsCoupon code post type file
 *
 * @package SzCsCoupon
 */

if (!defined('ABSPATH')) {
  exit;
}

class SzCsCouponCodePostType
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponCodePostType
   * @since 1.1.1
   */
  protected static $_instance = null;

  /**
   * Main instance
   *
   * @return class object
   */
  protected static $temp = '';

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
    add_action('init', array($this, 'post_type'));
    add_action('szcs_admin_menu', array($this, 'admin_menu'), 10);
    add_action('add_meta_boxes_szcs_coupons_code', array($this, 'add_metaboxes'));
    add_action('save_post_szcs_coupons_code', array($this, 'save_code'));
    add_filter('manage_szcs_coupons_code_posts_columns', array($this, 'set_columns'));
    add_action('manage_szcs_coupons_code_posts_custom_column', array($this, "manage_custom_columns"));
    add_filter('hidden_columns', array($this, 'set_hidden_columns'), 10, 2);
    add_filter('enter_title_here', array($this, 'title_text'));
    add_filter('post_updated_messages', array($this, 'updated_messages'));
    add_filter('post_updated_error', array($this, 'updated_messages'));
    add_action('admin_notices', array($this, 'admin_notices'), 15);
    add_filter('wp_insert_post_data', array($this, 'modify_post_title')); // Grabs the inserted post data so you can modify it.
    add_filter('post_row_actions', array($this, 'remove_quick_edit'), 10, 2);
    add_filter('views_edit-szcs_coupons_code', array($this, 'reorder_subsubsub'));
    add_filter('display_post_states', array($this, 'add_display_post_states'), 10, 2);
    add_filter('wp_insert_post_empty_content', array($this, 'prevent_from_saving'), 10, 2);
    add_action('manage_posts_extra_tablenav', array($this, 'extra_tablenavExport'));
    add_action('wp_trash_post', array($this, 'trash_code'));
    add_action('untrashed_post', array($this, 'untrashed_code'));
  }

  function admin_menu($parent_slug)
  {
    $szcs_coupon_code_menu_page_hook = add_submenu_page(
      $parent_slug,
      __('Coupons', 'szcs-coupon'),
      __('Coupons', 'szcs-coupon'),
      get_szcs_coupon_user_capability(),
      'edit.php?post_type=szcs_coupons_code',
      ''
    );
    add_action("load-$szcs_coupon_code_menu_page_hook", array($this, 'add_szcs_coupon_code'));
  }

  /**
   * Coupon details page initialization
   */
  public function add_szcs_coupon_code()
  {
    $option = 'per_page';
    $args   = array(
      'label'   => 'Number of items per page:',
      'default' => 15,
      'option'  => 'users_per_page',
    );
    add_screen_option($option, $args);
  }

  function post_type()
  {
    register_post_type(
      'szcs_coupons_code',
      array(
        'labels' => array(
          'name'                => __('Coupons', 'szcs-coupon'),
          'singular_name'       => __('Coupon', 'szcs-coupon'),
          'search_items'        => __('Search', 'szcs-coupon'),
          'add_new'             => __('Add Coupon', 'szcs-coupon'),
          'add_new_item'        => __('Add New Coupon', 'szcs-coupon'),
          'edit_item'           => __('Edit Coupon', 'szcs-coupon'),
          'not_found'           => __('No coupons found.', 'szcs-coupon'),
          'not_found_in_trash'  => __('No coupons found in Trash.', 'szcs-coupon')
        ),
        'public'        => false,
        'private'       => true,
        'has_archive'   => false,
        'show_ui'       => true,
        'show_in_menu'  => false,
        'supports'      => array('title'),
      )
    );
    register_post_status('expired', array(
      'label'           => __('Expired', 'szcs-coupon'),
      'label_count'     => _n_noop(__('Expired', 'szcs-coupon') . ' <span class="count">(%s)</span>', __('Expired', 'szcs-coupon') . ' <span class="count">(%s)</span>'),
      'public'          => false,
      'internal'        => true,
      'private'         => true,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
    ));
  }

  function remove_quick_edit($actions, $post)
  {
    unset($actions['inline hide-if-no-js']);
    unset($actions['duplicate']);
    return $actions;
  }

  function add_display_post_states($post_states, $post)
  {

    if (!get_query_var('post_status')) {
      switch ($post->post_status) {
        case 'expired':
          $post_states['expired'] = 'Expired';
          break;
      }
    }
    return $post_states;
  }

  function set_columns()
  {
    return array(
      'cb'                      => '<input type="checkbox" />',
      'title'                   => __('Code', 'szcs-coupon'),
      'voucher_amount'          => __('Coupon Amount', 'szcs-coupon'),
      'usage_limit_per_voucher' => __('Usage / Limit', 'szcs-coupon'),
      'expiry_date'             => __('Expery data', 'szcs-coupon'),
      'date'                    => 'Creation Date'
    );
  }

  function set_hidden_columns($hidden, $screen)
  {
    $screen_id = $screen ? $screen->id : '';
    if (in_array($screen_id, array('szcs_coupons_code', 'edit-szcs_coupons_code'), true)) {
      $hidden[] = 'date';
    }
    return $hidden;
  }

  function manage_custom_columns($column)
  {
    global $post;
    global $szcs_coupon_voucher;
    $voucher = $szcs_coupon_voucher->get_voucher_by_post_id($post->ID);
    //$fileds = get_post_custom($post->ID);
    $value = $voucher ? $voucher->$column : '0';
    if ($column === 'voucher_amount') {
      echo number_format($value);
    } else if ($column === 'usage_limit_per_voucher') {
      global $szcs_coupon_transaction;
      $transactions = $voucher ? $szcs_coupon_transaction->get_transactions_by_voucher_id($voucher->voucher_id) : array();
      echo count($transactions) . '/' . $value;
    } else {
      echo $value;
    }
  }


  function title_text($title)
  {
    $screen = get_current_screen();

    if ('szcs_coupons_code' == $screen->post_type) {
      $title = 'Coupon Code';
    }
    return $title;
  }

  function modify_post_title($data)
  {
    if ($data['post_type'] === 'szcs_coupons_code') {
      $data['post_title'] = strtoupper(str_replace(' ', '', $data['post_title'])) . self::$temp;
    }
    return $data;
  }

  function prevent_from_saving($bool, $data)
  {
    global $szcs_coupon_voucher;
    global $post;
    $voucher = $szcs_coupon_voucher->validate_voucher($data['post_title']);
    if ($voucher[0] === 'valid' && $voucher[1]->post_id != $post->ID && $data['post_status'] === 'publish') {
      add_filter('redirect_post_location', function ($location, $post_id) {
        $loc = remove_query_arg('message', $location);

        return add_query_arg(array('error' => 15), $loc);
      }, 10, 2);
      return true;
    }
    return false;
  }

  function reorder_subsubsub($views)
  {
    $keys = array('all', 'publish', 'expired', 'draft', 'trash');
    $newView = array();
    foreach ($keys as $key) {
      if (key_exists($key, $views)) {
        $newView[$key] = $views[$key];
      }
    }

    return $newView;
  }

  function save_code()
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (empty($_POST)) return;
    global $post;
    if ($post) {
      do_action('szcs_coupon_create_voucher', $post->ID, $_POST["voucher_amount"], array(
        'voucher_no'                => $post->post_title,
        'expiry_date'               => $_POST["expiry_date"],
        'usage_limit_per_voucher'   => $_POST["usage_limit_per_voucher"],
        'usage_limit_per_user'      => $_POST["usage_limit_per_user"],
        'status'                    => $_POST['post_status'] !== 'publish' ? $_POST['post_status'] : 'active',
      ));
    }
  }

  function untrashed_code($post_id)
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post = get_post($post_id);
    if ($post->post_type === 'szcs_coupons_code') {
      do_action('szcs_coupon_create_voucher', $post_id, 0, array(
        'status' => $post->post_status,
      ));
    }
  }

  function trash_code($post_id)
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post = get_post($post_id);
    if ($post->post_type === 'szcs_coupons_code') {
      do_action('szcs_coupon_create_voucher', $post_id, 0, array(
        'status' => 'trashed',
      ));
    }
  }

  public function extra_tablenavExport($which)
  {
    global $post;
    if ($post->post_type == 'szcs_coupons_code' && 'top' === $which) {
      /* translators: SzCsCommerce currency */
      echo '<div class="alignleft actions"><input type="submit" name="szcs_export_coupons" id="szcs-export-submit" class="button" value="' . __('Export', 'szcs-coupon') . '"></div>';
    }
  }

  function updated_messages($message)
  {
    global $post;
    if ($post->post_type == 'szcs_coupons_code') {
      $message['post'] = str_replace('Post', 'Coupon', $message['post']);
      $message['post'][20] = 'Coupon with the same code already exist';
    }

    return $message;
  }

  function add_metaboxes()
  {
    add_meta_box('coupon_data', __('Coupon Data'), array($this, 'metaboxes_html'), 'szcs_coupons_code', 'normal', 'high');
  }

  /**
   * Display admin notice
   */
  public function admin_notices()
  {
    $notice = get_settings_errors('szcs_notices');
    if ($notice) {
?>
      <div class="<?php echo $notice['type']; ?>">
        <p>
          <?php echo esc_html_e($notice['message'], 'szcs-coupon'); ?>
        </p>
      </div>
    <?php
    }
  }

  function metaboxes_html()
  {
    global $post;
    //$custom = get_post_custom($post->ID);
    global $szcs_coupon_voucher;
    $voucher = $szcs_coupon_voucher->get_voucher_by_post_id($post->ID);
    $voucher_amount = $voucher ? $voucher->voucher_amount : '';
    $expiry_date = $voucher ? $voucher->expiry_date : '';
    $usage_limit_per_voucher = $voucher ? $voucher->usage_limit_per_voucher : 1;
    $usage_limit_per_user = $voucher ? $voucher->usage_limit_per_user : 1;
    ?>
    <div class="szcs_coupon szcs_coupon_options_panel">
      <p class="form-field szcs_coupon_voucher_amount_field ">
        <label for="voucher_amount">Coupon amount</label>
        <input type="number" class="short wc_input_price" name="voucher_amount" id="voucher_amount" value="<?php echo $voucher_amount; ?>" placeholder="0" required>
      </p>
      <p class="form-field szcs_coupon_expiry_date_field ">
        <label for="expiry_date">Coupon expiry date</label>
        <input type="date" class="date-picker hasDatepicker" name="expiry_date" id="expiry_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $expiry_date; ?>" placeholder="YYYY-MM-DD" required>
      </p>
      <p class="form-field szcs_coupon_usage_limit_per_voucher_field ">
        <label for="usage_limit_per_voucher">Usage limit per coupon</label>
        <input type="number" class="short" name="usage_limit_per_voucher" id="usage_limit_per_voucher" value="<?php echo $usage_limit_per_voucher; ?>" placeholder="1" pattern="[1-9]{1}" required>
      </p>
      <p class="form-field szcs_coupon_usage_limit_per_user_field ">
        <label for="usage_limit_per_user">Usage limit per user</label>
        <input type="number" class="short" name="usage_limit_per_user" id="usage_limit_per_user" value="<?php echo $usage_limit_per_user; ?>" placeholder="1" pattern="[1-9]{1}" required>
      </p>
    </div>
<?php
  }
}
SzCsCouponCodePostType::instance();
