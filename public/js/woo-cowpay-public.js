jQuery(window).ready(function(){
  "use strict";
  jQuery('body').on('updated_checkout', function(){
    if(cowpay_data.tansaction_id){
      COWPAYOTPDIALOG.init()
      COWPAYOTPDIALOG.load(cowpay_data.tansaction_id); // the token from the charge request response
    }
  });
  
  window.addEventListener('message', function (e) {
    if (e.data && e.data.message_source === 'cowpay') {
        let paymentStatus = e.data.payment_status,
            cowpayReferenceId = e.data.cowpay_reference_id;

        jQuery.ajax({
          method: 'post',
          url: cowpay_data.ajax_url,  // don't forget to pass ajax which we send by data object via wp_localize_script(). otherwise the ajax is not working.
          cache: false,
          dataType: 'json',  // we need the result back to ajax with json data type.
          data: {
            action: 'check_otp_response',  // we hook get_job_application function to wp_ajax_get_job_applications earlier.
            data: e.data
          },
          success: function (response) {
            if(response.redirect){
              window.location.href = response.redirect;
            }else{
              var wooError = jQuery('.woocommerce-error').text(response.message).fadeIn(160);
                setTimeout(function(){
                    wooError.fadeOut(160);
                }, 4000);
            }
            // display the result data from response variable into the screen
            // it will return the response as json which we set the dataType as json.
          },
          error: function (xhr, status, error) {
            var err = eval("(" + xhr.responseText + ")");
          }
        });
        // take an action based on the values
    }
  
  }, false);
});
