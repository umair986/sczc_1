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
      fetch(`${SZCS_VARS.siteurl}${ajaxurl}`, {
        method: 'post',
        body: formData,
      })
      .then(res => {
        return res.json();
      })
      .then(data => {
        if(typeof data === 'object'){
          var url = download(csvmaker(data), false);
          $('<div />').attr({
            id: 'message',
            class: 'updated szcs-coupon-notice',
          }).append($('<p />').html(`Your export is ready <a href="${url}" download="vouchers.csv">Click Here</a> to download.`)).insertAfter('.wp-header-end');
        }else if(typeof data === 'string'){
          $('<div />').attr({
            id: 'message',
            class: 'error szcs-coupon-notice',
          }).append($('<p />').text(data)).insertAfter('.wp-header-end');
        }
        $('#szcs_coupon_login_screen').remove();
      })
      .catch(e => {
        $('<div />').attr({
          id: 'error',
          class: 'error szcs-coupon-notice',
        }).append($('<p />').html(`Something went wrong.`)).insertAfter('.wp-header-end');
        $('#szcs_coupon_login_screen').remove();
      })
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
