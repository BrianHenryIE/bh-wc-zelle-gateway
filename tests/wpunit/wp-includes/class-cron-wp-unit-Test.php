<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package  brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WP_Includes;

use BrianHenryIE\WC_Zelle_Gateway\API_Interface;
use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;
use Psr\Log\NullLogger;
use Exception;

/**
 *
 * @see Cron
 */
class Cron_WP_Unit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Happy path, adds cron job if it doesn't already exist.
	 *
	 * @throws Exception
	 */
	public function test_schedule_cron() {

		$cron_name = '_check_for_payment_emails';

		$settings_mock = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'           => function () {
					return ''; },
				'get_plugin_version'        => 'a',
				'is_imap_reconcile_enabled' => true,
			)
		);

		$api_mock = $this->makeEmpty( API_Interface::class );

		$cron = new Cron( $api_mock, $settings_mock, new NullLogger() );

		assert( false === wp_next_scheduled( $cron_name ) );

		$cron->add_cron_jon();

		$this->assertNotFalse( wp_next_scheduled( Cron::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK ) );
	}


	/**
	 * Don't schedule a cron if settings say no.
	 *
	 * @throws Exception
	 */
	public function test_does_not_schedule_cron() {

		$cron_name = '_check_for_payment_emails';

		$settings_mock = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'       => '',
				'get_plugin_version'    => 'a',
				'enable_imap_reconcile' => false,
			)
		);

		$api_mock = $this->makeEmpty( API_Interface::class );

		$cron = new Cron( $api_mock, $settings_mock, new NullLogger() );

		assert( false === wp_next_scheduled( $cron_name ) );

		$cron->add_cron_jon();

		$this->assertFalse( wp_next_scheduled( $cron_name ) );
	}



	/**
	 * Remove existing cron if settings suggest to.
	 *
	 * @throws Exception
	 */
	public function test_delete_existing_cron() {

		$cron_name = 'bh_wc_zelle_gateway_check_for_payment_emails';

		wp_schedule_event( time(), 'hourly', $cron_name );

		$settings_mock = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'       => '',
				'get_plugin_version'    => 'a',
				'enable_imap_reconcile' => false,
			)
		);

		$api_mock = $this->makeEmpty( API_Interface::class );

		$cron = new Cron( $api_mock, $settings_mock, new NullLogger() );

		// Check is the test primed.
		assert( false !== wp_next_scheduled( $cron_name ) );

		$cron->add_cron_jon();

		$this->assertFalse( wp_next_scheduled( $cron_name ) );
	}
}
