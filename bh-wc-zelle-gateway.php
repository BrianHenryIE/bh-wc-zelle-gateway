<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://BrianHenryIE.com
 * @since             1.0.0
 * @package           brianhenryie/bh-wc-zelle-gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Zelle Gateway
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wc-zelle-gateway/
 * Description:       Adds Zelle as a payment option at checkout and reconciles WooCommerce orders through email receipts.
 * Version:           1.1.0
 * Requires PHP:      8.0
 * Author:            BrianHenryIE
 * Author URI:        http://BrianHenryIE.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-zelle-gateway
 * Domain Path:       /languages
 */

namespace BrianHenryIE\WC_Zelle_Gateway;

use BrianHenryIE\WC_Zelle_Gateway\Plugin_Meta_Kit\Plugin_Meta_Kit;
use BrianHenryIE\WC_Zelle_Gateway\API\API;
use BrianHenryIE\WC_Zelle_Gateway\API\Settings;
use BrianHenryIE\WC_Zelle_Gateway\lucatume\DI52\Container;
use BrianHenryIE\WC_Zelle_Gateway\Plugin_Meta_Kit\Plugin_Meta_Kit_Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\BH_WC_Order_Email_Reconcile;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\Email_Reconcile_Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Logger\Logger;
use BrianHenryIE\WC_Zelle_Gateway\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Includes\Deactivator;
use BrianHenryIE\WC_Zelle_Gateway\WP_SLSWC_Client\SLSWC_Client;
use Throwable;
use Psr\Log\LoggerInterface;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	return;
}

// If the GitHub repo was installed without running `composer install` to add the dependencies, the autoload will fail.
try {
	require_once __DIR__ . '/autoload.php';
} catch ( Throwable $error ) {
	// This only hides one error at a time.
	$display_download_from_releases_error_notice = function () use ( $error ) {
		echo '<div class="notice notice-error"><p><b>Zelle Gateway missing dependencies.</b> Please <a href="https://github.com/BrianHenryIE/bh-wc-zelle-gateway/releases">install the distribution archive from the GitHub Releases page</a>. It appears you downloaded the GitHub repo and installed that as the plugin.</p><p style="display: none">' . $error->getMessage() . '</p></div>';
	};
	add_action( 'admin_notices', $display_download_from_releases_error_notice );
	return;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BH_WC_ZELLE_GATEWAY_VERSION', '1.1.0' );

register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

$container = new Container();

$container->bind( API_Interface::class, API::class );
$container->bind( Settings_Interface::class, Settings::class );
$container->bind( Email_Reconcile_Settings_Interface::class, Settings::class );
$container->bind( Logger_Settings_Interface::class, Settings::class );
// BH WP Logger doesn't add its own hooks unless we use its singleton.
$container->singleton(
	LoggerInterface::class,
	static function ( Container $container ) {
		return Logger::instance( $container->get( Logger_Settings_Interface::class ) );
	}
);
// BH_WC_Order_Email_Reconcile uses its own Container, so we can't autowire it or the wrong container is used.
$container->singleton(
	BH_WC_Order_Email_Reconcile::class,
	static function ( Container $container ) {
		return BH_WC_Order_Email_Reconcile::instance(
			$container->get( Email_Reconcile_Settings_Interface::class ),
			$container->get( LoggerInterface::class )
		);
	}
);

$app = $container->get( BH_WC_Zelle_Gateway::class );

$GLOBALS['bh_wc_zelle_gateway'] = $container->get( API_Interface::class );

add_action(
	'plugins_loaded',
	function () use ( $container ) {
		$settings = new \BrianHenryIE\WC_Zelle_Gateway\WP_SLSWC_Client\Settings(
			'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php',
			'http://localhost:8080/bh-wp-autologin-urls'
		);
		$logger   = $container->get( LoggerInterface::class );

		SLSWC_Client::get_instance( $settings, $logger );
	}
);

/**
 * @hooked admin_enqueue_scripts
 */
function example_admin_enqueue_scripts() {
	$plugin_slug = 'bh-wc-zelle-gateway';

	$script_handle = "{$plugin_slug}-licence";

	// Only load the JS on the plugin information modal for this plugin.
	global $pagenow;
	if ( 'plugin-install.php' !== $pagenow
		|| ! isset( $_GET['plugin'] )
		|| sanitize_key( wp_unslash( $_GET['plugin'] ) !== $plugin_slug )
	) {
		return;
	}

	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	wp_enqueue_script(
		$script_handle,
		plugins_url( 'build/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	$api = SLSWC_Client::get_instance();

	$data = wp_json_encode(
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'BrianHenryIE\WP_SLSWC_Client\Admin\AJAX' ), // TODO: use ::class.
			'licence_details' => $api->get_licence_details(),
		)
	);

	// `bh-wc-zelle-gateway-licence` -> `bhWcZelleGatewayLicence`;
	$script_var_name = lcfirst( str_replace( ' ', '', ucwords( str_replace( '-', ' ', $script_handle ) ) ) );

	wp_add_inline_script(
		$script_handle,
		"const {$script_var_name} = {$data};",
		'before'
	);
}
add_action( 'admin_enqueue_scripts', '\BrianHenryIE\WC_Zelle_Gateway\example_admin_enqueue_scripts' );

$container->bind( Plugin_Meta_Kit_Settings_Interface::class, Settings::class );
$pmk = $container->get( Plugin_Meta_Kit::class );
$pmk->view_details_modal();
