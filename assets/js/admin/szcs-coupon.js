(function($) {
  $.QueryString = (function(a) {
      if (a == "") return {};
      var b = {};
      for (var i = 0; i < a.length; ++i)
      {
          var p=a[i].split('=');
          if (p.length != 2) continue;
          b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
      }
      return b;
  })(window.location.search.substr(1).split('&'))
})(jQuery);

jQuery(document).ready(function ($) {
  // Add buttons to Coupons screen.
  var couponScreen = $( '.edit-php.post-type-szcs_coupons_code' );
  var titleAction = couponScreen.find( '.page-title-action:first' );
  var exportAction = couponScreen.find( '#szcs-export-submit' );
  if(titleAction.length){
    ($("<a></a>").addClass('page-title-action').text('Coupon Generator').attr('href', SZCS_VARS.couponGeneratorUrl)).insertAfter(titleAction);
  }

  if(jQuery.QueryString["error"] == 15){
    $('<div></div>').addClass('error notice-error').append($('<p></p>').text('Coupon with same code already exist.')).insertAfter($('.wp-header-end'));
  }

  if(exportAction.length){
    exportAction.click(function(e){
      e.preventDefault();
      // show loading screen
      $('body').append($('<div />').attr('id', 'szcs_coupon_login_screen'));
      $('.szcs-coupon-notice').remove();
      // get form
      var form = $(this).closest('form')[0];
      // get form data
      var formData = new FormData(form);

      // add custom field to form data
      formData.append('action', 'szcs-coupon-export');

      // make a fetch request
      fetch(`${SZCS_VARS.ajaxUrl}`, {
        method: 'post',
        body: formData,
      })
      .then(res => {
        return res.json();
      })
      .then(data => {
        if(typeof data === 'object'){
          var url = download(csvmaker(data), false);
          showNotification($, `Your export is ready <a href="${url}" download="vouchers.csv">Click Here</a> to download.`, 'updated');
        }else if(typeof data === 'string'){
          showNotification($, data, 'error');
        }
      })
      .catch(e => {
        $('<div />').attr({
          id: 'error',
          class: 'error szcs-coupon-notice',
        }).append($('<p />').html(`Something went wrong.`)).insertAfter('.wp-header-end');
        
      }).finally(() => {
        $('#szcs_coupon_login_screen').remove();
      });
    });
  }
  //;
});

function downloadObjectAsJson(exportObj, exportName){
  var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
  var downloadAnchorNode = document.createElement('a');
  downloadAnchorNode.setAttribute("href",     dataStr);
  downloadAnchorNode.setAttribute("download", exportName + ".json");
  document.body.appendChild(downloadAnchorNode); // required for firefox
  downloadAnchorNode.click();
  downloadAnchorNode.remove();
}

function download(data, download = true) {
 
  // Creating a Blob for having a csv file format
  // and passing the data with type
  var blob = new Blob([data], { type: 'text/csv' });

  // Creating an object for downloading url
  var url = window.URL.createObjectURL(blob)

  
  if(download){

    // Creating an anchor(a) tag of HTML
    var a = document.createElement('a')

    // Passing the blob downloading url
    a.setAttribute('href', url)
    
    // Setting the anchor tag attribute for downloading
    // and passing the download file name
    a.setAttribute('download', 'download.csv');
    
    // Performing a download with click
    a.click()
  }else{
    return url;
  }
}

const csvmaker = function (data) {

  // Empty array for storing the values
  var csvRows = [];
  var serializeData = Object.values(data);

  

  // Headers is basically a keys of an
  // object which is id, name, and
  var headers = Object.keys(serializeData[0]);

  // As for making csv format, headers
  // must be separated by comma and
  // pushing it into array
  csvRows.push(headers.join(','));

  // Pushing Object values into array
  // with comma separation

  serializeData.forEach(item => {
    const values = Object.values(item).join(',');
    csvRows.push(values)
  });


  // Returning the array joining with new line
  return csvRows.join('\n')
}



jQuery(function($) {
    $('#the-list button.editinline').click(function( e ) {
  e.preventDefault();
  var $tr = $(this).closest('tr');
  var catField = $tr.find('td.szcs_cat_points_field');
  var brandField = $tr.find('td.szcs_brand_points_field');
  // Update field
  if(catField.length){
    cat = parseFloat(catField.text()) || '';
    $('tr.inline-edit-row :input[name="szcs_cat_points_field"]').val(cat);
  }
  if(brandField.length){
    brand = parseFloat(brandField.text()) || '';
    $('tr.inline-edit-row :input[name="szcs_brand_points_field"]').val(brand);
  }
});
});


