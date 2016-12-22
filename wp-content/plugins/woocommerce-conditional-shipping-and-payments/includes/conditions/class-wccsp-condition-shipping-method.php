<?php
/**
 * Selected Shipping Method Condition.
 *
 * @class   WC_CSP_Condition_Shipping_Method
 * @version 1.1.3
 * @author  SomewhereWarm
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CSP_Condition_Shipping_Method extends WC_CSP_Condition {

	public function __construct() {

		$this->id                             = 'shipping_method';
		$this->title                          = __( 'Shipping Method', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		$this->supported_global_restrictions  = array( 'payment_gateways' );
		$this->supported_product_restrictions = array( 'payment_gateways' );
	}

	/**
	 * True if a rate id is excluded.
	 *
	 * @param  string  $rate_id
	 * @param  array   $excluded_rates
	 * @return boolean
	 */
	private function is_restricted( $rate_id, $excluded_rates ) {

		foreach ( $excluded_rates as $excluded_rate_id ) {

			if ( $rate_id === $excluded_rate_id ) {
				return true;
			} elseif ( $excluded_rate_id !== 'flat_rate' && false === strpos( $excluded_rate_id, ':' ) ) {
				if ( 0 === strpos( $rate_id, $excluded_rate_id ) ) {
					return true;
				}
			}
		}

		return false;
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

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$excluded_rates = $data[ 'value' ];

		foreach ( $chosen_methods as $chosen_method ) {
			if ( $this->is_restricted( $chosen_method, $excluded_rates ) ) {
				return __( 'select a different shipping method', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
			}
		}

		return false;
	}

	/**
	 * Evaluate if a condition field is in effect or not.
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

		if ( is_checkout_pay_page() ) {

			global $wp;

			if ( isset( $wp->query_vars[ 'order-pay' ] ) ) {

				$order_id       = $wp->query_vars[ 'order-pay' ];
				$order          = wc_get_order( $order_id );
				$chosen_methods = array();

				if ( $order ) {
					$order_shipping_methods = $order->get_shipping_methods();

					if ( ! empty( $order_shipping_methods ) ) {
						foreach ( $order_shipping_methods as $order_shipping_method ) {
							$chosen_methods[] = $order_shipping_method[ 'method_id' ];
						}
					}
				}
			}

		} else {
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		}

		$excluded_rates = $data[ 'value' ];

		if ( ! empty( $chosen_methods ) ) {
			foreach ( $chosen_methods as $chosen_method ) {
				if ( $this->is_restricted( $chosen_method, $excluded_rates ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Validate, process and return condition field data.
	 *
	 * @param  array  $posted_condition_data
	 * @return array
	 */
	public function process_admin_fields( $posted_condition_data ) {

		$processed_condition_data = array();

		if ( ! empty( $posted_condition_data[ 'value' ] ) ) {
			$processed_condition_data[ 'condition_id' ] = $this->id;
			$processed_condition_data[ 'value' ]        = array_map( 'stripslashes', $posted_condition_data[ 'value' ] );
			$processed_condition_data[ 'modifier' ]     = stripslashes( $posted_condition_data[ 'modifier' ] );

			return $processed_condition_data;
		}

		return false;
	}

	/**
	 * Get shipping methods condition content for admin product restriction metaboxes.
	 *
	 * @param  int    $index
	 * @param  int    $condition_index
	 * @param  array  $condition_data
	 * @return str
	 */
	public function get_admin_fields_html( $index, $condition_index, $condition_data ) {

		$modifier = '';
		$methods  = array();

		if ( ! empty( $condition_data[ 'modifier' ] ) ) {
			$modifier = $condition_data[ 'modifier' ];
		}

		if ( ! empty( $condition_data[ 'value' ] ) ) {
			$methods = $condition_data[ 'value' ];
		}

		$shipping_methods = WC()->shipping->load_shipping_methods();

		?>
		<input type="hidden" name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][condition_id]" value="<?php echo $this->id; ?>" />
		<div class="condition_modifier">
			<select name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][modifier]">
				<option value="in" <?php selected( $modifier, 'in', true ) ?>><?php echo __( 'is', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></option>
			</select>
		</div>
		<div class="condition_value">
			<select name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][value][]" style="width:80%" class="multiselect <?php echo WC_CSP_Core_Compatibility::is_wc_version_gte_2_3() ? 'wc-enhanced-select' : 'chosen_select'; ?>" multiple="multiple" data-placeholder="<?php _e( 'Select shipping methods&hellip;', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>">
				<?php
					foreach ( $shipping_methods as $key => $val ) {
						do_action( 'woocommerce_csp_admin_shipping_method_option', $key, $val, $methods );
					}
				?>
			</select>
		</div><?php

	}

}
