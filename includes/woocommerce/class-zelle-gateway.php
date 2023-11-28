<?php

namespace BrianHenryIE\WC_Zelle_Gateway\WooCommerce;

use BrianHenryIE\WC_Zelle_Gateway\API\Settings;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\WooCommerce\Credentials_Settings_Fields;
use WC_Order;
use WC_Payment_Gateway;
use WP_Screen;

class Zelle_Gateway extends WC_Payment_Gateway {

	public $id = 'zelle';

	// The Zelle emails (Chase) do not have the customer email to help with matching. Still, let's collect
	// the account name so it might help with manual matching.
	const ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY = 'customer-zelle-account-name';

	const DESTINATION_ZELLE_EMAIL_ADDRESS_META_KEY = 'destination-account-zelle-email-address';

	/**
	 * @var Settings
	 */
	protected Settings $plugin_settings;

	public function __construct() {

		$this->plugin_settings = new Settings();

		$this->plugin_id = "{$this->plugin_settings->get_plugin_slug()}_";

		$this->has_fields = true;

		$this->icon = plugins_url( 'assets/images/zelle-logo.png', 'bh-wc-zelle-gateway/bh-wc-zelle-gateway.php' );

		/**
		 * @see Payment_Gateways::format_admin_gateway_name()
		 * @see Zelle_Gateway::get_title()
		 */
		$this->method_title = 'Zelle';

		/**
		 * @see Zelle_Gateway::get_method_description()
		 */
		$this->method_description = 'Prompts the customer for their Zelle account name and tells them an account to send to.';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		// Save the wp-admin configuration form options.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Save the Zelle username to the order meta as the order is created.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_order_payment_type_meta_data' ), 10, 2 );

		$this->enabled = ( 'yes' === $this->enabled & $this->is_configured() ) ? 'yes' : 'no';
	}

