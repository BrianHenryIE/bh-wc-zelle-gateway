<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package  brianhenryie/bh-wc-zelle-gateway
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Zelle_Gateway;

use BrianHenryIE\WC_Zelle_Gateway\API\API;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class BH_WC_Zelle_Gateway_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wc_zelle_gateway', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_zelle_gateway'] );
	}
}
