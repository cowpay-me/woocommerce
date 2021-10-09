<?php




/**
 * Cowpay Payment Gateway for Cash Collection method
 */
class WC_Payment_Gateway_Cowpay_Cash_Collection extends WC_Payment_Gateway_Cowpay
{

    public $notify_url;

    // Setup our Gateway's id, description and other values
    function __construct()
    {
        parent::__construct();

        // The global ID for this Payment method
        $this->id = "cowpay_cash_collection";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = esc_html__("Cowpay Cash Collection", 'cowpay');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = esc_html__("Cowpay Cash Collection Payment Gateway for WooCommerce", 'cowpay');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = esc_html__("Cowpay Cash Collection", 'cowpay');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = WOO_COWPAY_PLUGIN_URL . '/public/images/visa.svg';

        // Bool. Can be set to true if you want payment fields to show on the checkout 
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;

        // register required scripts for Cash Collection payment method
        add_action('wp_enqueue_scripts', array($this, 'cowpay_enqueue_scripts'));

        // get notify url for our payment.
        // when this url is entered, an action is called from WooCommerce => woocommerce_api_<class_name>
        $this->notify_url = WC()->api_request_url('WC_Payment_Gateway_Cowpay_Cash_Collection');
        // add_action('wp_enqueue_scripts','check_otp_response');

        parent::init();
    }

