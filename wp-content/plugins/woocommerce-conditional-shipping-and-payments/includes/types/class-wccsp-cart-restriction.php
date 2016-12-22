<?php
/**
 * Check Cart Restriction Interface.
 *
 * @version 1.0.0
 * @author  SomewhereWarm
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface WC_CSP_Cart_Restriction {

	/**
	 * Restriction validation running on the 'check_cart_items' hook.
	 *
	 * @return void
	 */
	public function validate_cart();
}
