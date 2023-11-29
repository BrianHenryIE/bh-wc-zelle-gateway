<?php
/**
 * The plugin's settings.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\API;

use BrianHenryIE\WC_Zelle_Gateway\Plugin_Meta_Kit\Plugin_Meta_Kit_Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Logger\WooCommerce_Logger_Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Mailboxes\API\Ddeboer_Imap\IMAP_Credentials_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\Email_Reconcile_Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Mailboxes\Account_Credentials_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WP_Mailboxes\BH_WP_Mailboxes_Settings_Defaults_Trait;
use BrianHenryIE\WC_Zelle_Gateway\WP_Mailboxes\Mailbox_Settings_Defaults_Trait;
use BrianHenryIE\WC_Zelle_Gateway\WP_Mailboxes\Mailbox_Settings_Interface;
use Psr\Log\LogLevel;
use BrianHenryIE\WC_Zelle_Gateway\WooCommerce\Zelle_Gateway;
use WC_Payment_Gateways;


class Settings implements Settings_Interface,
	WooCommerce_Logger_Settings_Interface,
	Email_Reconcile_Settings_Interface,
	Plugin_Meta_Kit_Settings_Interface {

	use BH_WP_Mailboxes_Settings_Defaults_Trait;

	/**
	 *
	 * @see Logger_Settings_Interface
	 * @see IMAP_Reconcile_Settings_Interface
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'bh-wc-zelle-gateway';
	}

	/**
	 * TODO: Add to WooCommerce settings. Debug is bad.
	 *
	 * @return string
	 */
	public function get_log_level(): string {
		return LogLevel::DEBUG;
	}

	/**
	 * This bool determines if the cron job is created (if absent) or deleted (if present).
	 *
	 * TODO: use the actual settings! (validate...)
	 * TODO: add filter.
	 *
	 * @return bool
	 */
	public function is_imap_reconcile_enabled(): bool {

		// if( empty(  $mailbox->get_email_account_username() )
		// || empty ($mailbox->get_email_account_password() )
		// || empty ( $mailbox->get_email_imap_server() ) ) {
		// continue;
		// }

		return true;
	}

	/**
	 * I don't think this is in use. Initially it was for CSS/JS versioning.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return '1.1.0';
	}

	/**
	 *
	 *
	 * @return string[]
	 */
	public function get_payment_method_ids(): array {

		// TODO: Can this be run before woocommerce_loaded?
		// If not?... cache it.
		// Print a warning in the logs.

		if ( class_exists( WC_Payment_Gateways::class ) ) {
			$gateway_subclasses = array();
			$payment_gateways   = WC_Payment_Gateways::instance()->payment_gateways();

			foreach ( $payment_gateways as $payment_gateway_instance ) {

				if ( $payment_gateway_instance instanceof Zelle_Gateway ) {

					$gateway_subclasses[] = $payment_gateway_instance->id;

				}
			}

			return $gateway_subclasses;
		} else {
			return array( 'zelle' );
		}
	}


	/**
	 * Helper function to return settings saved by WooCommerce.
	 *
	 * @param string $setting
	 * @return mixed
	 */
	protected function get_woo_settings( $gateway_id, string $setting ) {

		$settings_id = "bh-wc-zelle-gateway_{$gateway_id}_settings";

		$woo_settings = get_option( $settings_id, array() );

		if ( isset( $woo_settings[ $setting ] ) ) {
			return $woo_settings[ $setting ];
		}

		return false;
	}


	/**
	 * The settings for the mailboxes to be checked.
	 *
	 * @return Mailbox_Settings_Interface[]
	 */
	public function get_configured_mailbox_settings(): array {

		$mailboxes = array();
		foreach ( $this->get_payment_method_ids() as $gateway_id ) {

			$email_imap_server      = $this->get_woo_settings( $gateway_id, 'email_server' );
			$email_account_username = $this->get_woo_settings( $gateway_id, 'email_username' );
			$email_account_password = $this->get_woo_settings( $gateway_id, 'email_password' );

			if ( empty( $email_imap_server ) || empty( $email_account_username ) || empty( $email_account_password ) ) {
				continue;
			}

			$action = $this->get_woo_settings( $gateway_id, 'after_reconcile_email_action' );

			$mailboxes[] = new class( $gateway_id, $email_imap_server, $email_account_username, $email_account_password, $action ) implements Mailbox_Settings_Interface {
				use Mailbox_Settings_Defaults_Trait;

				protected string $gateway_id;

				protected Account_Credentials_Interface $credentials;

				protected string $action;

				public function __construct( string $gateway_id, $email_imap_server, $email_account_username, $email_account_password, $action ) {
					$this->gateway_id = $gateway_id;
					$this->action     = $action;

					$imap_credentials = new class($email_imap_server, $email_account_username, $email_account_password) implements IMAP_Credentials_Interface {

						protected string $email_imap_server;
						protected string $email_account_username;
						protected string $email_account_password;

						public function __construct( $email_imap_server, $email_account_username, $email_account_password ) {
							$this->email_imap_server      = $email_imap_server;
							$this->email_account_username = $email_account_username;
							$this->email_account_password = $email_account_password;
						}

						public function get_email_imap_server(): string {
							return $this->email_imap_server;
						}

						public function get_email_account_username(): string {
							return $this->email_account_username;
						}

						public function get_email_account_password(): string {
							return $this->email_account_password;
						}
					};

					$this->credentials = $imap_credentials;
				}

				/**
				 * Should the email be deleted after it is reconciled?
				 *
				 * Default: mark_read.
				 * On staging sites: nothing.
				 *
				 * @return string nothing|mark_read|delete
				 */
				public function after_reconcile_email_action(): string {

					if ( 'production' !== wp_get_environment_type() ) {
						return 'nothing';
					}

					return in_array( $this->action, array( 'nothing', 'mark_read', 'delete' ), true ) ? $this->action : 'mark_read';
				}

				/**
				 * Do not filter to a specific email address.
				 *
				 * @return null
				 */
				public function get_from_email_regex(): ?string {
					return null;
				}

				/**
				 * Filter to only emails whose body contains `https://cash.app/`.
				 *
				 * @return string
				 */
				public function get_identifier_regex(): ?string {
					return '/https:\/\/cash.app\//';
				}

				public function get_account_unique_friendly_name(): string {
					return $this->gateway_id;
				}

				public function get_credentials(): Account_Credentials_Interface {
					return $this->credentials;
				}
			};

		}
		return $mailboxes;
	}

	/**
	 * The regex patterns for parsing the emails.
	 *
	 * Multiple sets of patterns to extract data from the emails can be defined.
	 *
	 * @return array
	 */
	public function get_patterns(): array {

		$patterns = array();

		$patterns[] = new Patterns_1();

		return $patterns;
	}

	/**
	 * Used by IMAP_Reconcile to help match emails to customer orders.
	 *
	 * @return string
	 */
	public function get_customer_payment_id_meta_key(): string {
		return Zelle_Gateway::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY;
	}

	/**
	 * Used by logger.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return 'Zelle Gateway';
	}

	/**
	 * Used by logger and by plugins.php filter.
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return 'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php';
	}


	public function get_cpt_friendly_name(): string {
		return 'Zelle Reconciliation Emails';
	}
}
