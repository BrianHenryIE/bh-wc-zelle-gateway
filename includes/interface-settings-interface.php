<?php
/**
 * The plugin's settings interface.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway;

interface Settings_Interface {

	/**
	 * Used by WC_Zelle_Gateway to set its plugin_id.
	 *
	 * @see WC_Settings_API::$plugin_id
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string;

	/**
	 * Used to add links on plugins.php.
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string;

	/**
	 * TODO: Returns true if the user has entered all the appropriate settings and checked enable.
	 *
	 * @return bool
	 */
	public function is_imap_reconcile_enabled(): bool;

	/**
	 * Returns the ids for all instances of Zelle gateway registered with WooCommerce.
	 *
	 * @return string[]
	 */
	public function get_payment_method_ids(): array;

	public function get_plugin_version(): string;
}
