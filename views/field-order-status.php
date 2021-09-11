<select name='cowpay_settings[order_status]'>
    <option value='wc-processing' <?php selected($options['order_status'], "wc-processing") ?>><?php esc_html_e("Processing", "cowpay") ?></option>
    <option value='wc-completed' <?php selected($options['order_status'], "wc-completed") ?>><?php esc_html_e("Completed", "cowpay") ?></option>
</select>
<p><?php esc_html_e('Choose the order status when the order paid.', 'cowpay') ?></p>