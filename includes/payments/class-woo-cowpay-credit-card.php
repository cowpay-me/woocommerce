<?php




/**
 * Cowpay Payment Gateway for credit card method
 */
class WC_Payment_Gateway_Cowpay_CC extends WC_Payment_Gateway_Cowpay
{

    public $notify_url;

    // Setup our Gateway's id, description and other values
    function __construct()
    {
        parent::__construct();

        // The global ID for this Payment method
        $this->id = "cowpay_credit_card";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = esc_html__("Cowpay Credit Card", 'cowpay');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = esc_html__("Cowpay Credit Card Payment Gateway for WooCommerce", 'cowpay');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = esc_html__("Cowpay Credit Card", 'cowpay');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = WOO_COWPAY_PLUGIN_URL . '/public/images/visa-credit.png';

        // Bool. Can be set to true if you want payment fields to show on the checkout 
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;

        // register required scripts for credit card payment method
        add_action('wp_enqueue_scripts', array($this, 'cowpay_enqueue_scripts'));

        // get notify url for our payment.
        // when this url is entered, an action is called from WooCommerce => woocommerce_api_<class_name>
        $this->notify_url = WC()->api_request_url('WC_Payment_Gateway_Cowpay_CC');
        // we then register our otp response check for this action, and call $this->check_otp_response()
        add_action('woocommerce_api_wc_payment_gateway_cowpay_cc', array($this, 'check_otp_response'));

        parent::init();
    }

    /**
     * Called when $this->notify_url is entered
     * check otp response and redirect user to corresponding page
     */
    public function check_otp_response()
    {
        // check otp response cline-side
        //* @security this should not complete the payment until confirmation from server-to-server validation

        // from cowpay docs, otp response contains these params
        /**
         * {
         *    callback_type: "order_status_update",
         *    cowpay_reference_id: "1000242",
         *    message_source: "cowpay",
         *    message_type: "cowpay_browser_callback",
         *    payment_gateway_reference_id: "971498564",
         *    payment_status: "PAID" // or "FAILED"
         *  }
         */
        if (!$this->is_valid_otp_response()) return false;
        // get order by reference id
        // TODO?: should we get it from the session instead
        $cowpay_reference_id = $_GET['cowpay_reference_id'];
        $payment_status = $_GET['payment_status'];
        $order = $this->get_order_by('cp_cowpay_reference_id', $cowpay_reference_id);
        if ($order === false) {
            // order doesn't exit, invalid cowpay reference id, redirect to home
            wp_safe_redirect(get_home_url());
            exit;
        }
        $order->add_order_note("OTP Status: $payment_status");
        if ($payment_status == 'PAID') {
            WC()->cart->empty_cart();
            // don't complete payment here, only in server-server notification
            wp_safe_redirect($this->get_return_url($order));
            exit;
        } else if ($payment_status == 'FAILED' || $payment_status == 'UNPAID') { //? Is UNPAID always means FAILED
            wc_add_notice("Your OTP has failed", 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        } else {
            wp_safe_redirect(get_home_url());
            exit;
        }
    }

    private function is_valid_otp_response()
    {
        return isset($_GET['callback_type'])
            && $_GET['callback_type'] == "order_status_update"
            && isset($_GET['cowpay_reference_id']);
    }

    /**
     * Find order where order[$key] = $value.
     */
    private function get_order_by($key, $value)
    {
        $order = wc_get_orders(array($key => $value, 'limit' => 1));
        if (empty($order)) return false;
        return $order[0];
    }

    /**
     * Build the administration fields for this specific Gateway
     * This settings shows up at WooCommerce payments tap when this method is selected
     * @todo consider moving the configuration here and remove cowpay from admin side menu
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'        => esc_html__('Enable / Disable', 'cowpay'),
                'label'        => esc_html__('Enable this payment gateway', 'cowpay'),
                'type'        => 'checkbox',
                'default'    => 'no',
            ),
            'title' => array(
                'title'        => esc_html__('Title', 'cowpay'),
                'type'        => 'text',
                'desc_tip'    => esc_html__('Payment title the customer will see during the checkout process.', 'cowpay'),
                'default'    => esc_html__('Credit card', 'cowpay'),
            ),
            'description' => array(
                'title'        => esc_html__('Description', 'cowpay'),
                'type'        => 'textarea',
                'desc_tip'    => esc_html__('Payment description the customer will see during the checkout process.', 'cowpay'),
                'default'    => esc_html__('Pay securely using your credit card.', 'cowpay'),
                'css'        => 'max-width:350px;'
            ),
        );
    }


    /**
     * builds the credit card request params
     */
    private function create_payment_request($order_id)
    {

        $customer_order = wc_get_order($order_id);

        $merchant_ref_id = $this->get_cp_merchant_reference_id($customer_order);
        $customer_profile_id = $this->get_cp_customer_profile_id($customer_order);
        $description = $this->get_cp_description($customer_order);
        $amount = $customer_order->get_total(); // TODO: format it like 10.00;
        $signature = $this->get_cp_signature($amount, $merchant_ref_id, $customer_profile_id);

        $card_number = $_POST['cowpay_credit_card-card-number'];
        $card_number = str_replace(' ', '', $card_number);
        $card_number = sanitize_text_field($card_number);

        $expiry_year = $_POST['cowpay_credit_card-expiry-year'];
        $expiry_year = isset($expiry_year) ? sanitize_text_field($expiry_year) : '01';

        $expiry_month = $_POST['cowpay_credit_card-expiry-month'];
        $expiry_month = isset($expiry_month) ? sanitize_text_field($expiry_month) : '21';
        $cvv = $_POST['cowpay_credit_card-card-cvc'];
        $cvv = isset($cvv) ? sanitize_text_field($cvv) : '';

        $request_params = array(
            // redirect user to our controller to check otp response
            'return_url' => $this->notify_url,

            'card_number' => $card_number,
            'cvv' => $cvv,
            'expiry_month' => $expiry_month,
            'expiry_year' => $expiry_year,

            'merchant_reference_id' => $merchant_ref_id,
            'customer_merchant_profile_id' => $customer_profile_id,
            'customer_name' => $customer_order->get_formatted_billing_full_name(),
            'customer_email' => $customer_order->get_billing_email(),
            'customer_mobile' => $customer_order->get_billing_phone(),
            'amount' => $amount,
            'signature' => $signature,
            'description' => $description
        );
        return $request_params;
    }

    /**
     * @inheritdoc
     */
    public function process_payment($order_id)
    {
        $customer_order = wc_get_order($order_id);
        $request_params = $this->create_payment_request($order_id);

        $response = WC_Gateway_Cowpay_API_Handler::get_instance()->charge_cc($request_params);
        $messages = $this->get_user_error_messages($response);
        if (empty($messages)) { // success
            // update order meta
            $this->set_cowpay_meta($customer_order, $request_params, $response);

            // display to the admin
            $customer_order->add_order_note(__($response->status_description));

            if (isset($response->three_d_secured) && $response->three_d_secured == true) {
                // TODO: add option to use OTP plugin when return_url is not exist
                $res = array(
                    'result' => 'success',
                    'redirect' =>  $response->return_url
                );
                return $res;
            }
            // not 3DS:
            WC()->cart->empty_cart();
            // wait server-to-server notification
            //// $customer_order->payment_complete();

            // Redirect to thank you page
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($customer_order),
            );
        } else { // error
            // update order meta
            $this->set_cowpay_meta($customer_order, $request_params);

            // display to the customer
            foreach ($messages as $m) {
                wc_add_notice($m, "error");
            }

            // display to the admin
            $one_line_message = join(', ', $messages);
            $customer_order->add_order_note("Error: $one_line_message");
        }
    }

