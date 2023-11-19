<?php

namespace BrianHenryIE\WC_Zelle_Gateway\WP_Includes;

use BrianHenryIE\WC_Zelle_Gateway\API_Interface;
use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;

use Psr\Log\NullLogger;

class Cron_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * Check when the cron's check_for_payment_emails function is called, i.e.
	 * by Cron, that it calls API's check_for_payment_emails function.
	 */
	public function test_check_for_payment_emails_calls_api() {

		$settings_mock = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'    => '',
				'get_plugin_version' => 'a',
			)
		);

		$api_mock = $this->makeEmpty(
			API_Interface::class,
			array( 'check_for_payment_emails' => \Codeception\Stub\Expected::once() )
		);

		$cron = new Cron( $api_mock, $settings_mock, new NullLogger() );

		$cron->check_for_payment_emails();
	}
}