jQuery(function($) {
  var taxonomy = $('input[name="taxonomy"]');
  if(taxonomy.length){
  $('#posts-filter').submit(function(e){
    
    var form = $(this);
    var formData = new FormData(form[0]);
    
    if(formData.get('action') === 'edit_points'){
      e.preventDefault();
      hideNotification($);


      // remove any previous screens
      $('#szcs_change_points_screen').remove();

      var wrapper = $('<div />').attr('id', 'szcs_change_points_screen').css('display', 'none');

      var innerWrapper = $('<div />').addClass('szcs_change_points_content');

      var newForm = $('<form />').attr({
        id: 'szcs_change_points_form',
        method: 'post',
      });

      var title = $('<h2 />').text('Change Points');

      var inputWrapper = $('<div />').addClass('form-field form-required term-name-wrap');

      var labelText = 'Points';
      if(taxonomy.val() === 'product_cat')
        labelText = 'Categories Points';
      else if(taxonomy.val() === 'product_brand')
        labelText = 'Brands Points';

      var label = $('<label />').attr('for', 'szcs_points_field').text(labelText);
      var input = $('<input />').attr({
        type: 'number',
        name: 'szcs_points_field',
        id: 'szcs_points_field',
        step: '0.01',
        min: '0',
      });

      var button = $('<button />').attr({
        type: 'submit',
        id: 'szcs_change_points_submit',
        class: 'button button-primary',
      }).text('Change Points');

      var buttonWrapper = $('<p />');

      var close = $('<button />').attr({
        type: 'button',
        class: 'dashicons dashicons-no-alt',
      }).click(function(){
        wrapper.fadeOut(300, function(){
          wrapper.remove();
        });
      });

      var loading = $('<div />').attr({id: 'szcs_coupon_loading'}).css('display', 'none');

      inputWrapper.append(close, label, input);

      buttonWrapper.append(button, loading);
      newForm.append(title, inputWrapper, buttonWrapper);
      innerWrapper.append(newForm);
      wrapper.append(innerWrapper);
      $('body').append(wrapper);

      wrapper.fadeIn(300);

      // add event listener to the form
      newForm.submit(function(e){
        e.preventDefault();

        var pointsValue = input.val();

        // exit if the points value is empty or the tags are not selected
        if(!formData.get('delete_tags[]')){
          showNotification($, 'Please select at least one tag', 'error');
          wrapper.fadeOut(300, function(){
            wrapper.remove();
          });
          return;
        }

        // get the points value
        var points = parseFloat(pointsValue);

        if(points < 0 || points > 100 || isNaN(points)){
          showNotification($, 'Points must be between 0 and 100', 'error');
          wrapper.fadeOut(300, function(){
            wrapper.remove();
          });
          return;
        }

        // add the points value to the form data
        formData.set('points', points);

        // add the action to the form data
        formData.append('action', 'szcs_change_taxonomy_points');

        // add the nonce to the form data
        formData.append('nonce', SZCS_VARS.nonce);

        // show the loading
        loading.fadeIn(300);

        // make a fetch request
        fetch(`${SZCS_VARS.ajaxUrl}`, {
          method: 'post',
          body: formData,
        })
        .then(res => {
          return res.json();
        })
        .then(data => {
          if(data.success){
            showNotification($, data.message, 'updated');
            // reload the page
            if(data.term_ids.length){
              data.term_ids.forEach((termId) => {
                var term = $(`#tag-${termId}`);
                if(term.length){
                  var pointsField = term.find('td[data-colname="Points"]');
                  if(pointsField.length){
                    pointsField.text(points+'%');
                  }
                }
              });
            }
          }else{
            showNotification($, data.message, 'error');
          }
        }).finally(() => {
          wrapper.fadeOut(300, function(){
            wrapper.remove();
          });
        })

      });
    }
  });
}
});



function showNotification($, message, type = 'error'){
  hideNotification($);
  var notice = $('<div />').attr({
    id: 'message',
    class: type +' szcs-coupon-notice',
  }).append($('<p />').html(message)).insertAfter('.wp-header-end');

}

function hideNotification($){
  $('.szcs-coupon-notice').remove();
}



// auto select category and brand
jQuery(function($) {

  if(SZCS_VARS.autoSelectCategory){

    var taxonomies = ['product_cat', 'product_brand'];

    function checkParents(checkbox, taxonomy){
      var parent = checkbox.closest('.children').closest('li').find('input[type="checkbox"]:first');
      
      while(parent.length){
        var all = $(`#${taxonomy}div input[value="${parent.val()}"]`);
        all.prop('checked', true);
        parent = parent.closest('.children').closest('li').find('input[type="checkbox"]:first');
      }
    }
    
    function unCheckAllChildren(checkbox, taxonomy){
      var children = $(`#${taxonomy}div input[value="${checkbox.val()}"]`).closest('li').find('input[type="checkbox"]');
      children.each(function(){
        $(this).prop('checked', false);
        $(`#${taxonomy}-pop input[value="${$(this).val()}"]`).prop('checked', false);
      });
    }
    
    taxonomies.forEach((taxonomy) => {
    $(`#${taxonomy}div`).on('click', 'input[type="checkbox"]', function(e){
      var checkbox = $(this);
      if(checkbox.is(':checked')){
        var original = $(`#${taxonomy}-all input[value="${checkbox.val()}"]`);
        checkParents(original, taxonomy);
      }
    });
  });

  // uncheck children when parent is unchecked
  taxonomies.forEach((taxonomy) => {
    $(`#${taxonomy}div`).on('click', `input[type="checkbox"]`, function(e){
      var checkbox = $(this);
      if(!checkbox.is(':checked')){
        unCheckAllChildren(checkbox, taxonomy);
      }
    });
  });
}
});