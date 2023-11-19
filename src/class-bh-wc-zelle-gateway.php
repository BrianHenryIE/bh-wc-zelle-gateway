<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway;

use BrianHenryIE\WC_Zelle_Gateway\Admin\Plugins_Page;
use BrianHenryIE\WC_Zelle_Gateway\lucatume\DI52\Container;
use BrianHenryIE\WC_Zelle_Gateway\Psr\Container\ContainerInterface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Includes\I18n;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Checkout;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Email;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Order;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Payment_Gateways;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Thank_You;
use Psr\Log\LoggerInterface;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * frontend-facing site hooks.
 */
class BH_WC_Zelle_Gateway {
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @param ContainerInterface $container The DI container.
	 */
	public function __construct( protected ContainerInterface $container ) {
		$this->set_locale();
		$this->define_admin_hooks();

		$this->define_woocommerce_hooks();
		$this->define_woocommerce_order_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 */
	protected function set_locale(): void {
		/** @var I18n $plugin_i18n */
		$plugin_i18n = $this->container->get( I18n::class );

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	protected function define_admin_hooks(): void {
		/** @var Plugins_Page $plugins_page */
		$plugins_page = $this->container->get( Plugins_Page::class );

		$settings        = $this->container->get( Settings_Interface::class );
		$plugin_basename = $settings->get_plugin_basename();
		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'action_links' ) );
	}

	/**
	 * Register the payment gateway and customise the UI.
	 */
	protected function define_woocommerce_hooks(): void {
		/** @var Payment_Gateways $payment_gateways */
		$payment_gateways = $this->container->get( Payment_Gateways::class );

		// Register the payment gateway with WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $payment_gateways, 'add_to_woocommerce' ) );
		// In admin UI, show the username associated with the gateway.
		add_filter( 'woocommerce_gateway_method_title', array( $payment_gateways, 'format_admin_gateway_name' ), 10, 2 );
		add_filter( 'woocommerce_order_get_payment_method_title', array( $payment_gateways, 'format_method_title' ), 10, 2 );

		/** @var Thank_You $thank_you */
		$thank_you = $this->container->get( Thank_You::class );
		// Display payment instructions on thank you page.
		add_action( 'woocommerce_thankyou', array( $thank_you, 'print_instructions' ), 1 );

		/** @var Email $email */
		$email = $this->container->get( Email::class );
		// Add instructions to the customer emails.
		add_action( 'woocommerce_email_before_order_table', array( $email, 'email_instructions' ), 10, 2 );
	}

	protected function define_woocommerce_order_hooks(): void {
		/** @var Order $admin_order_page */
		$admin_order_page = $this->container->get( Order::class );
		// On admin order screen, show the Zelle username in place of the billing address.
		add_filter( 'woocommerce_order_get_formatted_billing_address', array( $admin_order_page, 'admin_view_billing_address' ), 10, 3 );
		add_action( 'woocommerce_order_status_changed', array( $admin_order_page, 'schedule_email_check' ), 10, 3 );

		/** @var Checkout $checkout */
		$checkout = $this->container->get( Checkout::class );
		add_action( 'wp_enqueue_scripts', array( $checkout, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $checkout, 'enqueue_styles' ) );
	}
}
