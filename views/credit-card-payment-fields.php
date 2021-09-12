<script>
(function ($) {
    COWPAYOTPDIALOG.init()
    // COWPAYOTPDIALOG.load('b61aea044ef182ea79859f59526b043b') // the token from the charge request response
})(jQuery);

</script>
<script>
    window.addEventListener('message', function (e) {
        if (e.data && e.data.message_source === 'cowpay') {
            let paymentStatus = e.data.payment_status,
                    cowpayReferenceId = e.data.cowpay_reference_id;
            console.log('my catches =================>',e.data);
            // take an action based on the values
        }

    }, false);
</script>
<?php

    echo "";