    /**
     * Return whether or not this gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true a
     * redirect will occur to the settings page instead.
     *
     * @since 3.4.0
     * @return bool
     */
    public function needs_setup()
    {
        return !is_email($this->email);
    }


    /**
     * Renders the credit card form
     * @todo: should use the woo_cowpay_view function (add cc-form.php inside views folder)
     */
    public function form()
    {
        wp_enqueue_script('wc-credit-card-form');
        woo_cowpay_view("credit-card-payment-fields"); // have no data right now

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

        if (!$this->supports('credit_card_form_cvc_on_saved_method')) {
            $default_fields['card-cvc-field'] = $cvc_field;
        }


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

        if ($this->supports('credit_card_form_cvc_on_saved_method')) {
            echo '<fieldset>' . $cvc_field . '</fieldset>'; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
        }
        ?>
        <div id="cowpay-otp-container"></div>
<?php

    }

    /**
     * This function used by WC if $this->has_fields is true.
     * This returns the form that usually contains the credit card data.
     */
    public function payment_fields()
    {
        // echo "<p>Pay securely using your credit card.</p>";
        if ($this->supports('tokenization') && is_checkout()) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            $this->form();
            $this->save_payment_method_checkbox();
        } else {
            $this->form();
        }
        echo '<style> .form-row.woocommerce-SavedPaymentMethods-saveNew {
    	display: none !important;}</style>';
    }

    // Validate fields
    public function validate_fields()
    {
        /**
         * Return true if the form passes validation or false if it fails.
         * You can use the wc_add_notice() function if you want to add an error and display it to the user.
         * TODO: validate and display to the user useful information
         */
        return true;
    }

    /**
     * register cowpay otp script
     * method will be fired by wp_enqueue_scripts action
     * the script registration will ensure that the file will only be loaded once and only when needed
     * @return void
     */
    public function cowpay_enqueue_scripts()
    {
        $host = $this->cp_admin_settings->get_active_host();
        $schema = is_ssl() ? "https" : "http";
        wp_enqueue_script('cowpay_otp_js', "$schema://$host/js/plugins/OTPPaymentPlugin.js");
        wp_enqueue_script('cowpay_js', plugin_dir_url(__FILE__) . '/public/js/woo-cowpay-public.js', ['cowpay_otp_js']);

        wp_enqueue_style('cowpay_public_css', plugin_dir_url(__FILE__) . '/public/css/woo-cowpay-public.css');

        // Pass ajax_url to cowpay_js
        // this line will pass `admin_url('admin-ajax.php')` value to be accessed through
        // plugin_ajax_object.ajax_url in javascipt file with the handle cowpay_js (the one above)
        wp_localize_script('cowpay_js', 'plugin_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }
}