    /**
	 * Get a link to the transaction on the 3rd party gateway site (if applicable).
	 *
	 * @param  WC_Order $order the order object.
	 * @return string transaction URL, or empty string.
	 */
	public function get_transaction_url( $order ) {

		$return_url     = '';
		$transaction_id = $order->get_transaction_id();
		if ( ! empty( $this->view_transaction_url ) && ! empty( $transaction_id ) ) {
			$return_url = sprintf( $this->view_transaction_url, $transaction_id );
		}

		return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
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
                'default'    => esc_html__('Cash Collection', 'cowpay'),
            ),
            'description' => array(
                'title'        => esc_html__('Description', 'cowpay'),
                'type'        => 'textarea',
                'desc_tip'    => esc_html__('Payment description the customer will see during the checkout process.', 'cowpay'),
                'default'    => esc_html__('Pay securely using your Cash Collection.', 'cowpay'),
                'css'        => 'max-width:350px;'
            ),
        );
    }


    /**
     * builds the Cash Collection request params
     */
    private function create_payment_request($order_id)
    {

        $customer_order = wc_get_order($order_id);

        $merchant_ref_id = $this->get_cp_merchant_reference_id($customer_order);
        $customer_profile_id = $this->get_cp_customer_profile_id($customer_order);
        $description = $this->get_cp_description($customer_order);
        $amount = $customer_order->get_total(); // TODO: format it like 10.00;
        $signature = $this->get_cp_signature($amount, $merchant_ref_id, $customer_profile_id);
        $floor = $_POST['cowpay_cash_collection-floor'];
        $appartment = $_POST['cowpay_cash_collection-appartment'];
        $city_code = $this->get_city_code($customer_order);
        if(!$city_code){
            wc_add_notice("this payment method dose not support this area", "error");
        }
        $request_params = array(
            // redirect user to our controller to check otp response
            'return_url' => $this->notify_url,
            'merchant_reference_id' => $merchant_ref_id,
            'customer_merchant_profile_id' => $customer_profile_id,
            'customer_name' => $customer_order->get_formatted_billing_full_name(),
            'customer_email' => $customer_order->get_billing_email(),
            'customer_mobile' => $customer_order->get_billing_phone(),
            'address'=>$customer_order->get_billing_address_1(),
            'district'=>$customer_order->get_billing_city(),
            'apartment'=>$appartment,
            'floor'=>$floor,
            'city_code'=>$city_code,
            'amount' => $amount,
            'signature' => $signature,
            'description' => $description
        );
        return $request_params;
    }
    public function get_city_code($order)
    {
        $avilableCountries = [
            "EGC" => "EG-01", //cairo => Downtown Cairo
            "EGGZ"=>"EG-01", //Giza & Haram	=> Giza
            "EGALX"=>"EG-02", //Downtown Alex => Downtown Alexandria
            // ""=>"EG-03", //Sahel => Sahel
            "EGBH"=>"EG-04", //Behira => Damanhour
            "EGDK"=>"EG-05", //Dakahlia => Al Mansoura
            "EGKB"=>"EG-06", //El Kalioubia	=> Sheben Alkanater
            "EGGH"=>"EG-07", //Gharbia => Tanta
            "EGKFS"=>"EG-08", //Kafr Alsheikh => Kafr Alsheikh
            "EGMNF"=>"EG-09", //Monufia => Shebin El Koom
            "EGSHR"=>"EG-10", //Sharqia => Zakazik
            "EGIS"=>"EG-11", //Isamilia => Hay 1
            "EGSUZ"=>"EG-12", //Suez => Al Suez District
            "EGPTS"=>"EG-13", //Port Said => Sharq
            "EGDT"=>"EG-14", //Damietta => Damietta
            "EGFYM"=>"EG-15", //Fayoum => Fayoum
            "EGBNS"=>"EG-16", //Bani Suif => Bani Suif
            "EGAST"=>"EG-17", //Asyut => Asyut
            "EGSHG"=>"EG-18", //Sohag => Sohag
            "EGMN"=>"EG-19", //Menya => Menya
            "EGKN"=>"EG-20", //Qena => Qena
            "EGASN"=>"EG-21", //Aswan => Aswan
            "EGLX"=>"EG-22", //Luxor => Luxor
        ];
        $avilableKeys = array_keys($avilableCountries);
        return in_array($order->get_billing_state(),$avilableKeys)?$avilableCountries[$order->get_billing_state()]:false;
    }

    /**
     * @inheritdoc
     */
    public function process_payment($order_id)
    {
        $customer_order = wc_get_order($order_id);
        // create request 
        $request_params = $this->create_payment_request($order_id);
        $response = WC_Gateway_Cowpay_API_Handler::get_instance()->charge_cash_collection($request_params);
        $messages = $this->get_user_error_messages($response);
        if (empty($messages)) { // success
            // update order meta
            $this->set_cowpay_meta($customer_order, $request_params, $response);

            // display to the admin
            $customer_order->add_order_note(__($response->status_description));            
            // not 3DS:
            WC()->cart->empty_cart();
            // wait server-to-server notification
            $customer_order->update_status( 'pending' );

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
     * Renders the Cash Collection form
     * @todo: should use the woo_cowpay_view function (add cc-form.php inside views folder)
     */

    public function form()
    {
        // wp_enqueue_script('wc-cash-collection-form');
        woo_cowpay_view("cash-collection-payment-fields"); // have no data right now
    }
   
    /**
     * This function used by WC if $this->has_fields is true.
     * This returns the form that usually contains the Cash Collection data.
     */
    public function payment_fields()
    {
        // echo "<p>Pay securely using your Cash Collection.</p>";
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
        wp_enqueue_script('cowpay_card_js', "$schema://$host/js/plugins/CardPlugin.js");
        // wp_enqueue_script('cowpay_otp_js', "$schema://$host/js/plugins/OTPPaymentPlugin.js");
        wp_enqueue_script('woo-cowpay', WOO_COWPAY_PLUGIN_URL . 'public/js/woo-cowpay-public.js');

        wp_enqueue_style('cowpay_public_css', WOO_COWPAY_PLUGIN_URL . 'public/css/woo-cowpay-public.css');

        // Pass ajax_url to cowpay_js
        // this line will pass `admin_url('admin-ajax.php')` value to be accessed through
        // plugin_ajax_object.ajax_url in javascipt file with the handle cowpay_js (the one above)
        // wp_localize_script('cowpay_js', 'cowpay_data', array('ajax_url' => admin_url('admin-ajax.php')));
        
    }
}
