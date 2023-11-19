<?php

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

class Payment_Gateways_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Let's run the function WooCommerce uses to poll for gateways and see is our gateway there.
	 *
	 * This is distinct to `WC()->payment_gateways()->get_available_payment_gateways()`.
	 */
	public function test_gateway_is_added() {

		$gateways = WC()->payment_gateways()->payment_gateways();

		$this->assertArrayHasKey( 'zelle', $gateways );

		$this->assertInstanceOf( Zelle_Gateway::class, $gateways['zelle'] );
	}
}
