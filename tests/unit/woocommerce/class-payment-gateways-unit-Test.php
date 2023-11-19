<?php

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

class Payment_Gateways_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * All it needs to do is add the classname to WooCommerce's filter so it can be instantiated later.
	 */
	public function test_class_is_added_to_array() {

		$sut = new Payment_Gateways();

		$result = $sut->add_to_woocommerce( array() );

		$this->assertContains( 'BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Zelle_Gateway', $result );
	}
}
