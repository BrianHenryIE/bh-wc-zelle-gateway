<?php
/**
 * Cron job should be deleted when the plugin is deactivated.
 *
 * @package brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WP_Includes;

class Deactivator_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	public function test_check_cron_job_is_deleted() {

		\WP_Mock::userFunction(
			'wp_clear_scheduled_hook',
			array(
				'args'  => array( 'bh_wc_zelle_gateway_check_for_payment_emails' ),
				'times' => 1,
			)
		);

		Deactivator::deactivate();
	}
}
