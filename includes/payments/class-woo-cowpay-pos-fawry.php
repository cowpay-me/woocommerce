<?php




/**
 * Pay At Fawry payment method
 */
class WC_Payment_Gateway_Cowpay_POS_Fawry extends WC_Payment_Gateway_Cowpay
{

    // Setup our Gateway's id, description and other values
    function __construct()
    {
        parent::__construct();

        // The global ID for this Payment method
        $this->id = "cowpay_payat_fawry";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = esc_html__("Cowpay Pay at Fawry", 'cowpay');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = esc_html__("Cowpay Pay Using Cash at Fawry Payment Gateway for WooCommerce", 'cowpay');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = esc_html__("Cowpay POS Fawry", 'cowpay');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        //$this->icon = plugin_dir_url(__FILE__) . 'LOGO.png';
        $this->icon = WOO_COWPAY_PLUGIN_URL . '/public/images/fawry-logo.png';

        // Bool. Can be set to true if you want payment fields to show on the checkout 
        $this->has_fields = false;

        // if set to true, an admin notice will displayed if not on https
        $this->require_ssl = true;

        // add support for pos
        $this->supports[] = 'pos';

        parent::init();
    }

    /**
     * Build the administration fields for this specific Gateway.
     * This settings shows up at WooCommerce payments tap when this method is selected
     * @todo consider moving the configuration here and remove cowpay from admin side menu
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'        => __('Enable / Disable', 'cowpay'),
                'label'        => __('Enable this payment gateway', 'cowpay'),
                'type'        => 'checkbox',
                'default'    => 'no',
            ),
            'title' => array(
                'title'        => __('Title', 'cowpay'),
                'type'        => 'text',
                'desc_tip'    => __('Payment title the customer will see during the checkout process.', 'cowpay'),
                'default'    => __('Pay at Fawry', 'cowpay'),
            ),
            'description' => array(
                'title'        => __('Description', 'cowpay'),
                'type'        => 'textarea',
                'desc_tip'    => __('Payment description the customer will see during the checkout process.', 'cowpay'),
                'default'    => __('Pay using Fawry reference code.', 'cowpay'),
                'css'        => 'max-width:350px;'
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function process_payment($order_id)
    {
        $customer_order = wc_get_order($order_id);

        $merchant_ref_id = $this->get_cp_merchant_reference_id($customer_order);
        $customer_profile_id = $this->get_cp_customer_profile_id($customer_order);
        $description = $this->get_cp_description($customer_order);
        $amount = $customer_order->order_total;
        $signature = $this->get_cp_signature($amount, $merchant_ref_id, $customer_profile_id);

        $req_params = array(
            'merchant_reference_id' => $merchant_ref_id,
            'customer_merchant_profile_id' => $customer_profile_id,
            'customer_name' => $customer_order->get_formatted_billing_full_name(),
            'customer_email' => $customer_order->get_billing_email(),
            'customer_mobile' => $customer_order->get_billing_phone(),
            'amount' => $amount,
            'signature' => $signature,
            'description' => $description
        );
        $response = WC_Gateway_Cowpay_API_Handler::get_instance()->charge_fawry($req_params);
        $messages = $this->get_user_error_messages($response);
        if (empty($messages)) { // success
            // update order meta
            $this->set_cowpay_meta($customer_order, $req_params, $response);

            // display to the admin
            $customer_order->add_order_note(__($response->status_description));

            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($customer_order),
            );
        } else { // error
            // update order meta
            $this->set_cowpay_meta($customer_order, $req_params);

            // display to the customer
            foreach ($messages as $m) {
                wc_add_notice($m, "error");
            }

            // display to the admin
            $one_line_message = join(', ', $messages);
            $customer_order->add_order_note("Error: $one_line_message");
        }
    }
}
