<?php

/**
 * Bride from our plugin to WooCommerce
 */
class Woo_Bridge
{

    private WooCowpayLoader $loader;
    function __construct(WooCowpayLoader $loader)
    {
        $this->loader = $loader;
    }

    public function register()
    {
        $this->loader->add_filter("woocommerce_order_data_store_cpt_get_orders_query", $this, 'add_meta_query_support', 10, 2);
        $this->register_server_callback();
    }

    /**
     * Add payment classes to the scope
     */
    public function init_cowpay_gateways_classes()
    {
        require_once(__DIR__ . "/abstract/abstract-woo-payment-gateway-cowpay.php");
        require_once(__DIR__ . "/payments/class-woo-cowpay-pos-fawry.php");
        require_once(__DIR__ . "/payments/class-woo-cowpay-credit-card.php");
        // require_once(__DIR__ . "/payments/class-woo-cowpay-checkout.php");

        require_once(__DIR__ . "/rest_api/class-woo-cowpay-api.php");
    }


    /**
     * Register cowpay payment methods into WooCommerce
     * @param methods: current available methods
     */
    public function add_cowpay_gateways($methods)
    {
        /** @see WC_Payment_Gateway_Cowpay_POS_Fawry*/
        $methods[] = 'WC_Payment_Gateway_Cowpay_POS_Fawry';
        /** @see WC_Payment_Gateway_Cowpay_CC*/
        $methods[] = 'WC_Payment_Gateway_Cowpay_CC';
        /** @see WC_Payment_Gateway_Cowpay_Checkout*/
        // $methods[] = 'WC_Payment_Gateway_Cowpay_Checkout';
        return $methods;
    }

    /**
     * Add support for querying (finding) orders using custom cowpay meta keys
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function add_meta_query_support($query, $query_vars)
    {
        if (!empty($query_vars['cp_merchant_reference_id'])) {
            $query['meta_query'][] = array(
                'key' => 'cp_merchant_reference_id',
                'value' => esc_attr($query_vars['cp_merchant_reference_id']),
            );
        }
        if (!empty($query_vars['payment_gateway_reference_id'])) {
            $query['meta_query'][] = array(
                'key' => 'payment_gateway_reference_id',
                'value' => esc_attr($query_vars['payment_gateway_reference_id']),
            );
        }
        if (!empty($query_vars['cp_cowpay_reference_id'])) {
            $query['meta_query'][] = array(
                'key' => 'cp_cowpay_reference_id',
                'value' => esc_attr($query_vars['cp_cowpay_reference_id']),
            );
        }
        return $query;
    }

    /**
     * register server callbacks, webhooks from cowpay to this website
     */
    private function register_server_callback()
    {
        $server_callback = new Cowpay_Server_Callback();
        $this->loader->add_action("init", $server_callback, 'update_order');
    }
}
