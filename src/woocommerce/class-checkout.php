<?php
/**
 * Swap in the Zelle color "Place Order" button when the gateway is selected.
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;

/**
 * @see wp-content/plugins/woocommerce/templates/checkout/payment.php
 *
 * Class Checkout_Payment
 * @package   brianhenryie/bh-wc-zelle-gateway
 */
class Checkout {

	public function __construct(
		protected Settings_Interface $settings,
	) {
	}

	/**
	 * Register the JavaScript files used at checkout.
	 *
	 * The JS changes the colour of the "Place Order" button to Zelle purple when the Zelle gateway is selected.
	 */
	public function enqueue_scripts(): void {

		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$js_url = plugins_url( 'assets/js/zelle-checkout.js', 'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php' );

		$version = $this->settings->get_plugin_version();

		wp_enqueue_script( 'zelle-checkout', $js_url, array( 'jquery', 'wc-checkout' ), $version, true );

		$image = plugins_url( 'assets/images/buy-now-with-zelle.png', 'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php' );

		$zelle_gateways = array_filter(
			\WC()->payment_gateways()->get_available_payment_gateways(),
			function ( $gateway ) {
				return $gateway instanceof Zelle_Gateway;
			}
		);

		$zelle_gateway_ids = array_keys( $zelle_gateways );

		$javascripts_params = json_encode(
			array(
				'gatewayIdsList' => $zelle_gateway_ids,
			)
		);

		wp_add_inline_script(
			'zelle-checkout',
			"var zelleGateway = {$javascripts_params};\n",
			'before'
		);
	}

	public function enqueue_styles(): void {

		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$css_url = plugins_url( 'assets/css/zelle-checkout.css', 'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php' );

		$version = $this->settings->get_plugin_version();

		wp_enqueue_style(
			'bh-wc-zelle-gateway',
			$css_url,
			array(),
			$version,
			'all'
		);
	}
}
