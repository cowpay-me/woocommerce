<?php

/**
 * Singleton class that reads cowpay admin settings (merchant code, hash, ...)
 */
class Cowpay_Admin_Settings
{
    // Hold the class instance.
    private static $instance = null;
    private $settings;

    private static $settings_key = 'cowpay_settings';

    // The constructor is private
    // to prevent initiation with outer code.
    private function __construct()
    {
        $this->settings = get_option(self::$settings_key);
        $this->settings_key = 'cowpay_settings';
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Cowpay_Admin_Settings();
        }
        return self::$instance;
    }

    /**
     * Returns correct host depending on current activated environment in admin settings.
     * @deprecated this class shouldn't know about hosts urls
     */
    public function get_active_host()
    {
        $staging_host = "staging.cowpay.me";
        $production_host = "cowpay.me";
        return $this->get_environment() == 1 ? $production_host : $staging_host;
    }

    /**
     * Returns correct auth token depending on current activated environment in admin settings.
     */
    public function get_active_token()
    {
        return $this->get_environment() == 1 ? $this->get_auth_token() : $this->get_staging_auth_token();
    }


    /**
     * Get filtered value of configured environment in admin settings
     * staging or production.
     */
    public function get_environment()
    {
        $value = $this->settings['environment'];
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * Get sanitized **production** authorization token configured
     * in admin settings
     * @todo separate two fields in admin settings with different keys for staging and production
     */
    public function get_auth_token()
    {
        $value = $this->settings['YOUR_AUTHORIZATION_TOKEN'];
        return sanitize_text_field($value);
    }

    /**
     * Get sanitized **staging** authorization token configured
     * in admin settings
     * @todo separate two fields in admin settings with different keys for staging and production
     */
    public function get_staging_auth_token()
    {
        // TODO: should add another field in the future
        $value = $this->settings['YOUR_AUTHORIZATION_TOKEN'];
        return sanitize_text_field($value);
    }

    /**
     * Return configured callback url
     * @todo should be removed as this field should not be editable
     */
    public function get_callback_url()
    {
        $value = $this->settings['cowpay_callbackurl'];
        return esc_url_raw($value);
    }

    /**
     * Get sanitized value of merchant code configured in admin settings
     */
    public function get_merchant_code()
    {
        $value = $this->settings['YOUR_MERCHANT_CODE'];
        return sanitize_text_field($value);
    }

    /**
     * Get sanitized value of merchant hash configured in admin settings
     */
    public function get_merchant_hash()
    {
        $value = $this->settings['YOUR_MERCHANT_HASH'];
        return sanitize_text_field($value);
    }

    /**
     * Get sanitized value of description configured in admin settings
     */
    public function get_description()
    {
        $value =  $this->settings['description'];
        return sanitize_textarea_field($value);
    }

    /**
     * Get sanitized value of paid order status configured in admin settings
     * This value should be used when order status is PAID at Cowpay
     */
    public function get_order_status()
    {
        $value = $this->settings['order_status'];
        return sanitize_text_field($value);
    }
}
