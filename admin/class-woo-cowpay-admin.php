<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WooCowpay
 * @subpackage WooCowpay/admin Woocomerce
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCowpay
 * @subpackage WooCowpay/admin
 * @author     Your Name <email@example.com> //TODO
 */
class WooCowpayAdmin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_admin_page_setting()
	{
		// add Cowpay to main admin sidebar
		add_menu_page(
			esc_html__('Cowpay Setting', 'cowpay'), // page title
			esc_html__('Cowpay Setting', 'cowpay'), // this menu title
			'manage_options', // capability, current user role should have this permission
			'cowpay_setting', // menu slug, used as url param by wordpress
			array($this, 'render_cowpay_setting'), // callback when clicked, we render the settings page.
			'dashicons-cart' // menu icon
		);
		add_submenu_page(
			'cowpay_setting', // parent slug, this should match menu slug in the above line
			esc_html__('Cowpay Setting', 'cowpay'), // page title
			esc_html__('Cowpay Setting', 'cowpay'), // this sub menu title
			'manage_options', // capability, current user role should have this permission
			'cowpay_setting', // menu slug, used as url param by wordpress
			array($this, 'render_cowpay_setting') // callback when clicked, we render the settings page.
		);
		add_submenu_page(
			'cowpay_setting', // parent slug, this should match menu slug in the above line
			esc_html__('Credit Card', 'cowpay'),  // page title
			esc_html__('Credit Card', 'cowpay'), // this sub menu title
			'manage_options', // capability, current user role should have this permission
			'cowpay_credit', // menu slug, used as url param by wordpress
			array($this, 'render_credit_card_settings') // callback when clicked, we redirect to WooCommerce settings
		);
		add_submenu_page(
			'cowpay_setting', // parent slug, this should match menu slug in the above line
			esc_html__('Pay at Fawry', 'cowpay'),  // page title
			esc_html__('Pay at Fawry', 'cowpay'), // this sub menu title
			'manage_options', // capability, current user role should have this permission
			'cowpay_fawry', // menu slug, used as url param by wordpress
			array($this, 'render_fawry_settings') // callback when clicked, we redirect to WooCommerce settings
		);
	}

	function render_cowpay_setting()
	{
		woo_cowpay_view('admin-settings');
	}

	function render_fawry_settings()
	{
		// just redirect to fawry WooCommerce tab.
		//* section value should match the id of the payment method
		wp_safe_redirect(admin_url("admin.php?page=wc-settings&tab=checkout&section=cowpay_payat_fawry"));
		die();
	}
	function render_credit_card_settings()
	{
		// just redirect to credit card WooCommerce tab.
		//* section value should match the id of the payment method
		wp_safe_redirect(admin_url("admin.php?page=wc-settings&tab=checkout&section=cowpay_credit_card"));
		die();
	}

	function init_settings()
	{
		$defaultOptionValues = array(
			'YOUR_MERCHANT_CODE' => "",
			'YOUR_MERCHANT_HASH' => "",
			'YOUR_AUTHORIZATION_TOKEN' => "",
			'description' => "No Description",
			'cowpay_callbackurl' => add_query_arg('action', 'cowpay', home_url('/')),
			'environment' => 2,
			'order_status' => "wc-processing",
		);
		register_setting('cowpay', 'cowpay_settings', array(
			'type' => 'array',
			'default' => $defaultOptionValues
		));
		add_settings_section('cowpay_section_main', esc_html__('Cowpay Setting', 'cowpay'), '', 'cowpay');
		add_settings_field(
			'YOUR_MERCHANT_CODE', // id
			esc_html__('Merchant Code', 'cowpay'), // title
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-merchant-code', array("options" => $options));
			}, // callback
			'cowpay', // The slug-name of the settings page on which to show the section.
			'cowpay_section_main' // settings section id
		);
		add_settings_field(
			'YOUR_MERCHANT_HASH',
			esc_html__('Merchant Hash', 'cowpay'),
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-merchant-hash', array("options" => $options));
			}, // callback
			'cowpay',
			'cowpay_section_main'
		);
		add_settings_field(
			'YOUR_AUTHORIZATION_TOKEN',
			esc_html__('Authorization Token', 'cowpay'),
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-auth-token', array("options" => $options));
			}, // callback
			'cowpay',
			'cowpay_section_main'
		);
		add_settings_field(
			'description',
			esc_html__('Description', 'cowpay'),
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-description', array("options" => $options));
			}, // callback
			'cowpay',
			'cowpay_section_main'
		);
		add_settings_field(
			'cowpay_callbackurl',
			esc_html__('Callback URL', 'cowpay'),
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-callback-url', array("options" => $options));
			}, // callback
			'cowpay',
			'cowpay_section_main'
		);
		add_settings_field(
			'order_status',
			esc_html__('Order Status', 'cowpay'),
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-order-status', array("options" => $options));
			}, // callback
			'cowpay',
			'cowpay_section_main'
		);
		add_settings_field(
			'environment',
			esc_html__('Environment', 'cowpay'),
			function () {
				$options = get_option('cowpay_settings');
				woo_cowpay_view('field-environment', array("options" => $options));
			}, // callback
			'cowpay',
			'cowpay_section_main',
			array('value' => 1)
		);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WooCowpayLoader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WooCowpayLoader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-cowpay-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WooCowpayLoader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WooCowpayLoader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-cowpay-admin.js', array('jquery'), $this->version, false);
	}
}
