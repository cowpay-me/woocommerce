<?php
$fields = array();

$year_field = '<select id="' . esc_attr($this->id) . '-expiry-year" name="' . esc_attr($this->id) . '-expiry-year" class="cowpay_feild input-text  wc-credit-card-form-expiry-year" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="2" ' . $this->field_name('expiry-year') . ' style="width:100px">
<option value="" disabled="disabled">' . esc_html__("Year", "wpqa") . '</option>';
for ($i = 0; $i <= 10; $i++) {
    $year_field .= '<option value="' . date('y', strtotime('+' . $i . ' year')) . '">' . date('y', strtotime('+' . $i . ' year')) . '</option>';
}
$year_field .= '</select>';


$cvc_field = '<p class="form-row form-row-last">
    <label for="' . esc_attr($this->id) . '-card-cvc">' . esc_html__('Card code', 'cowpay') . '&nbsp;<span class="required">*</span></label>
    <input  id="' . esc_attr($this->id) . '-card-cvc" name="' . esc_attr($this->id) . '-card-cvc" class="cowpay_feild input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="3" placeholder="' . esc_attr__('CVC', 'cowpay') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
</p>';

$default_fields = array(
    'card-number-field' => '<p class="form-row form-row-wide">
        <label for="' . esc_attr($this->id) . '-card-number">' . esc_html__('Card number', 'cowpay') . '&nbsp;<span class="required">*</span></label>
        <input  maxlength="22" id="' . esc_attr($this->id) . '-card-number" name="' . esc_attr($this->id) . '-card-number" class="cowpay_feild input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
    </p>',
    'card-expiry-field' => '<p class="form-row form-row-first">
    <label for="' . esc_attr($this->id) . '-expiry-month">' . esc_html__('Expiry (MM/YY)', 'cowpay') . '&nbsp;<span class="required">*</span></label>
    <select id="' . esc_attr($this->id) . '-expiry-month" name="' . esc_attr($this->id) . '-expiry-month" class="cowpay_feild input-text js_field-country wc-credit-card-form-expiry-month" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="2" ' . $this->field_name('expiry-month') . ' style="width:100px;float:left;">
        <option value="" disabled="disabled">' . esc_html__("Month", "wpqa") . '</option>
        <option value="01">01 - ' . esc_html__("January", "wpqa") . '</option>
        <option value="02">02 - ' . esc_html__("February", "wpqa") . '</option>
        <option value="03">03 - ' . esc_html__("March", "wpqa") . '</option>
        <option value="04">04 - ' . esc_html__("April", "wpqa") . '</option>
        <option value="05">05 - ' . esc_html__("May", "wpqa") . '</option>
        <option value="06">06 - ' . esc_html__("June", "wpqa") . '</option>
        <option value="07">07 - ' . esc_html__("July", "wpqa") . '</option>
        <option value="08">08 - ' . esc_html__("August", "wpqa") . '</option>
        <option value="09">09 - ' . esc_html__("September", "wpqa") . '</option>
        <option value="10">10 - ' . esc_html__("October", "wpqa") . '</option>
        <option value="11">11 - ' . esc_html__("November", "wpqa") . '</option>
        <option value="12">12 - ' . esc_html__("December", "wpqa") . '</option>
    </select>
    ' . $year_field . '
</p>',
);


$default_fields['card-cvc-field'] = $cvc_field;


$fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
?>

<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>

    <?php
    foreach ($fields as $field) {
        echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
    }
    ?>
    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
    <div class="clear"></div>

</fieldset>

<?php

// if ($this->supports('credit_card_form_cvc_on_saved_method')) {
//     echo '<fieldset>' . $cvc_field . '</fieldset>'; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
// }
?>
<div id="cowpay-otp-container"></div>