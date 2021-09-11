<?php




/**
 * Cowpay Payment Gateway for cowpay checkout method
 */
class WC_Payment_Gateway_Cowpay_Checkout extends WC_Payment_Gateway_Cowpay
{

    public $notify_url;

    // Setup our Gateway's id, description and other values
    function __construct()
    {
        parent::__construct();

        // The global ID for this Payment method
        $this->id = "cowpay_checkout";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = esc_html__("Cowpay Checkout", 'cowpay');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = esc_html__("Cowpay Checkout Payment Gateway for WooCommerce", 'cowpay');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = esc_html__("Cowpay Checkout", 'cowpay');

        // Bool. Can be set to true if you want payment fields to show on the checkout 
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = false;

        // register required scripts for credit card payment method
        add_action('wp_enqueue_scripts', array($this, 'cowpay_enqueue_scripts'));

        // get notify url for our payment.
        // when this url is entered, an action is called from WooCommerce => woocommerce_api_<class_name>
        $this->notify_url = WC()->api_request_url('WC_Payment_Gateway_Cowpay_Checkout');
        // we then register our otp response check for this action, and call $this->check_otp_response()
        add_action('woocommerce_api_wc_payment_gateway_cowpay_checkout', array($this, 'check_cowpay_response'));

        parent::init();
    }

    /**
     * Called when $this->notify_url is entered
     * check otp response and redirect user to corresponding page
     */
    public function check_cowpay_response()
    {
        // check cowpay response cline-side
        //* @security this should not complete the payment until confirmation from server-to-server validation

        if (!$this->is_valid_cowpay_response()) return false;
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
        $order->add_order_note("Cowpay Status: $payment_status");
        if ($payment_status == 'PAID') {
            WC()->cart->empty_cart();
            // don't complete payment here, only in server-server notification
            wp_safe_redirect($this->get_return_url($order));
            exit;
        } else if ($payment_status == 'FAILED' || $payment_status == 'UNPAID') { //? Is UNPAID always means FAILED
            wc_add_notice("Your Payment has failed", 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        } else {
            wp_safe_redirect(get_home_url());
            exit;
        }
    }

    private function is_valid_cowpay_response()
    {
        return isset($_GET['message_source'])
            && $_GET['message_source'] == "cowpay"
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
                'default'    => esc_html__('Cowpay Checkout', 'cowpay'),
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

        $request_params = array(
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

        $response = WC_Gateway_Cowpay_API_Handler::get_instance()->load_iframe_token($request_params);
        $messages = $this->get_user_error_messages($response);
        if (empty($messages)) { // success
            // update order meta
            $this->set_cowpay_meta($customer_order, $request_params, $response);
            $redirect_url = WC_Gateway_Cowpay_API_Handler::get_instance()->get_checkout_url($response->token, $this->notify_url);

            // display to the admin
            $customer_order->add_order_note(__($response->status_description));
            $res = array(
                'result' => 'success',
                'redirect' =>  $redirect_url
            );
            return $res;
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
     * register cowpay script
     * method will be fired by wp_enqueue_scripts action
     * the script registration will ensure that the file will only be loaded once and only when needed
     * @return void
     */
    public function cowpay_enqueue_scripts()
    {
        wp_enqueue_style('cowpay_public_css', plugin_dir_url(__FILE__) . '/public/css/woo-cowpay-public.css');
    }
}
