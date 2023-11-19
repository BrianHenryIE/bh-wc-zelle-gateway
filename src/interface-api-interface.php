<?php
/**
 * The plugin's API interface.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway;

interface API_Interface {

	/**
	 * Initiate reconciling emails and orders for all gateways under this plugin.
	 */
	public function check_for_payment_emails(): void;
}
