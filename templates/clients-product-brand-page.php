<?php
require_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-client-products_brand.php';
$this->product_brand_table = new SzCs_Coupon_Client_Products_Brand($vendor);
$this->product_brand_table->prepare_items();
?>

<h2><?php _e('Brands', 'szcs-coupon'); ?></h2>
<form id="coupon-product_brand" method="get">
  <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
  <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
  <input type="hidden" name="client" value="<?php echo $_REQUEST['client'] ?>" />
  <?php $this->product_brand_table->search_box(__('Search Brand', 'szcs-coupon'), 'search_id'); ?>
  <?php $this->product_brand_table->views(); ?>
  <?php $this->product_brand_table->display(); ?>
  <div class="floating-confirmation">
    <div class="floating-confirmation__content">
      <div class="floating-confirmation__title">Careful &mdash; you have unsaved changes!</div>
      <div class="floating-confirmation__buttons">
        <button type="button" id="coupon-product_brand-cancel" class="button button-cancel floating-confirmation__button floating-confirmation__button--cancel">Cancel</button>
        <button type="button" id="coupon-product_brand-confirm" class="button button-primary floating-confirmation__button floating-confirmation__button--confirm">Confirm</button>
      </div>
    </div>
  </div>
</form>

<script type="text/javascript">
  function get_filtred_product_brand($) {
    var form = $('#coupon-product_brand');
    var values = form.serializeArray();
    var filteredValues = values.filter(function(item) {
      return /^product-brand-points\[\d+\]$/.test(item.name) || /^query\[\d+\]$/.test(item.name);
    });
    return filteredValues;
  }

  jQuery(function($) {

    $('#coupon-product_brand .product-points').on('input', function() {
      if ($(this).val() > 100) {
        $(this).val(100);
      } else if ($(this).val() < 0) {
        $(this).val(0);
      }
    })

    $('#coupon-product_brand').trigger('reset');
    var currentValues = get_filtred_product_brand($);

    $('#coupon-product_brand').on('change', function() {
      var newValues = get_filtred_product_brand($);
      if (JSON.stringify(currentValues) !== JSON.stringify(newValues)) {
        $('.floating-confirmation').addClass('floating-confirmation--visible');
      } else {
        $('.floating-confirmation').removeClass('floating-confirmation--visible');
      }
    });

    $('#coupon-product_brand-cancel').on('click', function() {

      $('.floating-confirmation').removeClass('floating-confirmation--visible');
      $('#coupon-product_brand').trigger('reset');
    });

    $('#coupon-product_brand-confirm').on('click', function() {

      $('#coupon-product_brand button').prop('disabled', true);
      currentValues = get_filtred_product_brand($);

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'szcs_coupon_client_update_product_brand',
          vendor: $('[name="client"]').val(),
          data: currentValues,

        },
        success: function(data) {
          if (data.success) {
            Object.keys(data.data).forEach(function(key) {
              // #szcs-product-54189-point
              if ($('#szcs-product_brand-' + key + '-point').text() != data.data[key]) {
                if (data.data[key] == "") {
                  $('#szcs-product_brand-' + key + '-point').html("&mdash;");
                } else {
                  $('#szcs-product_brand-' + key + '-point').text(data.data[key]);
                }
              }
            });
          }
        },
        error: function(errorThrown) {
          console.log(errorThrown);
        },
        complete: function() {
          $('#coupon-product_brand button').prop('disabled', false);
          $('.floating-confirmation').removeClass('floating-confirmation--visible');
        }
      });

    });

  })
</script>