<?php
/**
 * Checkout Restriction Interface.
 *
 * @version 1.0.0
 * @author  SomewhereWarm
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface WC_CSP_Checkout_Restriction {

	/**
	 * Restriction validation running on the 'woocommerce_after_checkout_validation' hook.
	 *
	 * @param  array  $posted
	 * @return void
	 */
	public function validate_checkout( $posted );
}