	/**
	 * Check is the destination Zelle account username entered so the gateway is ready to use.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return ! empty( $this->get_option( 'zelle_destination_email_address' ) );
	}

	/**
	 * The wp-admin configuration form.
	 */
	public function init_form_fields(): void {

		$form_fields = array(
			'enabled'                         => array(
				'title'   => __( 'Enable/Disable', 'bh-wc-zelle-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable This Gateway', 'bh-wc-zelle-gateway' ),
				'default' => 'yes',
			),
			'title'                           => array(
				'title'       => __( 'Title', 'bh-wc-zelle-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'bh-wc-zelle-gateway' ),
				'default'     => _x( 'Zelle', 'Method description here', 'bh-wc-zelle-gateway' ),
				'desc_tip'    => true,
			),
			'description'                     => array(
				'title'       => __( 'Description', 'bh-wc-zelle-gateway' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'bh-wc-zelle-gateway' ),
				'default'     => 'Use the Zelle app to pay for your order.',
				'desc_tip'    => true,
			),
			'zelle_destination_email_address' => array(
				'title'       => __( 'Zelle Email Address', 'bh-wc-zelle-gateway' ),
				'type'        => 'text',
				'description' => __( 'The Zelle account email address the customer will be instructed to pay.', 'bh-wc-zelle-gateway' ),
				'desc_tip'    => true,
			),
		// 'zelle_destination_account_name'  => array(
		// 'title'       => __( 'Zelle Account Name', 'bh-wc-zelle-gateway' ),
		// 'type'        => 'text',
		// 'description' => __( 'The name on the Zelle account the customer will be instructed to pay.', 'bh-wc-zelle-gateway' ),
		// 'desc_tip'    => true,
		// ),
		);

		$credentials_fields = new Credentials_Settings_Fields();
		$form_fields        = $credentials_fields->append_imap_reconcile_fields( $form_fields );

		$this->form_fields = $form_fields;
	}

	/**
	 * Prints the form displayed on the checkout.
	 *
	 * I.e. a simple HTML text input for the Zelle username.
	 *
	 * Checks for a logged in WordPress user, and checks their user meta for a saved Zelle username.
	 */
	public function payment_fields(): void {

		// This just prints the description.
		parent::payment_fields();

		$args = array(
			'label'       => 'Enter your Zelle account name: (to help reconciliation)',
			'placeholder' => '',
			'maxlength'   => 255,
			'required'    => false,
		);

		$current_user_id = get_current_user_id();
		if ( ! empty( $current_user_id ) ) {
			$zelle_username = get_user_meta( $current_user_id, self::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY, true );
			if ( ! empty( $zelle_username ) ) {
				$args['default'] = $zelle_username;
			}
		}

		// Prints the field.
		woocommerce_form_field(
			self::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY,
			$args
		);
	}

	/**
	 * Save the Zelle username to the order as it is created.
	 * If they have a WordPress user account, save it as use meta for future checkouts.
	 *
	 * @hooked woocommerce_checkout_create_order
	 * @see WC_Checkout::create_order()
	 * @see WC_Checkout::get_posted_data()
	 *
	 * @param WC_Order              $order The newly created WooCommerce order.
	 * @param array<string, string> $data The order data as POSTed (and cleaned).
	 */
	public function save_order_payment_type_meta_data( $order, $data ): void {

		if ( $data['payment_method'] !== $this->id ) {
			return;
		}

		// This isn't really filtered here.
		$zelle_customer_account_name = filter_input( INPUT_POST, self::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY );

		$order->add_meta_data( self::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY, $zelle_customer_account_name );

		$customer_id = $order->get_customer_id();
		if ( ! empty( $customer_id ) ) {
			update_user_meta( $customer_id, self::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY, $zelle_customer_account_name );
		}

		$zelle_destination_email_address = $this->get_option( 'zelle_destination_email_address' );
		$order->add_meta_data( self::DESTINATION_ZELLE_EMAIL_ADDRESS_META_KEY, $zelle_destination_email_address );

		$order->add_order_note( "Customer Zelle account name: {$zelle_customer_account_name} <br/>sent to pay: {$zelle_destination_email_address}." );

		$order->save();
	}

	/**
	 * Function that is called by WooCommerce when customer presses "Place Order".
	 *
	 * For cc payments, the card details would be POSTed to an API, but for this gateway,
	 * we just redirect to the Thank You page where customers are given payment instructions.
	 *
	 * On-Hold – Awaiting payment – stock is reduced, but you need to confirm payment.
	 *
	 * @see https://docs.woocommerce.com/document/managing-orders/
	 * @see WC_Payment_Gateway::process_payment()
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @param int $order_id The WooCommerce order id.
	 * @return array{'result': string}|array{'result': string, "redirect": string}
	 */
	public function process_payment( $order_id ): array {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			return array(
				'result' => 'failure',
			);
		}

		$zelle_destination_email_address = $this->get_option( 'zelle_destination_email_address' );

		// $zelle_destination_account_name  = $this->get_option( 'zelle_destination_account_name' );

		$zelle_qr_link = 'https://enroll.zellepay.com/qr-codes?data=' .
						base64_encode(
							json_encode(
								array(
									'token'  => $zelle_destination_email_address,
									// 'name'   => $zelle_destination_account_name,
									'action' => 'payment',
								)
							)
						);

		$zelle_customer_account_name = $order->get_meta( self::ZELLE_CUSTOMER_ACCOUNT_NAME_ORDER_META_KEY );

		$order->update_status(
			'on-hold',
			sprintf(
				'Awaiting Zelle payment to <a data-qr="%s">%s</a> %s.',
				$zelle_qr_link,
				$zelle_destination_email_address,
				! empty( $zelle_customer_account_name ) ? " from <b>{$zelle_customer_account_name}</b>" : '',
			)
		);

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Empty cart.
		WC()->cart->empty_cart();

		// Redirect to Thank You page.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Return the gateway's title.
	 *
	 * This is displayed on the checkout to the customer.
	 * Also displayed on the admin order page.
	 *
	 * @see WC_Payment_Gateway::get_title()
	 *
	 * @return string
	 */
	public function get_title(): string {

		$title = $this->title;

		// This will only be entered on admin screens.
		if ( function_exists( 'get_current_screen' ) ) {
			$zelle_destination_email_address = $this->get_option( 'zelle_destination_email_address' );
			$screen                          = get_current_screen();

			if ( ! empty( $zelle_destination_email_address ) && $screen instanceof WP_Screen && 'shop_order' === $screen->id ) {
				$title = "Zelle: {$zelle_destination_email_address}";
			}
		}

		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}


	/**
	 * Return the description for admin screens.
	 *
	 * Adds a link to the Zelle account if it has been configured.
	 *
	 * TODO: Add a thickbox to show the QR code (because the link only works when scanned by the bank app's Zelle camera).
	 *
	 * @see WC_Payment_Gateway::get_method_description()
	 */
	public function get_method_description(): string {

		$method_description = $this->method_description;

		$zelle_destination_email_address = $this->get_option( 'zelle_destination_email_address' );
		// $zelle_destination_account_name  = $this->get_option( 'zelle_destination_account_name' );

		$zelle_qr_link = 'https://enroll.zellepay.com/qr-codes?data=' .
						base64_encode(
							json_encode(
								array(
									'token' => $zelle_destination_email_address,
									// 'name'   => $zelle_destination_account_name,
															'action' => 'payment',
								)
							)
						);

		if ( ! empty( $zelle_destination_email_address ) ) {
			$method_description = "Prompts the customer for their Zelle email address and instructs them to send payment to: <a href=\"{$zelle_qr_link}\">Zelle: {$zelle_destination_email_address}</a>";
		}

		return apply_filters( 'woocommerce_gateway_method_description', $method_description, $this );
	}
}
