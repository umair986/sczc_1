jQuery(function($){


  // Set the initial values
  var variable_id = $('form.cart').find('input[name="variation_id"]').val();
  
  $('body').on('change', 'form.cart input[type="radio"]', function(e){

    // wait for the variation to be updated before updating the coupon values
    setTimeout(function(){
      variable_id = $('form.cart').find('input[name="variation_id"]').val();
      if (variable_id && SZCS_COUPONS.variations[variable_id]) {
        $('#szcs-points .coupon-amount').html(Math.round(SZCS_COUPONS.variations[variable_id].points * 10) / 10);
        $('#szcs-payable .coupon-amount').html(`${SZCS_COUPONS.currency}${Math.round(SZCS_COUPONS.variations[variable_id].payable * 10) / 10}`);
      }
    }, 100);
  })

});