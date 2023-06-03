<?php

/**
 * Coupon settings page file
 *
 * @package SzCsCoupon
 */
class SzCsCouponSettings
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponSettings
   * @since 1.1.10
   */
  protected static $_instance = null;

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
    add_action('szcs_admin_menu', array($this, 'admin_menu'), 50);
    add_action('edit_user_profile', array($this, 'user_profile_coupon_fields'));
    add_action('show_user_profile', array($this, 'user_profile_coupon_fields'));
    add_action('edit_user_profile_update', array($this, 'user_profile_coupon_fields_save'));
    add_action('personal_options_update', array($this, 'user_profile_coupon_fields_save'));


    /**
     * Register our settings_init to the admin_init action hook.
     */
    add_action('admin_init', array($this, 'settings_init'));
  }

  /**
   * custom option and settings
   */
  function settings_init()
  {
    // Register a new setting for "szcs-coupon-settings" page.
    register_setting('szcs-coupon', 'szcs-coupon_options');

    // Register a new section in the "szcs-coupon-settings" page.
    add_settings_section(
      'szcs_coupon_section_membership',
      __('Membership', 'szcs-coupon'),
      '',
      'szcs-coupon'
    );
    // Register a new field in the "szcs_coupon_section_membership" section, inside the "szcs-coupon-settings" page.

    add_settings_field(
      'szcs_coupon_member_only', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('Member Only', 'szcs-coupon'),
      array($this, 'field_cb'),
      'szcs-coupon',
      'szcs_coupon_section_membership',
      array(
        'label'         => 'Member Only',
        'label_for'         => 'szcs-coupon-member-only',
        'helper_text'        => 'Only logged in user can access the site'
      )
    );

    add_settings_field(
      'szcs_coupon_member_exclude', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('Exclude Pages', 'szcs-coupon'),
      array($this, 'field_textarea'),
      'szcs-coupon',
      'szcs_coupon_section_membership',
      array(
        'label'         => 'Exclude Pages',
        'label_for'         => 'szcs-coupon-member-exclude',
        'helper_text'        => 'Enter page links to exclude from member only one per line'
      )
    );

    add_settings_section(
      'szcs_coupon_section_category',
      __('Category Options', 'szcs-coupon'),
      '',
      'szcs-coupon'
    );

    add_settings_field(
      'szcs_coupon_auto_select_parent_category', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('Auto Select Category', 'szcs-coupon'),
      array($this, 'field_cb'),
      'szcs-coupon',
      'szcs_coupon_section_category',
      array(
        'label'         => 'Auto Select Category',
        'label_for'         => 'szcs-coupon-auto-parent-category',
        'helper_text'        => 'Auto select parent category'
      )
    );

    add_settings_section(
      'szcs_coupon_section_rest_api',
      __('Rest Api Options', 'szcs-coupon'),
      '',
      'szcs-coupon'
    );

    add_settings_field(
      'szcs_coupon_section_rest_api_root', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('API Root', 'szcs-coupon'),
      array($this, 'field_input'),
      'szcs-coupon',
      'szcs_coupon_section_rest_api',
      array(
        'label'         => 'API Root',
        'label_for'         => 'szcs-coupon-rest-api-root',
        'prifix_text'        => get_home_url() . '/'
      )
    );

    /**
     add_settings_field(
       'szcs_coupon_prefer_child_category', // As of WP 4.6 this value is used only internally.
       // Use $args' label_for to populate the id inside the callback.
       __('Prefer Child Category', 'szcs-coupon'),
       array($this, 'field_cb'),
       'szcs-coupon',
       'szcs_coupon_section_category',
       array(
         'label'         => 'Prefer Child Category',
         'label_for'         => 'szcs-coupon-prefer-child-category',
         'helper_text'        => 'Prefer child category points'
         )
         );
     */
  }
  public function admin_menu($parent_slug)
  {
    add_submenu_page(
      $parent_slug,
      __('Coupons Settings', 'szcs-coupon'),
      __('Settings', 'szcs-coupon'),
      get_szcs_coupon_user_capability(),
      'szcs-coupon-settings',
      [$this, 'page_html'],
    );
  }

  /**
   * Member only field callback function.
   *
   * WordPress has magic interaction with the following keys: label_for, class.
   * - the "label_for" key value is used for the "for" attribute of the <label>.
   * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
   * Note: you can add custom key value pairs to be used inside your callbacks.
   *
   * @param array $args
   */
  function field_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('szcs-coupon_options');
