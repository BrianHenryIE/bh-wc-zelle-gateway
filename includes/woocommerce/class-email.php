<?php

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

use WC_Order;
use WC_Payment_Gateways;

class Email {

	// TODO: Don't send order received email unless the order has not been paid in 60 minutes.

	/**
	 * Adds instructions to the order confirmation emails.
	 *
	 * This runs for every order, on every email (received, complete, etc.).
	 *
	 * @hooked woocommerce_email_before_order_table
	 *
	 * @param WC_Order $order
	 * @param bool     $sent_to_admin
	 * @param bool     $plain_text
	 */
	public function email_instructions( WC_Order $order, bool $sent_to_admin, bool $plain_text = false ): void {

		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();

		if ( ! isset( $payment_gateways[ $order->get_payment_method() ] ) ) {
			return;
		}

		$payment_gateway_instance = $payment_gateways[ $order->get_payment_method() ];

		if ( ! ( $payment_gateway_instance instanceof Zelle_Gateway ) ) {
			return;
		}

		// TODO: Duplicate code
		// @see Thank_You::print_instructions()

		$zelle_destination_email_address = $payment_gateway_instance->get_option( 'zelle_destination_email_address' );
		// $zelle_destination_account_name              = $payment_gateway_instance->get_option( 'zelle_destination_account_name' );

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$zelle_qr_link = 'https://enroll.zellepay.com/qr-codes?data=' .
						base64_encode(
							wp_json_encode(
								array(
									'token' => $zelle_destination_email_address,
									// 'name'   => $zelle_destination_account_name,
																															'action' => 'payment',
								)
							)
						);

		$instructions = "<p>Please send payment of {$order->get_formatted_order_total()} via Zelle to <a href=\"{$zelle_qr_link}\">{$zelle_destination_email_address}</a></p>";

		$instructions .= "<p>* Enter the order number – <b>{$order->get_id()}</b> – and nothing else in the order memo.</p>";

		$instructions .= "<p>* Please pay the precise amount – <b>{$order->get_formatted_order_total()}</b> – so the payment can be automatically matched to the order.";

		$instructions .= "<p><a href=\"{$zelle_qr_link}\">Open Zelle</a></p>";

		if ( ! $sent_to_admin && ( $order->has_status( 'on-hold' ) || $order->has_status( 'pending' ) ) ) {

			echo wptexturize( $instructions );
		}
	}
}
