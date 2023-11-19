<?php
/**
 * Functions hooked on / altering WC_Order output for the gateway.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Includes\Cron;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Payment_Gateways;

class Order {

	use LoggerAwareTrait;

	public function __construct(
		protected Settings_Interface $settings,
		LoggerInterface $logger,
	) {
		$this->setLogger( $logger );
	}

	/**
	 * When an order is  created (set to on-hold), schedule to check for emails in five mintues.
	 *
	 * @hooked woocommerce_order_status_changed
	 *
	 * @param int    $order_id
	 * @param string $status_from
	 * @param string $status_to
	 */
	public function schedule_email_check( $order_id, $status_from, $status_to ): void {

		if ( ! $this->settings->is_imap_reconcile_enabled() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		$payment_method = $order->get_payment_method();

		if ( ! in_array( $payment_method, $this->settings->get_payment_method_ids() ) ) {
			return;
		}

		if ( 'on-hold' === $status_to ) {

			$timestamp = wp_next_scheduled( Cron::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
			if ( false !== $timestamp ) {
				wp_unschedule_event( $timestamp, Cron::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
			}

			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), Cron::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
		}
	}

	/**
	 * Displays the user's Zelle email address instead of their billing address.
	 *
	 * @hooked woocommerce_order_get_formatted_billing_address
	 * @see WC_Order::get_formatted_billing_address()
	 * @see WC_Order::get_address()
	 *
	 * @param string   $formatted_address
	 * @param string[] $raw_address The array of strings as returned from WC_Order::get_address().
	 * @param WC_Order $order The WooCommerce order.
	 * @return string
	 */
	public function admin_view_billing_address( string $formatted_address, array $raw_address, WC_Order $order ): string {

		global $pagenow, $plugin_page;

		$g = $GLOBALS;

		if (
			'admin.php' !== $pagenow
			|| 'wc-orders' !== $plugin_page
		) {
			return $formatted_address;
		}

		// TODO: On the Thank You page this fails.
		if ( ! is_admin() ) {
			return $formatted_address;
		}

		if ( ! ( $order instanceof WC_Order ) ) {
			return $formatted_address;
		}

		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();

		if ( ! isset( $payment_gateways[ $order->get_payment_method() ] ) ) {
			return $formatted_address;
		}

		$payment_method_instance = $payment_gateways[ $order->get_payment_method() ];

		if ( ! ( $payment_method_instance instanceof Zelle_Gateway ) ) {
			return $formatted_address;
		}

		$zelle_customer_account_name = $order->get_meta( Zelle_Gateway::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY );
		$address                     = '<strong>Zelle account:</strong> <br/>' . $zelle_customer_account_name;

		return $address;
	}
}