?>
    <fieldset>
      <legend class="screen-reader-text">
        <span><?php echo esc_attr($args['label']); ?></span>
      </legend>
      <label for="<?php echo esc_attr($args['label_for']); ?>">
        <input name="szcs-coupon_options[<?php echo esc_attr($args['label_for']); ?>]" type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" value="1" <?php echo isset($options[$args['label_for']]) ? (checked($options[$args['label_for']], '1', false)) : (''); ?>>
        <?php echo esc_attr($args['helper_text']); ?></label>
    </fieldset>
  <?php
  }

  function field_textarea($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('szcs-coupon_options');
  ?>
    <!-- <fieldset>
      <legend class="screen-reader-text">
        <span><?php echo esc_attr($args['label']); ?></span>
      </legend>
      <label for="<?php echo esc_attr($args['label_for']); ?>">
        <input name="szcs-coupon_options[<?php echo esc_attr($args['label_for']); ?>]" type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" value="1" <?php echo isset($options[$args['label_for']]) ? (checked($options[$args['label_for']], '1', false)) : (''); ?>>
        <?php echo esc_attr($args['helper_text']); ?></label>
    </fieldset> -->

    <fieldset>
      <legend class="screen-reader-text">
        <span><?php echo esc_attr($args['label']); ?></span>
      </legend>
      <textarea name="szcs-coupon_options[<?php echo esc_attr($args['label_for']); ?>]" type="textarea" id="<?php echo esc_attr($args['label_for']); ?>" rows="5" cols="50"><?php echo isset($options[$args['label_for']]) ? (esc_attr($options[$args['label_for']])) : (''); ?></textarea>
      <label for="<?php echo esc_attr($args['label_for']); ?>">
        <?php echo esc_attr($args['helper_text']); ?></label>
    </fieldset>

  <?php
  }

  function field_input($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('szcs-coupon_options');
  ?>
    <fieldset>
      <legend class="screen-reader-text">
        <span><?php echo esc_attr($args['label']); ?></span>
      </legend>
      <label for="<?php echo esc_attr($args['label_for']); ?>">
        <code><?php echo esc_attr($args['prifix_text']); ?></code></label>
      <input name="szcs-coupon_options[<?php echo esc_attr($args['label_for']); ?>]" type="input" id="<?php echo esc_attr($args['label_for']); ?>" value="<?php echo sanitize_title(str_replace(get_home_url(), "", get_rest_url())); ?>" pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$" title="This field should only contain lowercase letters, digits, and hyphens. Please ensure that the input is formatted correctly.">
    </fieldset>
  <?php
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

    // add error/update messages

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated'])) {
      // add settings saved message with the class of "updated"
      add_settings_error('szcs-coupon_messages', 'szcs-coupon_message', __('Settings Saved', 'szcs-coupon'), 'updated');
    }

    // show error/update messages
    settings_errors('szcs-coupon_messages');
  ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "wporg"
        settings_fields('szcs-coupon');
        // output setting sections and their fields
        // (sections are registered for "wporg", each field is registered to a specific section)
        do_settings_sections('szcs-coupon');
        // output save settings button
        submit_button('Save Settings');
        ?>
      </form>


      <p>Make sure to update permalink after changing Api root from <code>Settings > Permalinks > Save Changes</code></p>
    </div>
  <?php
  }



  public function user_profile_coupon_fields($user)
  {
    echo '<h3 class="heading">Point Balance Management</h3>';
    global $szcs_coupon_wallet;

    $vendor_id = get_user_meta($user->ID, 'szcs_coupon_vendor_id', true);
    if ($vendor_id) {
      $vendor = get_user_by('id', $vendor_id);
      $vendor_name = $vendor->display_name;
    } else {
      $vendor_name = '';
    }
  ?>

    <table class="form-table szcs_coupon_user_setting_table">
      <?php if ($vendor_name) { ?>
        <tr>
          <th><label for="szcs_coupon_user_vendor">Client</label></th>

          <td>
            <span class="" id="szcs_coupon_user_vendor"><?php echo $vendor_name; ?></span>
          </td>
        </tr>
      <?php } ?>
      <tr>
        <th><label for="szcs_coupon_user_balance">Current Balance</label></th>

        <td>
          <span class="" id="szcs_coupon_user_balance"><?php echo $szcs_coupon_wallet->get_balance($user->ID); ?></span>
        </td>

      </tr>
      <tr>
        <th><label for="szcs_coupon_user_balance_field">New Balance</label></th>

        <td><input type="number" class="regular-text" min="0" name="szcs_coupon_user_balance_field" id="szcs_coupon_user_balance_field" />
        </td>

      </tr>

      <?php
      do_action('szcs_coupon_after_user_profile_fields', $user);
      ?>

    </table>

<?php
  }


  function user_profile_coupon_fields_save($user_id)
  {
    if (is_admin()) {

      $new_points = filter_input(INPUT_POST, 'szcs_coupon_user_balance_field');
      if (($new_points >= 0)) {
        do_action('szcs_coupon_update_balance', $user_id, $new_points, 'set');
      }
    }
  }
}

SzCsCouponSettings::instance();
