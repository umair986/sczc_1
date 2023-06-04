<?php
require_once SZCS_COUPON_ABSPATH . 'includes/admin/class-szcs-coupon-client-products.php';
$this->products_table = new SzCs_Coupon_Client_Products($vendor);
$this->products_table->prepare_items();
?>
<h2><?php _e('Products', 'szcs-coupon'); ?></h2>
<form id="coupon-products" method="get">
  <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
  <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
  <input type="hidden" name="client" value="<?php echo $_REQUEST['client'] ?>" />
  <?php $this->products_table->search_box(__('Search Products', 'szcs-coupon'), 'search_id'); ?>
  <?php $this->products_table->views(); ?>
  <?php $this->products_table->display(); ?>
  <div class="floating-confirmation">
    <div class="floating-confirmation__content">
      <div class="floating-confirmation__title">Careful &mdash; you have unsaved changes!</div>
      <div class="floating-confirmation__buttons">
        <button type="button" id="coupon-products-cancel" class="button button-cancel floating-confirmation__button floating-confirmation__button--cancel">Cancel</button>
        <button type="button" id="coupon-products-confirm" class="button button-primary floating-confirmation__button floating-confirmation__button--confirm">Confirm</button>
      </div>
    </div>
  </div>
</form>

<script type="text/javascript">
  function get_filtred_products($) {
    var form = $('#coupon-products');
    var values = form.serializeArray();
    var filteredValues = values.filter(function(item) {
      return /^product-points\[\d+\]$/.test(item.name) || /^query\[\d+\]$/.test(item.name);
    });
    return filteredValues;
  }

  jQuery(function($) {

    $('#coupon-products .product-points').on('input', function() {
      if ($(this).val() > 100) {
        $(this).val(100);
      } else if ($(this).val() < 0) {
        $(this).val(0);
      }
    })

    $('#coupon-products').trigger('reset');
    var currentValues = get_filtred_products($);

    $('#coupon-products').on('change', function() {
      var newValues = get_filtred_products($);
      if (JSON.stringify(currentValues) !== JSON.stringify(newValues)) {
        $('.floating-confirmation').addClass('floating-confirmation--visible');
      } else {
        $('.floating-confirmation').removeClass('floating-confirmation--visible');
      }
    });

    $('#coupon-products-cancel').on('click', function() {

      $('.floating-confirmation').removeClass('floating-confirmation--visible');
      $('#coupon-products').trigger('reset');
    });

    $('#coupon-products-confirm').on('click', function() {

      $('#coupon-products button').prop('disabled', true);

      currentValues = get_filtred_products($);

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'szcs_coupon_client_update_products',
          vendor: $('[name="client"]').val(),
          data: currentValues,

        },
        success: function(data) {
          if (data.success) {
            Object.keys(data.data).forEach(function(key) {
              // #szcs-product-54189-point
              if ($('#szcs-product-' + key + '-point').text() != data.data[key]) {
                $('#szcs-product-' + key + '-point').text(data.data[key]);
              }
            });
          }
        },
        error: function(errorThrown) {
          console.log(errorThrown);
        },
        complete: function() {
          $('.floating-confirmation').removeClass('floating-confirmation--visible');
          $('#coupon-products button').prop('disabled', false);
        }
      });

    });

  })
</script>