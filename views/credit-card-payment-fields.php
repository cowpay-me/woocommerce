<script>
(function ($) {
    COWPAYOTPDIALOG.init()
    COWPAYOTPDIALOG.load('ece91a023c7cf8f274c141e82af64d5b') // the token from the charge request response
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