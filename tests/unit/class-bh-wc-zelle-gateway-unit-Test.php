<?php
/**
 * Verify things are hooked up.
 */

namespace BrianHenryIE\WC_Zelle_Gateway;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Zelle_Gateway\API\Settings;
use BrianHenryIE\WC_Zelle_Gateway\lucatume\DI52\Container;
use BrianHenryIE\WC_Zelle_Gateway\Psr\Container\ContainerInterface;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Order;
use Psr\Log\LoggerInterface;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Zelle_Gateway\BH_WC_Zelle_Gateway
 */
class BH_WC_Zelle_Gateway_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}
	protected function get_container(): ContainerInterface {

		$container = new Container();

		$container->bind(
			API_Interface::class,
			function () {
				return self::makeEmpty( API_Interface::class );
			}
		);
		$container->bind( Settings_Interface::class, Settings::class );
		$container->bind( LoggerInterface::class, ColorLogger::class );

		return $container;
	}

	/**
	 * @covers ::define_woocommerce_order_hooks
	 */
	public function test_woocommerce_order_hooks() {

		\WP_Mock::expectActionAdded(
			'woocommerce_order_status_changed',
			array( new AnyInstance( Order::class ), 'schedule_email_check' ),
			10,
			3
		);

		new BH_WC_Zelle_Gateway( $this->get_container() );
	}
}
