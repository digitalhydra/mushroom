<?php
/**
 * Restrict Shipping Countries.
 *
 * @class   WC_CSP_Restrict_Shipping_Countries
 * @version 1.1.6
 * @author  SomewhereWarm
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CSP_Restrict_Shipping_Countries extends WC_CSP_Restriction implements WC_CSP_Checkout_Restriction, WC_CSP_Cart_Restriction {

	public function __construct() {

		$this->id                       = 'shipping_countries';
		$this->title                    = __( 'Shipping Countries &amp; States', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		$this->description              = __( 'Restrict the allowed shipping countries based on product-related constraints.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
		$this->validation_types         = array( 'checkout', 'cart' );
		$this->has_admin_product_fields = true;
		$this->supports_multiple        = true;

		$this->has_admin_global_fields  = true;
		$this->method_title             = __( 'Shipping Country Restrictions', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );

		// shows a woocommerce error on the 'woocommerce_review_order_before_cart_contents' hook when country restrictions apply
		add_action( 'woocommerce_review_order_before_cart_contents', array( $this, 'excluded_country_notice' ) );

		// save global settings
		add_action( 'woocommerce_update_options_restrictions_' . $this->id, array( $this, 'update_global_restriction_data' ) );

		// initialize global settings
		$this->init_form_fields();
	}

	/**
	 * Declare 'admin_global_fields' type, generated by 'generate_admin_global_fields_html'.
	 *
	 * @return void
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'admin_global_fields' => array(
				'type' => 'admin_global_fields'
				)
			);
	}

	/**
	 * Generates the 'admin_global_fields' field type, which is based on metaboxes.
	 *
	 * @return string
	 */
	function generate_admin_global_fields_html() {
		?><p>
			<?php echo __( 'Restrict the allowed shipping countries when the defined conditions apply. Complex rules can be created by adding multiple restriction instances. Each instance will be evaluated independently.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>
		</p><?php

		$this->get_admin_global_metaboxes_html();
	}

	/**
	 * Display admin options.
	 *
	 * @param  int    $index    restriction metabox unique id
	 * @param  string $options  metabox options
	 * @return string
	 */
	public function get_admin_fields_html( $index, $options, $field_type ) {

		$description = '';
		$countries   = array();
		$states      = array();
		$message     = '';

		if ( isset( $options[ 'description' ] ) ) {
			$description = $options[ 'description' ];
		}

		if ( isset( $options[ 'countries' ] ) ) {
			$countries = $options[ 'countries' ];
		}

		if ( isset( $options[ 'states' ] ) ) {
			$states = $options[ 'states' ];
		}

		if ( ! empty( $options[ 'message' ] ) ) {
			$message = $options[ 'message' ];
		}

		$shipping_countries = WC()->countries->get_shipping_countries();

		?>
		<p class="form-field">
			<label>
				<?php _e( 'Short Description', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>:
			</label>
			<textarea class="short_description" name="restriction[<?php echo $index; ?>][description]" id="restriction_<?php echo $index; ?>_message" placeholder="<?php _e( 'Optional short description for this rule&hellip;', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>" rows="1" cols="20"><?php echo $description; ?></textarea>
		</p>
		<p class="form-field select-field">
			<label><?php _e( 'Exclude Countries', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></label>
			<select name="restriction[<?php echo $index; ?>][countries][]" style="width:80%" class="multiselect <?php echo WC_CSP_Core_Compatibility::is_wc_version_gte_2_3() ? 'wc-enhanced-select' : 'chosen_select'; ?>" multiple="multiple" data-placeholder="<?php _e( 'Select Countries&hellip;', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>">
				<?php
					foreach ( $shipping_countries as $key => $val ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $countries ), true, false ) . '>' . $val . '</option>';
					}
				?>
			</select>
			<span class="form_row restriction_form_row">
				<a class="wccsp_select_all button" href="#"><?php _e( 'Select all', 'woocommerce' ); ?></a>
				<a class="wccsp_select_none button" href="#"><?php _e( 'Select none', 'woocommerce' ); ?></a>
			</span>
		</p>
		<p class="form-field exclude_states">
			<label><?php _e( 'Exclude States / Regions', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?></label>
			<select name="restriction[<?php echo $index; ?>][exclude_states]" style="width:80%" class="<?php echo WC_CSP_Core_Compatibility::is_wc_version_gte_2_3() ? 'wc-enhanced-select' : ''; ?>">
				<?php
					echo '<option value="all" ' . selected( empty( $states ), true, false ) . '>' . __( 'All States / Regions in the selected Countries', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ) . '</option>';
					echo '<option value="specific" ' . selected( ! empty( $states ), true, false ) . '>' . __( 'Specific States / Regions', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ) . '</option>';
				?>
			</select>
		</p>
		<p class="form-field excluded_states select-field" <?php echo empty( $states ) ? 'style="display:none;"': ''; ?>><?php
			if ( ! empty( $countries ) ) {
				?><select name="restriction[<?php echo $index; ?>][states][]" style="width:80%" class="multiselect <?php echo WC_CSP_Core_Compatibility::is_wc_version_gte_2_3() ? 'wc-enhanced-select' : 'chosen_select'; ?>" multiple="multiple" data-placeholder="<?php _e( 'Select States / Regions&hellip;', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>">
					<?php
					if ( ! empty( $countries ) ) {
						foreach ( $countries as $country_key ) {

							if ( ! isset( $shipping_countries[ $country_key ] ) ) {
								continue;
							}

							$country_value = $shipping_countries[ $country_key ];

							if ( $country_states = WC()->countries->get_states( $country_key ) ) {
								echo '<optgroup label="' . esc_attr( $country_value ) . '">';
									foreach ( $country_states as $state_key => $state_value ) {
										echo '<option value="' . esc_attr( $country_key ) . ':' . $state_key . '"';
										if ( ! empty( $states[ $country_key ] ) && in_array( $state_key, $states[ $country_key ] ) ) {
											echo ' selected="selected"';
										}
										echo '>' . $country_value . ' &mdash; ' . $state_value . '</option>';
									}
								echo '</optgroup>';
							}
						}
					}
					?>
				</select>
				<span class="form_row restriction_form_row">
					<a class="wccsp_select_all button" href="#"><?php _e( 'Select all', 'woocommerce' ); ?></a>
					<a class="wccsp_select_none button" href="#"><?php _e( 'Select none', 'woocommerce' ); ?></a>
				</span>
				<?php
			} else {
				?><span class="description"><?php
				 echo __( 'To select specific States / Regions, please add some countries with States / Regions to the <strong>Exclude Countries</strong> field and then save your changes.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
				?></span><?php
			}
			?>
		</p>
		<p class="form-field">
			<label>
				<?php _e( 'Custom Checkout Notice', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); ?>:
				<?php

					if ( $field_type == 'global' ) {
						$tiptip = __( 'Defaults to:<br/>&quot;Unfortunately your order cannot be shipped {to_excluded_destination}. To complete your order, please select an alternative shipping country / state.&quot;<br/>When conditions are defined, the default message will be amended with resolution instructions.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
					} else {
						$tiptip = __( 'Defaults to:<br/>&quot;Unfortunately your order cannot be shipped {to_excluded_destination}. To complete your order, please select an alternative shipping country / state, or remove {product} from your cart.&quot;<br/>When conditions are defined, the default message will be amended with resolution instructions.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
					}
				?>
			</label>
			<textarea class="custom_message" name="restriction[<?php echo $index; ?>][message]" id="restriction_<?php echo $index; ?>_message" placeholder="" rows="2" cols="20"><?php echo $message; ?></textarea>
			<?php
			echo '<img class="help_tip" data-tip="' . $tiptip . '" src="' . WC()->plugin_url() . '/assets/images/help.png" />';

			if ( $field_type == 'global' ) {
				$tip = __( 'Define a custom checkout error message to show when selecting an excluded shipping destination. You may include <code>{to_excluded_destination}</code> and have it substituted by the selected shipping country / state.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
			} else {
				$tip = __( 'Define a custom checkout error message to show when selecting an excluded shipping destination. You may include <code>{product}</code> and <code>{to_excluded_destination}</code> and have them substituted by the actual product title and country / state.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
			}

			echo '<span class="description">' . $tip . '</span>';

			?>
		</p>
		<?php
	}

	/**
	 * Display a short summary of the restriction's settings.
	 *
	 * @param  array  $options
	 * @return string
	 */
	public function get_options_description( $options ) {

		if ( ! empty( $options[ 'description' ] ) ) {
			return $options[ 'description' ];
		}

		$country_strings = array();
		$countries       = array();

		if ( isset( $options[ 'countries' ] ) ) {
			$countries = $options[ 'countries' ];
		}

		$shipping_countries = WC()->countries->get_shipping_countries();

		foreach ( $shipping_countries as $key => $val ) {

			if ( in_array( $key, $countries ) ) {
				$country_strings[] = $val;
			}
		}

		return trim( implode( ', ', $country_strings ), ', ' );
	}

	/**
	 * Display options on the global Restrictions page.
	 *
	 * @param  int    $index    restriction metabox unique id
	 * @param  string $options  metabox options
	 * @return string
	 */
	public function get_admin_global_fields_html( $index, $options = array() ) {

		$this->get_admin_fields_html( $index, $options, 'global' );
	}

	/**
	 * Display options on the product Restrictions write-panel.
	 *
	 * @param  int    $index    restriction metabox unique id
	 * @param  string $options  metabox options
	 * @return string
	 */
	public function get_admin_product_fields_html( $index, $options = array() ) {
		?><div class="description">
			<em><?php echo __( 'Restrict the allowed shipping countries when an order contains this product.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ); echo '<img class="help_tip" data-tip="' . __( 'The restriction does not have any effect on the list of billing/shipping countries available during checkout. When an excluded country is selected during checkout, a notice is displayed under the shipping details. Shipping country restrictions are additionally validated when attempting to complete an order. ', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" />'; ?></em>
		</div><?php

		$this->get_admin_fields_html( $index, $options, 'product' );
	}

	/**
	 * Validate, process and return options.
	 *
	 * @param  array  $posted_data
	 * @return array
	 */
	public function process_admin_fields( $posted_data ) {

		$processed_data = array();

		$processed_data[ 'countries' ] = array();

		if ( ! empty( $posted_data[ 'countries' ] ) ) {
			$processed_data[ 'countries' ] = array_map( 'stripslashes', $posted_data[ 'countries' ] );

			if ( ! empty( $posted_data[ 'exclude_states' ] ) && $posted_data[ 'exclude_states' ] === 'specific' && ! empty( $posted_data[ 'states' ] ) ) {
				$processed_data[ 'states' ] = array();
				$country_states             = array_map( 'stripslashes', $posted_data[ 'states' ] );

				foreach ( $country_states as $country_state_key ) {
					$country_state_key = explode( ':', $country_state_key );
					$country_key       = current( $country_state_key );
					$state_key         = end( $country_state_key );

					if ( in_array( $country_key, $processed_data[ 'countries' ] ) ) {
						$processed_data[ 'states' ][ $country_key ][] = $state_key;
					}
				}
			}

		} else {
			return false;
		}

		if ( ! empty( $posted_data[ 'message' ] ) ) {
			$processed_data[ 'message' ] = wp_kses_post( stripslashes( $posted_data[ 'message' ] ) );
		}

		if ( ! empty( $posted_data[ 'description' ] ) ) {
			$processed_data[ 'description' ] = strip_tags ( stripslashes( $posted_data[ 'description' ] ) );
		}

		return $processed_data;
	}

	/**
	 * Validate, process and return product metabox options.
	 *
	 * @param  array  $posted_data
	 * @return array
	 */
	public function process_admin_product_fields( $posted_data ) {

		$processed_data = $this->process_admin_fields( $posted_data );

		if ( ! $processed_data ) {

			WC_Admin_Meta_Boxes::add_error( sprintf( __( 'Restriction #%s was not saved. Before saving a &quot;Shipping Countries&quot; restriction, remember to add at least one shipping country to the exclusions list.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), $posted_data[ 'index' ] ) );
			return false;
		}

		return $processed_data;
	}

	/**
	 * Validate, process and return global settings.
	 *
	 * @param  array  $posted_data
	 * @return array
	 */
	public function process_admin_global_fields( $posted_data ) {

		$processed_data = $this->process_admin_fields( $posted_data );

		if ( ! $processed_data ) {

			WC_Admin_Settings::add_error( sprintf( __( 'Restriction #%s was not saved. Before saving a &quot;Shipping Countries&quot; restriction, remember to add at least one shipping country to the exclusions list.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), $posted_data[ 'index' ] ) );
			return false;
		}

		return $processed_data;

	}

	/**
	 * Shows a woocommerce error on the 'woocommerce_review_order_before_cart_contents' hook when country restrictions apply.
	 *
	 * @return void
	 */
	public function excluded_country_notice() {

		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) ) {

			$result = $this->check_restriction();

			if ( $result->has_messages() ) {
				foreach ( $result->get_messages() as $message ) {
					wc_add_notice( $message[ 'text' ], $message[ 'type' ] );
				}
			}
		}
	}

	/**
	 * Evaluate restriction objectives and return WC_CSP_Check_Result object.
	 *
	 * @return  WC_CSP_Check_Result
	 */
	private function check_restriction( $msg_type = 'error' ) {

		$result = new WC_CSP_Check_Result();

		$product_restrictions_exist = false;

		$cart_contents    = WC()->cart->get_cart();
		$shipping_country = WC()->customer->get_shipping_country();
		$shipping_state   = WC()->customer->get_shipping_state();

		$locale           = WC()->countries->get_country_locale();
		$countries        = WC()->countries->get_countries();
		$states           = WC()->countries->get_states( $shipping_country );

		$destination      = '';
		$destination_type = '';

		/* ----------------------------------------------------------------- */
		/* Product Restrictions
		/* ----------------------------------------------------------------- */

		// Loop cart contents
		if ( ! empty( $cart_contents ) ) {
			foreach ( $cart_contents as $cart_item_key => $cart_item_data ) {

				$product = $cart_item_data[ 'data' ];

				$product_restriction_data = $this->get_product_restriction_data( $product->id );

				if ( ! empty( $product_restriction_data ) ) {

					// Evaluate all restriction sets for the current product
					foreach ( $product_restriction_data as $restriction ) {

						$restriction_exists = false;

						// If country exclusions are present and all exclusion conditions defined in the restriction apply, add error
						if ( ! empty( $restriction[ 'countries' ] ) && $this->check_conditions_apply( $restriction, array( 'cart_item_data' => $cart_item_data ) ) ) {

							$restricted_countries = $restriction[ 'countries' ];

							if ( in_array( $shipping_country, $restricted_countries ) ) {

								if ( empty( $restriction[ 'states' ][ $shipping_country ] ) ) {
									$product_restrictions_exist = true;
									$restriction_exists         = true;
									$destination                = $countries[ $shipping_country ];
									$to_destination             = sprintf( __( '%1$s %2$s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN, 'to country destination' ), WC()->countries->shipping_to_prefix(), $destination );
									$destination_type           = __( 'Country', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
								} elseif ( in_array( $shipping_state, $restriction[ 'states' ][ $shipping_country ] ) ) {
									$product_restrictions_exist = true;
									$restriction_exists         = true;
									$destination                = $states[ $shipping_state ];
									$to_destination             = sprintf( __( 'to %s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN, 'to state destination' ), $destination );
									$destination_type           = isset( $locale[ $shipping_country ][ 'state' ][ 'label' ] ) ? $locale[ $shipping_country ][ 'state' ][ 'label' ] : __( 'State / County', 'woocommerce' );
								}
							}

						}

						if ( $restriction_exists ) {

							if ( ! empty( $restriction[ 'message' ] ) ) {

								$message 	= str_replace( array('{product}', '{to_excluded_destination}' ), array( '&quot;%1$s&quot;', '%2$s' ), $restriction[ 'message' ] );
								$resolution = '';

							} else {

								$conditions_resolution = $this->get_conditions_resolution( $restriction, array( 'cart_item_data' => $cart_item_data ) );

								if ( $conditions_resolution ) {
									$resolution = sprintf( __( 'To have your order shipped %1$s, please %2$s. Otherwise, select an alternative shipping %3$s, or remove &quot;%4$s&quot; from your cart.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), $to_destination, $conditions_resolution, $destination_type, $product->get_title() );
								} else {
									$resolution = sprintf( __( 'To complete your order, please select an alternative shipping %1$s, or remove &quot;%2$s&quot; from your cart.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), $destination_type, $product->get_title() );
								}

								$message = __( 'Unfortunately your order cannot be shipped %2$s. %3$s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
							}

							$result->add( 'country_excluded_by_product_restriction', sprintf( $message, $product->get_title(), $to_destination, $resolution ), $msg_type );
						}
					}
				}
			}
		}

		/* ----------------------------------------------------------------- */
		/* Global Restrictions
		/* ----------------------------------------------------------------- */

		$global_restrictions_exist = false;
		$restriction_exists        = false;

		// Grab global restrictions
		$global_restriction_data = $this->get_global_restriction_data();

		if ( ! empty( $global_restriction_data ) ) {

			// Evaluate all restriction sets for the current cart
			foreach ( $global_restriction_data as $restriction ) {

				// Check if cart contents not empty
				if ( ! empty( $cart_contents ) ) {

					// Check if globally defined conditions apply
					if ( ! empty( $restriction[ 'countries' ] ) && $this->check_conditions_apply( $restriction ) ) {

						$restricted_countries = $restriction[ 'countries' ];

						if ( in_array( $shipping_country, $restricted_countries ) ) {

							if ( empty( $restriction[ 'states' ][ $shipping_country ] ) ) {
								$global_restrictions_exist = true;
								$restriction_exists        = true;
								$destination               = $countries[ $shipping_country ];
								$to_destination            = sprintf( __( '%1$s %2$s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN, 'to country destination' ), WC()->countries->shipping_to_prefix(), $destination );
								$destination_type          = __( 'Country', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
							} elseif ( in_array( $shipping_state, $restriction[ 'states' ][ $shipping_country ] ) ) {
								$global_restrictions_exist = true;
								$restriction_exists        = true;
								$destination               = $states[ $shipping_state ];
								$to_destination            = sprintf( __( 'to %s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN, 'to state destination' ), $destination );
								$destination_type          = isset( $locale[ $shipping_country ][ 'state' ][ 'label' ] ) ? $locale[ $shipping_country ][ 'state' ][ 'label' ] : __( 'State / County', 'woocommerce' );
							}
						}

						if ( $restriction_exists ) {

							if ( ! empty( $restriction[ 'message' ] ) ) {

								$message 	= str_replace( '{to_excluded_destination}', '%1$s', $restriction[ 'message' ] );
								$resolution = '';

							} else {

								$conditions_resolution = $this->get_conditions_resolution( $restriction );

								if ( $conditions_resolution ) {
									$resolution = sprintf( __( 'To have your order shipped %1$s, please %2$s. Otherwise, select an alternative shipping %3$s.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), $to_destination, $conditions_resolution, $destination_type );
								} else {
									$resolution = sprintf( __( 'To complete your order, please select an alternative shipping %1$s.', WC_Conditional_Shipping_Payments::TEXT_DOMAIN ), $destination_type );
								}

								$message = __( 'Unfortunately your order cannot be shipped %1$s. %2$s', WC_Conditional_Shipping_Payments::TEXT_DOMAIN );
							}

							$result->add( 'country_excluded_by_global_restriction', sprintf( $message, $to_destination, $resolution ), $msg_type );
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Validate order checkout and return errors if restrictions apply.
	 *
	 * @param  array  $posted
	 * @return void
	 */
	public function validate_checkout( $posted ) {

		return $this->check_restriction();
	}

	/**
	 * Validate cart and return errors if restrictions apply.
	 *
	 * @return void
	 */
	public function validate_cart() {

		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) && get_option( 'woocommerce_enable_shipping_calc' ) === 'yes' ) {
			return $this->check_restriction( 'notice' );
		} else {
			return new WC_CSP_Check_Result();
		}
	}
}
