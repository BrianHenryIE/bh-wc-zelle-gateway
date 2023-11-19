<?php
/**
 * Tests for the root plugin file.
 *
 * @package  brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway;

use BrianHenryIE\WC_Zelle_Gateway\API\API;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\BH_WC_Order_Email_Reconcile;
use BrianHenryIE\WC_Zelle_Gateway\WP_Logger\Logger;
use BrianHenryIE\WC_Zelle_Gateway\WP_Mailboxes\API\API as BH_WP_Mailboxes;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include(): void {

		\Patchwork\redefine(
			array( Logger::class, '__construct' ),
			function ( $settings ) {}
		);

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
				'times',
				1,
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php',
				'times',
				1,
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		\WP_Mock::passthruFunction( 'sanitize_key' );
		\WP_Mock::passthruFunction( 'sanitize_title' );

		ob_start();

		include $plugin_root_dir . '/bh-wc-zelle-gateway.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wc_zelle_gateway', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_zelle_gateway'] );
	}
}
