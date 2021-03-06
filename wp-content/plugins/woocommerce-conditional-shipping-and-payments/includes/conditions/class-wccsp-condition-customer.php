<?php
/**
 * Customer Condition (e-mail).
 *
 * @class   WC_CSP_Condition_Customer
 * @version 1.1.6
 * @author  SomewhereWarm
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CSP_Condition_Customer extends WC_CSP_Condition {

	public function __construct() {

		$this->id                             = 'customer';
		$this->title                          = __( 'Customer', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		$this->supported_product_restrictions = array( 'shipping_countries', 'payment_gateways', 'shipping_methods' );
		$this->supported_global_restrictions  = array( 'shipping_countries', 'payment_gateways', 'shipping_methods' );
	}

	/**
	 * Return condition field-specific resolution message which is combined along with others into a single restriction "resolution message".
	 *
	 * @param  array  $data   condition field data
	 * @param  array  $args   optional arguments passed by restriction
	 * @return string|false
	 */
	public function get_condition_resolution( $data, $args ) {

		// Empty conditions always return false (not evaluated).
		if ( empty( $data[ 'value' ] ) ) {
			return false;
		}

		$check_emails      = array();
		$restricted_emails = array_map( 'sanitize_email', $data[ 'value' ] );

		if ( is_user_logged_in() ) {
			$current_user   = wp_get_current_user();
			$check_emails[] = $current_user->user_email;
		}

		if ( isset( WC()->checkout->posted[ 'billing_email' ] ) ) {
			$check_emails[] = WC()->checkout->posted[ 'billing_email' ];
		}

		$check_emails = array_map( 'sanitize_email', array_map( 'strtolower', $check_emails ) );

		$identified_email  = false;

		if ( ! empty( $check_emails ) ) {
			foreach ( $check_emails as $check_email ) {
				if ( in_array( $check_email, $restricted_emails ) ) {
					$identified_email = true;
					break;
				}
			}
		}

		if ( $data[ 'modifier' ] === 'in' && $identified_email ) {
			return __( 'use an authorized account', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		} elseif ( $data[ 'modifier' ] === 'not-in' && ! $identified_email ) {
			return __( 'use an authorized account', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		}

		return false;
	}

	/**
	 * Evaluate if the condition is in effect or not.
	 *
	 * @param  array  $data   condition field data
	 * @param  array  $args   optional arguments passed by restrictions
	 * @return boolean
	 */
	public function check_condition( $data, $args ) {

		// Empty conditions always apply (not evaluated).
		if ( empty( $data[ 'value' ] ) ) {
			return true;
		}

		$check_emails      = array();
		$restricted_emails = array_map( 'sanitize_email', $data[ 'value' ] );

		if ( is_user_logged_in() ) {
			$current_user   = wp_get_current_user();
			$check_emails[] = $current_user->user_email;
		}

		if ( is_checkout_pay_page() ) {

			global $wp;

			if ( isset( $wp->query_vars[ 'order-pay' ] ) ) {

				$order_id = $wp->query_vars[ 'order-pay' ];
				$order    = wc_get_order( $order_id );

				if ( $order && ! empty( $order->billing_email ) ) {
					$check_emails[] = $order->billing_email;
				}
			}

		} else {

			if ( isset( WC()->checkout->posted[ 'billing_email' ] ) ) {
				$check_emails[] = WC()->checkout->posted[ 'billing_email' ];
			}

			$check_emails = array_map( 'sanitize_email', array_map( 'strtolower', $check_emails ) );
		}

		$identified_email = false;

		if ( ! empty( $check_emails ) ) {
			foreach ( $check_emails as $check_email ) {
				if ( in_array( $check_email, $restricted_emails ) ) {
					$identified_email = true;
					break;
				}
			}
		}

		if ( $data[ 'modifier' ] === 'in' && $identified_email ) {
			return true;
		} elseif ( $data[ 'modifier' ] === 'not-in' && ! $identified_email ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate, process and return condition fields.
	 *
	 * @param  array  $posted_condition_data
	 * @return array
	 */
	public function process_admin_fields( $posted_condition_data ) {

		$processed_condition_data = array();

		if ( ! empty( $posted_condition_data[ 'value' ] ) ) {
			$processed_condition_data[ 'condition_id' ] = $this->id;
			$processed_condition_data[ 'value' ]        = array_filter( array_map( 'trim', explode( ',', wc_clean( $posted_condition_data[ 'value' ] ) ) ) );
			$processed_condition_data[ 'modifier' ]     = stripslashes( $posted_condition_data[ 'modifier' ] );

			return $processed_condition_data;
		}

		return false;
	}

	/**
	 * Get categories-in-cart condition content for global restrictions.
	 *
	 * @param  int    $index
	 * @param  int    $condition_index
	 * @param  array  $condition_data
	 * @return str
	 */
	public function get_admin_fields_html( $index, $condition_index, $condition_data ) {

		$modifier = '';
		$emails   = '';

		if ( ! empty( $condition_data[ 'modifier' ] ) ) {
			$modifier = $condition_data[ 'modifier' ];
		}

		if ( ! empty( $condition_data[ 'value' ] ) ) {
			$emails = implode( ', ', $condition_data[ 'value' ] );
		}

		?>
		<input type="hidden" name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][condition_id]" value="<?php echo $this->id; ?>" />
		<div class="condition_modifier">
			<select name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][modifier]">
				<option value="in" <?php selected( $modifier, 'in', true ) ?>><?php echo __( 'e-mail is', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></option>
				<option value="not-in" <?php selected( $modifier, 'not-in', true ) ?>><?php echo __( 'e-mail is not', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></option>
			</select>
		</div>
		<div class="condition_value">
			<textarea class="short" name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][value]" placeholder="<?php _e( 'List of e-mails separated by comma.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>"><?php echo esc_textarea( $emails ) ?></textarea>
		</div><?php
	}
}
