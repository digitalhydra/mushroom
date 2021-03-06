<?php
/**
 * Order Total Condition.
 *
 * @class   WC_CSP_Condition_Order_Total
 * @version 1.1.3
 * @author  SomewhereWarm
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CSP_Condition_Order_Total extends WC_CSP_Condition {

	public function __construct() {

		$this->id                             = 'order_total';
		$this->title                          = __( 'Order Total', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		$this->supported_global_restrictions  = array( 'payment_gateways' );
		$this->supported_product_restrictions = array( 'payment_gateways' );
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
		if ( ! isset( $data[ 'value' ] ) || $data[ 'value' ] === '' ) {
			return false;
		}

		$order_total = WC()->cart->total;

		if ( $data[ 'modifier' ] === 'min' && $data[ 'value' ] <= $order_total ) {
			return sprintf( __( 'decrease your order total below %s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), wc_price( $data[ 'value' ] ) );
		}

		if ( $data[ 'modifier' ] === 'max' && $data[ 'value' ] > $order_total ) {
			return sprintf( __( 'increase your order total above %s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), wc_price( $data[ 'value' ] ) );
		}

		return false;
	}

	/**
	 * Evaluate if the condition is in effect or not.
	 *
	 * @param  string $data   condition field data
	 * @param  array  $args   optional arguments passed by restrictions
	 * @return boolean
	 */
	public function check_condition( $data, $args ) {

		// Empty conditions always apply (not evaluated).
		if ( ! isset( $data[ 'value' ] ) || $data[ 'value' ] === '' ) {
			return true;
		}

		if ( is_checkout_pay_page() ) {

			global $wp;

			if ( ! isset( $wp->query_vars[ 'order-pay' ] ) ) {
				return false;
			}

			$order_id = $wp->query_vars[ 'order-pay' ];
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				return false;
			}

			$order_total = $order->get_total();

		} else {

			$order_total = WC()->cart->total;
		}

		if ( $data[ 'modifier' ] === 'min' && $data[ 'value' ] <= $order_total ) {
			return true;
		}

		if ( $data[ 'modifier' ] === 'max' && $data[ 'value' ] > $order_total ) {
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

		if ( isset( $posted_condition_data[ 'value' ] ) ) {
			$processed_condition_data[ 'condition_id' ] = $this->id;
			$processed_condition_data[ 'value' ]        = $posted_condition_data[ 'value' ] !== '0' ? floatval( stripslashes( $posted_condition_data[ 'value' ] ) ) : 0;
			$processed_condition_data[ 'modifier' ]     = stripslashes( $posted_condition_data[ 'modifier' ] );

			if ( $processed_condition_data[ 'value' ] > 0 || $processed_condition_data[ 'value' ] === 0 ) {
				return $processed_condition_data;
			}
		}

		return false;
	}

	/**
	 * Get order total conditions content for admin restriction metaboxes.
	 *
	 * @param  int    $index
	 * @param  int    $condition_ndex
	 * @param  array  $condition_data
	 * @return str
	 */
	public function get_admin_fields_html( $index, $condition_index, $condition_data ) {

		$modifier    = '';
		$order_total = '';

		if ( ! empty( $condition_data[ 'modifier' ] ) ) {
			$modifier = $condition_data[ 'modifier' ];
		} else {
			$modifier = 'max';
		}

		if ( isset( $condition_data[ 'value' ] ) ) {
			$order_total = $condition_data[ 'value' ];
		}

		?>
		<input type="hidden" name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][condition_id]" value="<?php echo $this->id; ?>" />
		<div class="condition_modifier">
			<select name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][modifier]">
				<option value="max" <?php selected( $modifier, 'max', true ) ?>><?php echo __( '<', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></option>
				<option value="min" <?php selected( $modifier, 'min', true ) ?>><?php echo __( '>=', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></option>
			</select>
		</div>
		<div class="condition_value">
			<input type="text" class="wc_input_price short" name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][value]" value="<?php echo $order_total; ?>" placeholder="" step="any" min="0"/>
		</div>
		<?php
	}
}
