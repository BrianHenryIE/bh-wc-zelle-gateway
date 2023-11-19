<?php
/**
 * The primary invokable functions of the plugin.
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\API;

use BrianHenryIE\WC_Zelle_Gateway\API_Interface;
use BrianHenryIE\WC_Zelle_Gateway\Settings_Interface;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\BH_WC_Order_Email_Reconcile;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class API implements API_Interface {
	use LoggerAwareTrait;

	/**
	 * @param BH_WC_Order_Email_Reconcile $reconciler An instance of the IMAP_Reconcile library to check an email account and match the emails to orders.
	 * @param Settings_Interface          $settings The plugin's settings.
	 * @param LoggerInterface             $logger A PSR logger.
	 */
	public function __construct(
		protected BH_WC_Order_Email_Reconcile $reconciler,
		protected Settings_Interface $settings,
		LoggerInterface $logger,
	) {
		$this->logger = $logger;
	}

	/**
	 * TODO: Make available the mail checker so it can be invoked here (e.g. by CLI).
	 */
	public function check_for_payment_emails(): void {
	}
}
