<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WP_Includes;

use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;


class Cron {
	use LoggerAwareTrait;

	const CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK = 'bh_wc_zelle_gateway_check_for_payment_emails';

	/**
	 * Cron_Jobs constructor.
	 *
	 * @param API_Interface      $api
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct(
		protected API_Interface $api,
		protected Settings_Interface $settings,
		LoggerInterface $logger
	) {
		$this->logger = $logger;
	}

	/**
	 * Schedules or deletes the cron as per the settings.
	 *
	 * @hooked plugins_loaded
	 */
	public function add_cron_jon(): void {

		if ( $this->settings->is_imap_reconcile_enabled() ) {

			if ( ! wp_next_scheduled( self::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK ) ) {
				wp_schedule_event( time(), 'hourly', self::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
				$this->logger->notice( 'Cron job scheduled' );
			}
		} else {
			$timestamp = wp_next_scheduled( self::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
			if ( false !== $timestamp ) {
				wp_unschedule_event( $timestamp, self::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
				$this->logger->notice( 'Cron job removed' );
			}
		}
	}

	/**
	 * @hooked self::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK
	 */
	public function check_for_payment_emails(): void {

		$this->api->check_for_payment_emails();
	}
}
