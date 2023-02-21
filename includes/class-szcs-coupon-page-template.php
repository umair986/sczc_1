<?php

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponPageTemplate
{
  /**
   * The single instance of the class.
   *
   * @var SzCsCouponPageTemplate
   * @since 1.1.25
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
    add_filter('theme_page_templates', array($this, 'add_page_template_to_dropdown'));
    add_filter('template_include', array($this, 'change_page_template'), 99);
  }


  /**
   * Add page templates.
   *
   * @param  array  $templates  The list of page templates
   *
   * @return array  $templates  The modified list of page templates
   */
  public function add_page_template_to_dropdown($templates)
  {
    $templates[SZCS_COUPON_ABSPATH . 'templates/clean-page-template.php'] = __('Blank Page', 'szcs-compon');

    return $templates;
  }

  /**
   * Change the page template to the selected template on the dropdown
   * 
   * @param $template
   *
   * @return mixed
   */
  public function change_page_template($template)
  {

    if (is_page()) {
      $meta = get_post_meta(get_the_ID());

      if (!empty($meta['_wp_page_template'][0]) && $meta['_wp_page_template'][0] != 'default') {
        $template = $meta['_wp_page_template'][0];
      }
    }

    return $template;
  }
}


SzCsCouponPageTemplate::instance();
