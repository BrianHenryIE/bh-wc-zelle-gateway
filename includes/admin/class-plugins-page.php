<?php
/**
 * The plugins.php page output of the plugin.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\Admin;

use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Zelle_Gateway;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

/**
 * This class adds a `Settings` link on the plugins.php page.
 */
class Plugins_Page {

	/**
	 * Adds 'Settings' link to the configuration under WooCommerce's payment gateway settings page.
	 * Adds 'Orders' link if Filter WooCommerce Orders by Payment Method plugin is installed.
	 *
	 * @hooked plugin_action_links_{plugin basename}
	 * @see \WP_Plugins_List_Table::display_rows()
	 *
	 * @param string[] $links_array The links that will be shown below the plugin name on plugins.php (usually "Deactivate").
	 *
	 * @return string[]
	 */
	public function action_links( $links_array ) {

		if ( ! class_exists( WC_Payment_Gateways::class ) ) {
			return $links_array;
		}

		$setting_link   = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zelle' );
		$plugin_links   = array();
		$plugin_links[] = '<a href="' . $setting_link . '">' . __( 'Settings', 'bh-wc-zelle-gateway' ) . '</a>';

		/**
		 * Add an "Orders" link to a filtered list of orders if the Filter WooCommerce Orders by Payment Method plugin is installed.
		 *
		 * @see https://www.skyverge.com/blog/filtering-woocommerce-orders/
		 */
		if ( is_plugin_active( 'wc-filter-orders-by-payment/filter-wc-orders-by-gateway.php' ) && class_exists( WC_Payment_Gateway::class ) ) {

			$params = array(
				'post_type'                  => 'shop_order',
				'_shop_order_payment_method' => 'zelle',
			);

			$orders_link    = add_query_arg( $params, admin_url( 'edit.php' ) );
			$plugin_links[] = '<a href="' . $orders_link . '">' . __( 'Orders', 'bh-wc-zelle-gateway' ) . '</a>';
		}

		return array_merge( $plugin_links, $links_array );
	}
}
