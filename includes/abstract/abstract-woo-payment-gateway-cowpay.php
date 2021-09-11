<?php

/**
 * Base class for all cowpay gateways (methods)
 */
abstract class WC_Payment_Gateway_Cowpay extends WC_Payment_Gateway
{
    public $require_ssl = false;
    public Cowpay_Admin_Settings $cp_admin_settings;

    function __construct()
    {
        $this->cp_admin_settings = Cowpay_Admin_Settings::getInstance();
    }

    /**
     * Output field name HTML
     *
     * Gateways which support tokenization do not require names - we don't want the data to post to the server.
     *
     * @param  string $name Field name.
     * @return string
     */
    public function field_name($name)
    {
        return $this->supports('tokenization') ? '' : ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    /**
     * This function should be called from the concrete class after all fields initialization.
     */
    public function init() //TODO: rename this function
    {
        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Save settings
        $this->save_admin_settings();

        // Lets check for SSL
        if ($this->require_ssl) add_action('admin_notices', array($this, 'do_ssl_check'));
    }

    public function handle_order_fail($response, $order)
    {
        // Transaction was not successful
        // Add notice to the cart
        wc_add_notice($response->status_description, 'error');
        // Add note to the order for your reference
        $order->add_order_note('Error:  Failure');
        //$customer_order->update_status("wc-cancelled",esc_html__('The order was failed','cowpay'));
        //echo "<pre>"; print_r($result);
    }

    /**
     * Calculate and return the merchant reference id for cowpay(cp)
     * from the given order.
     * Note: this function generates random uuids. two consecutive calls will generate
     * different reference id.
     */
    public function get_cp_merchant_reference_id(WC_Order $order)
    {
        return $order->get_order_number() . '-' . wp_generate_uuid4();
    }

    /**
     * Calculate and return the customer profile id for cowpay(cp)
     * from the given order
     */
    public function get_cp_customer_profile_id(WC_Order $order)
    {
        $billing_name = $order->get_billing_first_name();
        $guest_id = str_replace(" ", "-", "GUEST-$billing_name");
        $res = $order->get_user() ? $order->get_customer_id() : $guest_id;
        return strval($res); // id should be str
    }


    /**
     * Store cowpay related needed values to the customer order as meta. information granted from request and response.
     * These information is used to retrieve the order in the server notification.
     * @param WC_Order $order current processed order 
     * @param array $req_params req params, should contains merchant_reference_id
     * @param Object? $response object if exists
     * @return void
     */
    public function set_cowpay_meta($order, $req_params, $response = null)
    {
        //* if meta key starts with underscore '_' character, it will be private
        //* and will not be shown in the order information in the dashboard.

        $setOrderMeta = function ($k, $v) use ($order) {
            update_post_meta($order->get_id(), $k, $v);
            // don't use this right now, as it doesn't update in the database.
            //// $order->add_meta_data($k, $v);
        };
        $setOrderMeta("cp_merchant_reference_id", $req_params['merchant_reference_id']);
        $setOrderMeta("cp_customer_merchant_profile_id", $req_params['customer_merchant_profile_id']);

        if ($response == null) return;
        // response meta
        $setOrderMeta("cp_cowpay_reference_id", $response->cowpay_reference_id);
        $is_3ds = isset($response->three_d_secured) && $response->three_d_secured;
        $setOrderMeta("cp_is_3ds", $is_3ds);
        if (isset($response->payment_gateway_reference_id)) {
            $setOrderMeta("cp_payment_gateway_reference_id", $response->payment_gateway_reference_id);
        }
        //TODO: do_action('cowpay_meta_after_update')
    }

    /**
     * parse an error and returns readable helpful user message
     * @param WP_Error|Object $maybe_error object that may contains error
     * @return string[] user error messages
     */
    public function get_user_error_messages($maybe_error)
    {
        $return_messages = array();
        if (is_wp_error($maybe_error)) { // extract readable error messages from wp_error object
            $error_codes = $maybe_error->get_error_codes();
            foreach ($error_codes as $error_code) {
                if ($error_code == 'http_request_failed') {
                    // get more readable error instead of 'not valid url'
                    $return_messages[] = __("Sorry, we can't establish a valid connection with Cowpay servers");
                } else {
                    // get default description for other error codes
                    $return_messages[] = $maybe_error->get_error_message($error_code);
                }
            }
        } else if (
            isset($maybe_error->status_code)
            && isset($maybe_error->status_description)
            && $maybe_error->status_code != 200
        ) { // extract errors from the server response
            $errors = $maybe_error->errors;

            if (isset($errors)) {
                if (!is_array($errors))
                    $errors = get_object_vars($errors);
                $return_messages = array_values($errors);
            } else {
                // if we can't find detailed errors, return status description as the error
                $return_messages[] = $maybe_error->status_description;
            }
        } else if (!isset($maybe_error->status_code)) { // server should return it in the response
            $return_messages[] = esc_html__("Unexpected Cowpay response");
        }
        return $return_messages;
    }

    /**
     * Calculate and return the description field for passed order param cowpay(cp)
     * from the given order
     */
    public function get_cp_description(WC_Order $order)
    {
        /**
         * TODO: return valid desc information for this specific order
         * see https://www.businessbloomer.com/woocommerce-easily-get-order-info-total-items-etc-from-order-object/
         * see https://www.businessbloomer.com/woocommerce-easily-get-product-info-title-sku-desc-product-object/
         */
        // array_values($order->get_items())
        $order_items = $order->get_items();
        $info = array(
            $this->cp_admin_settings->get_description(), // admin description
            $order->get_payment_method_title(),
            $order_items[array_key_first($order_items)]->get_name() // name of first item in the order
        );
        return join(', ', $info); // TODO: apply filter
    }

    /**
     * Creates the request signature from dynamic params sent and admin settings (merchant code, merchant hash)
     */
    public function get_cp_signature($amount, $merchant_reference_id, $customer_profile_id)
    {
        $message = join("", array(
            $this->cp_admin_settings->get_merchant_code(),
            $merchant_reference_id,
            $customer_profile_id,
            $amount,
            $this->cp_admin_settings->get_merchant_hash()
        ));
        return hash('sha256', $message);
    }

    /**
     * Save our administration woocomenrce options. Since we are not going to be doing anything special
     * we have not defined 'process_admin_options' in this class, extended class can define process_admin_options
     * to do its options logic before save.
     */
    public function save_admin_settings()
    {
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
    }


    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>", 'cowpay'), $this->method_title, admin_url('admin.php?page=cowpay_setting')) . "</p></div>";
            }
        }
    }
}
