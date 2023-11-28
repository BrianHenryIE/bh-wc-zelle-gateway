<?php
/**
 * Fired during plugin deactivation
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\WP_Includes;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */
class Deactivator {

	/**
	 * Removes the cron job.
	 */
	public static function deactivate(): void {

		wp_clear_scheduled_hook( Cron::CHECK_FOR_PAYMENT_EMAILS_CRON_HOOK );
	}
}
