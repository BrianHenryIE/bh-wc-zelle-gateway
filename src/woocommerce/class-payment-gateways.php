<?php
/**
 * Add the payment gateway to WooCommerce's list of gateways.
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

use WC_Order;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

/**
 * Add the payment gateway's class name to WooCommerce's list of gateways it will
 * later instantiate.
 */
class Payment_Gateways {

	/**
	 * Add the Gateway to WooCommerce.
	 *
	 * @hooked woocommerce_payment_gateways
	 * @see WC_Payment_Gateways::init()
	 *
	 * @param string[] $gateways The payment gateways registered with WooCommerce.
	 *
	 * @return string[]
	 **/
	public function add_to_woocommerce( array $gateways ): array {

		$gateways[] = Zelle_Gateway::class;

		return $gateways;
	}

	/**
	 * Add the Zelle username to the admin ui gateway title, for the case of multiple instances.
	 *
	 * Applies on admin.php?page=wc-settings&tab=checkout Method column.
	 *
	 * @hooked woocommerce_gateway_method_title
	 *
	 * @param string             $method_title
	 * @param WC_Payment_Gateway $payment_gateway
	 */
	public function format_admin_gateway_name( string $method_title, WC_Payment_Gateway $payment_gateway ): string {

		if ( ! ( $payment_gateway instanceof Zelle_Gateway ) ) {
			return $method_title;
		}

		$username = $payment_gateway->get_option( 'zelle_destination_email_address' );

		if ( empty( $username ) ) {
			return $method_title;
		}

		if ( isset( $_GET['tab'] ) && 'checkout' === $_GET['tab'] && ! isset( $_GET['section'] ) ) {
			return "{$method_title} – <i>{$username}</i>";
		} else {
			return "{$method_title} – {$username}";
		}
	}


	/**
	 * On the admin side, add the destination Zelle username where appropriate.
	 *
	 * @hooked woocommerce_order_get_payment_method_title
	 *
	 * @see WC_Admin_List_Table_Orders::render_order_total_column()
	 *
	 * @param string   $value
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function format_method_title( string $value, $order ): string {

		if ( 'Zelle' !== $value || ! is_admin() ) {
			return $value;
		}

		$destination_account_username = $order->get_meta( Zelle_Gateway::DESTINATION_ZELLE_EMAIL_ADDRESS_META_KEY );

		if ( empty( $destination_account_username ) ) {
			return $value;
		}

		// Returns "Prompts the customer for their Zelle username and tells them to send to: my@zelle.account".
		return "{$value}: {$destination_account_username}";
	}
}
