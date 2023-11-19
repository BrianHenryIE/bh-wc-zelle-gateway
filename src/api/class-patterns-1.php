<?php
/**
 * TODO
 *
 * @package   brianhenryie/bh-wc-zelle-gateway
 */

namespace BrianHenryIE\WC_Zelle_Gateway\API;

use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\Email_Extract_Settings_Helper_Trait;
use BrianHenryIE\WC_Zelle_Gateway\WC_Order_Email_Reconcile\Email_Extract_Settings_Interface;

/**
 * A set of getters returning string regex patterns. No logic.
 */
class Patterns_1 implements Email_Extract_Settings_Interface {
	use Email_Extract_Settings_Helper_Trait;

	/**
	 * "Brian Henry sent you $199.99"
	 */
	public function get_subject_regex(): ?string {
		return '/.*sent you.*/';
	}

	/**
	 * Amount: $6.66 (USD)
	 *
	 * ?? possibly dated: This regex expects a forwarded email. i.e. so the previous subject is in the body of the email.
	 */
	public function get_amount_regex(): string {
		return '/Amount:\s*.*?\$(\d+\.\d{2})/';
	}

	/**
	 * TODO: Does zelle send us the customer email address? (is a zelle account username always the email address?)
	 *
	 * @return string
	 */
	public function get_customer_email_regex(): ?string {
		return null;
	}

	/**
	 * "SENDER sent you money through"
	 *
	 * @return string
	 */
	public function get_customer_name_regex(): string {
		return '/^(.*)sent you money through/';
	}

	/**
	 * Set of terms to extract that will be saved to the order notes.
	 *
	 * @return array<string, string>
	 */
	public function get_notes_array_regex(): array {
		return array(
			'note' => '/Memo: (?P<note>.*?)$/',
		);
	}

	/**
	 * The Chase Zelle email does not contain the sender account email.
	 */
	public function get_customer_id_regex(): ?string {
		return null;
	}

	/**
	 * The Chase Zelle email does not contain a transaction id.
	 */
	public function get_transaction_id_regex(): ?string {
		return null;
	}

	/**
	 * The Chase Zelle emails do not contain a link to the transaction.
	 */
	public function get_transaction_url_regex(): ?string {
		return null;
	}
}
