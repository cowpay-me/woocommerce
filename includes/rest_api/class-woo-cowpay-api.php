<?php

/**
 * Cowpay API interface for all requests.
 * Only this class knows how to call cowpay servers to make payments.
 * Singleton class
 */
class WC_Gateway_Cowpay_API_Handler
{
    private static ?WC_Gateway_Cowpay_API_Handler $instance = null;
    private Cowpay_Admin_Settings $settings;

    protected static $production_host = 'cowpay.me';
    protected static $staging_host = 'staging.cowpay.me';
    // protected static $staging_host = 'dev.cowpay.me';
    protected static $endpoint_charge_fawry = 'api/v1/charge/fawry';
    protected static $endpoint_charge_cc = 'api/v2/charge/card/init';
    protected static $endpoint_charge_cash_collection = 'api/v1/charge/cash-collection';
    protected static $endpoint_load_iframe_token = 'api/v1/iframe/token';
    protected static $endpoint_checkout_url = 'api/v1/iframe/load';

    public $notify_url;

    public static function get_instance()
    {
        if (self::$instance == null) {
            self::$instance = new WC_Gateway_Cowpay_API_Handler();
        }
        return self::$instance;
    }
    private function __construct()
    {
        $this->settings = Cowpay_Admin_Settings::getInstance();
    }

    /**
     * Make fawry charge request
     * @param array $fawry_params fawry request params to send
     * @link https://docs.cowpay.me/api/fawry
     */
    public function charge_fawry($fawry_params)
    {
        $url = $this->make_url(self::$endpoint_charge_fawry);
        $auth_token = esc_html($this->settings->get_active_token());
        $raw_response = wp_safe_remote_post($url, array(
            'body' => json_encode($fawry_params),
            'httpversion' => "1.1",
            // 'timeout' => 15.0, // default is 5.0 seconds
            'headers' => array(
                "Accept" => "*/*",
                "Authorization" => "Bearer $auth_token",
                "cache-control" => "no-cache",
                "content-type" => "application/json",
            ),
        ));
        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['body'])) {
            return new WP_Error('cowpay_api_empty_response', __('Server Error, empty response'));
        }
        $objResponse = json_decode($raw_response['body']);
        if ($objResponse->status_code == 200) return $objResponse;
        // 400+ status code
        //? should we return WP_Error;
        return $objResponse; // return response with errors key for now;
    }

    /**
     * Make card charge request
     * @param array $cc_params credit card request params to send
     * @link https://docs.cowpay.me/api/card
     */
    public function charge_cc($cc_params)
    {
        $url = $this->make_url(self::$endpoint_charge_cc);
        $auth_token = esc_html($this->settings->get_active_token());
        $raw_response = wp_safe_remote_post($url, array(
            'body' => json_encode($cc_params),
            'httpversion' => "2.00",
            'headers' => array(
                "Accept" => "application/json",
                "Authorization" => "Bearer $auth_token",
                "cache-control" => "no-cache",
                "content-type" => "application/json",
            ),
        ));
        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['body'])) {
            return new WP_Error('cowpay_api_empty_response', __('Server Error, empty response'));
        }
        $objResponse = json_decode($raw_response['body']);
        if ($objResponse->status_code == 200) return $objResponse;
        // 400+ status code
        //? should we return WP_Error;
        return $objResponse; // return response with errors key for now;
    }

    /**
     * Make card charge request
     * @param array $cc_params credit card request params to send
     * @link https://docs.cowpay.me/api/card
     */
    public function charge_cash_collection($cash_collection_params)
    {
        $url = $this->make_url(self::$endpoint_charge_cash_collection);
        $auth_token = esc_html($this->settings->get_active_token());
        $raw_response = wp_safe_remote_post($url, array(
            'body' => json_encode($cash_collection_params),
            'httpversion' => "2.00",
            'headers' => array(
                "Accept" => "application/json",
                "Authorization" => "Bearer $auth_token",
                "cache-control" => "no-cache",
                "content-type" => "application/json",
            ),
        ));
        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['body'])) {
            return new WP_Error('cowpay_api_empty_response', __('Server Error, empty response'));
        }
        $objResponse = json_decode($raw_response['body']);
        if ($objResponse->status_code == 200) return $objResponse;
        // 400+ status code
        //? should we return WP_Error;
        return $objResponse; // return response with errors key for now;
    }

    /**
     * Make card charge request
     * @param array $token_params token request params to send
     * @link https://docs.cowpay.me/api/iframe
     */
    public function load_iframe_token($token_params)
    {
        $url = $this->make_url(self::$endpoint_load_iframe_token);
        $auth_token = esc_html($this->settings->get_active_token());
        $raw_response = wp_safe_remote_post($url, array(
            'body' => json_encode($token_params),
            'httpversion' => "1.1",
            'headers' => array(
                "Accept" => "*/*",
                "Authorization" => "Bearer $auth_token",
                "cache-control" => "no-cache",
                "content-type" => "application/json",
            ),
        ));
        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['body'])) {
            return new WP_Error('cowpay_api_empty_response', __('Server Error, empty response'));
        }
        $objResponse = json_decode($raw_response['body']);
        if ($objResponse->status_code == 200) return $objResponse;
        // 400+ status code
        //? should we return WP_Error;
        return $objResponse; // return response with errors key for now;
    }

    /**
     * Returns checkout url of cowpay servers
     * @param string $token token generated from load_iframe_token.
     * @param string $referer_url when checkout process is done, cowpay redirect user to this url
     */
    public function get_checkout_url($token, $referer_url)
    {
        return $this->make_url(self::$endpoint_checkout_url . "/$token?referer=$referer_url");
    }

    /**
     * Returns cowpay host depending on selected environment in admin settings
     */
    public function get_active_host()
    {
        return $this->settings->get_environment() == 1 ? self::$production_host : self::$staging_host;
    }

    /**
     * return url from endpoint path, take into
     * account http/https and production/staging selection
     */
    protected function make_url($path)
    {
        $host = $this->get_active_host();
        $schema = is_ssl() ? "https" : "http";
        $url = "$schema://$host/$path";
        return esc_url_raw($url);
    }
}
