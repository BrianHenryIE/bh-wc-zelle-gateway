<?php
/**
 * Instructions shown on the Thank You page immediately after the order is created.
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

use BrianHenryIE\WC_Zelle_Gateway\chillerlan\QRCode\QRCode;
use WC_Payment_Gateways;

class Thank_You {

	/**
	 * Prints the HTML on the Thank You (post checkout) page.
	 *
	 * @hooked woocommerce_thankyou
	 *
	 * @param int $order_id WooCommerce order id.
	 */
	public function print_instructions( int $order_id ): void {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();

		if ( ! isset( $payment_gateways[ $order->get_payment_method() ] ) ) {
			return;
		}

		$payment_gateway_instance = $payment_gateways[ $order->get_payment_method() ];

		if ( ! ( $payment_gateway_instance instanceof Zelle_Gateway ) ) {
			return;
		}

		$zelle_destination_email_address = $payment_gateway_instance->get_option( 'zelle_destination_email_address' );
		// $zelle_destination_account_name  = $payment_gateway_instance->get_option( 'zelle_destination_account_name' );

		$zelle_qr_link = 'https://enroll.zellepay.com/qr-codes?data=' .
							base64_encode(
								json_encode(
									array(
										'token' => $zelle_destination_email_address,
										// 'name'   => $zelle_destination_account_name,
																																		'action' => 'payment',
									)
								)
							);

		// Your order has been received.

		// TODO: Duplicate code.
		// @see Email::email_instructions()

		$instructions = "<p>Please send payment of {$order->get_formatted_order_total()} via Zelle to <a href=\"{$zelle_qr_link}\">{$zelle_destination_email_address}</a></p>";

		$instructions .= "<p>* Enter the order number – <b>{$order->get_id()}</b> – and nothing else in the payment memo.</p>";
		$instructions .= "<p>* Please pay the precise amount – <b>{$order->get_formatted_order_total()}</b> – so the payment can be automatically matched to the order.";

		// $instructions .= "<p><a href=\"{$zelle_qr_link}\">Open Zelle</a></p>";

		// @see https://github.com/bailsafe/zelle-qr-creator/blob/main/zelle.ps1

		// From the Chase app:
		// https://enroll.zellepay.com/qr-codes?data=ewogICJ0b2tlbiIgOiAiYnJpYW5oZW5yeWllQGdtYWlsLmNvbSIsCiAgIm5hbWUiIDogIkJSSUFOIiwKICAiYWN0aW9uIiA6ICJwYXltZW50Igp9
		// {
		// "token" : "brianhenryie@gmail.com",
		// "name" : "BRIAN",
		// "action" : "payment"
		// }

		// updated to change just the name to test
		// {
		// "token" : "brianhenryie@gmail.com",
		// "name" : "BRIANHENRY",
		// "action" : "payment"
		// }
		// https://enroll.zellepay.com/qr-codes?data=ewogICJ0b2tlbiIgOiAiYnJpYW5oZW5yeWllQGdtYWlsLmNvbSIsCiAgIm5hbWUiIDogIkJSSUFOSEVOUlkiLAogICJhY3Rpb24iIDogInBheW1lbnQiCn0=
		$instructions .= '<p>';
		$instructions .= "Scan this QR code using your <b>banking app</b>'s Zelle screen (<i>not</i> your phone's camera).";
		$instructions .= "<a href=\"{$zelle_qr_link}\">";
		$instructions .= sprintf(
			'<img src="%s" alt="%s" />',
			esc_attr( ( new QRCode() )->render( $zelle_qr_link ) ),
			esc_attr( 'Payment QR Code', 'bh-wc-zelle-gateway' )
		);
		$instructions .= '</a></p>';

		echo wp_kses(
			$instructions,
			array(
				'p'   => array(),
				'a'   => array(
					'href'   => array(),
					'target' => array(),
				),
				'b'   => array(),
				'i'   => array(),
				'img' => array(
					'src' => array(),
					'alt' => array(),
				),
			),
			array_merge( wp_allowed_protocols(), array( 'data' ) ),
		);
	}
}
