<?php
/**
 * Restrictions.
 *
 * Loads restriction classes via hooks and prepares them for use.
 *
 * @class   WC_CSP_Restrictions
 * @version 1.1.0
 * @author  SomewhereWarm
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CSP_Restrictions {

	/** @var array Array of registered restriction classes. */
	var $restrictions;

	public function __construct() {

		$load_restrictions = apply_filters( 'woocommerce_csp_restrictions', array(
			'WC_CSP_Restrict_Payment_Gateways', 	// Restrict payment gateways based on product constraints
			'WC_CSP_Restrict_Shipping_Methods', 	// Restrict shipping methods based on product constraints
			'WC_CSP_Restrict_Shipping_Countries', 	// Restrict shipping countries based on product constraints
		) );

		// Load cart restrictions.
		foreach ( $load_restrictions as $restriction ) {

			$restriction = new $restriction();

			$this->restrictions[ $restriction->id ] = $restriction;
		}

		// Validate add-to-cart.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 6 );

		// Validate cart.
		add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart' ), 10 );

		// Validate cart update.
		add_filter( 'woocommerce_update_cart_validation', array( $this, 'validate_cart_update' ), 10, 4 );

		// Validate checkout.
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 10 );


	}

	/**
	 * Get restriction class by restriction_id.
	 *
	 * @param  str    $restriction_id
	 * @return WC_CSP_Restriction
	 */
	public function get_restriction( $restriction_id ) {

		if ( ! empty( $this->restrictions[ $restriction_id ] ) ) {
			return $this->restrictions[ $restriction_id ];
		}

		return false;
	}

	/**
	 * Get all registered restrictions by supported validation type.
	 *
	 * @param  string $validation_type
	 * @return array
	 */
	public function get_restrictions( $validation_type = '' ) {

		$restrictions = array();

		foreach ( $this->restrictions as $id => $restriction ) {
			if ( $validation_type === '' || in_array( $validation_type, $restriction->get_validation_types() ) ) {
				$restrictions[ $id ] = $restriction;
			}
		}

		return apply_filters( 'woocommerce_csp_get_restrictions', $restrictions, $validation_type );
	}

	/**
	 * Get all registered restrictions that have admin product metabox options.
	 *
	 * @return array
	 */
	public function get_admin_product_field_restrictions() {

		$restriction_titles = array();

		foreach ( $this->restrictions as $id => $restriction ) {
			if ( $restriction->has_admin_product_fields() ) {
				$restriction_titles[ $id ] = $restriction;
			}
		}

		return apply_filters( 'woocommerce_csp_get_admin_product_field_restrictions', $restriction_titles );
	}

	/**
	 * Get all registered restrictions that have global settings.
	 *
	 * @return array
	 */
	public function get_admin_global_field_restrictions() {

		$restriction_titles = array();

		foreach ( $this->restrictions as $id => $restriction ) {
			if ( $restriction->has_admin_global_fields() ) {
				$restriction_titles[ $id ] = $restriction;
			}
		}

		return apply_filters( 'woocommerce_csp_get_admin_global_field_restrictions', $restriction_titles );
	}

	/**
	 * Add-to-cart validation ('woocommerce_add_to_cart_validation' filter) for all restrictions that implement the 'WC_CSP_Add_To_Cart_Restriction' interface.
	 *
	 * @param  bool   $add
	 * @param  int    $product_id
	 * @param  int    $product_quantity
	 * @param  string $variation_id
	 * @param  array  $variations
	 * @param  array  $cart_item_data
	 * @return bool
	 */
	public function validate_add_to_cart( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {

		$add_to_cart_restrictions = $this->get_restrictions( 'add-to-cart' );

		if ( ! empty( $add_to_cart_restrictions ) ) {

			foreach ( $add_to_cart_restrictions as $restriction ) {

				$result = $restriction->validate_add_to_cart();

				if ( $result->has_messages() ) {

					foreach ( $result->get_messages() as $message ) {
						wc_add_notice( $message[ 'text' ], $message[ 'type' ] );
					}

					$add = false;
				}
			}
		}

		return $add;
	}


	/**
	 * Cart validation ('check_cart_items' action) for all restrictions that implement the 'WC_CSP_Cart_Restriction' interface.
	 *
	 * @return void
	 */
	public function validate_cart() {

		$cart_restrictions = $this->get_restrictions( 'cart' );

		if ( ! empty( $cart_restrictions ) ) {

			foreach ( $cart_restrictions as $restriction ) {

				$result = $restriction->validate_cart();

				if ( $result->has_messages() ) {

					foreach ( $result->get_messages() as $message ) {
						wc_add_notice( $message[ 'text' ], $message[ 'type' ] );
					}
				}
			}
		}

	}

	/**
	 * Update cart validation ('update_cart_validation' filter) for all restrictions that implement the 'WC_CSP_Update_Cart_Restriction' interface.
	 *
	 * @param  bool   $passed
	 * @param  str    $cart_item_key
	 * @param  str    $cart_item_values
	 * @param  int    $quantity
	 * @return bool
	 */
	public function validate_cart_update( $passed, $cart_item_key, $cart_item_values, $quantity ) {

		$cart_update_restrictions = $this->get_restrictions( 'cart-update' );

		if ( ! empty( $cart_update_restrictions ) ) {

			foreach ( $cart_update_restrictions as $restriction ) {

				$result = $restriction->validate_cart_update( $passed, $cart_item_key, $cart_item_values, $quantity );

				if ( $result->has_messages() ) {

					foreach ( $result->get_messages() as $message ) {
						wc_add_notice( $message[ 'text' ], $message[ 'type' ] );
					}

					$passed = false;
				}
			}
		}

		return $passed;
	}

	/**
	 * Checkout validation ('woocommerce_after_checkout_validation' filter) for all restrictions that implement the 'WC_CSP_Checkout_Restriction' interface.
	 *
	 * @param  array  $posted
	 * @return void
	 */
	public function validate_checkout( $posted ) {

		$checkout_restrictions = $this->get_restrictions( 'checkout' );

		if ( ! empty( $checkout_restrictions ) ) {

			foreach ( $checkout_restrictions as $restriction ) {

				$result = $restriction->validate_checkout( $posted );

				if ( $result->has_messages() ) {

					foreach ( $result->get_messages() as $message ) {
						wc_add_notice( $message[ 'text' ], $message[ 'type' ] );
					}
				}
			}
		}
	}

	/**
	 * v1.0 to v1.1 update meta routine.
	 *
	 * @param  array  $data
	 * @param  string $scope
	 * @return array
	 */
	public function maybe_update_restriction_data( $data, $scope ) {

		if ( $data ) {

			if ( $scope === 'global' ) {
				foreach ( $data as $restriction_data_group_key => $restriction_data_group ) {

					foreach ( $restriction_data_group as $restriction_key => $restriction_data ) {

						if ( ! empty( $restriction_data[ 'conditions' ] ) ) {

							$conditions = $restriction_data[ 'conditions' ];
							$check      = current( $conditions );

							// Convert v1.0 to v1.1.
							if ( ! isset( $check[ 'condition_id' ] ) ) {
								$data[ $restriction_data_group_key ][ $restriction_key ][ 'conditions' ] = $this->update_condition_data( $conditions );
							} else {
								break;
							}
						}
					}
				}
			} elseif ( $scope === 'product' ) {
				foreach ( $data as $restriction_key => $restriction_data ) {

					if ( ! empty( $restriction_data[ 'conditions' ] ) ) {

						$conditions = $restriction_data[ 'conditions' ];
						$check      = current( $conditions );

						// Convert v1.0 to v1.1.
						if ( ! isset( $check[ 'condition_id' ] ) ) {
							$data[ $restriction_key ][ 'conditions' ] = $this->update_condition_data( $conditions );
						} else {
							break;
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * v1.0 to v1.1 update condition data routine.
	 *
	 * @param  array  $conditions
	 * @return array
	 */
	private function update_condition_data( $conditions ) {

		$updated_conditions = array();

		foreach ( $conditions as $condition_field => $condition_values ) {

			if ( $condition_field === 'cart_total_max' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'cart_total',
					'modifier'       => 'max',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'cart_total_min' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'cart_total',
					'modifier'       => 'min',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'pkg_weight_min' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'package_weight',
					'modifier'       => 'min',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'pkg_weight_max' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'package_weight',
					'modifier'       => 'max',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'order_total_max' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'order_total',
					'modifier'       => 'max',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'order_total_min' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'order_total',
					'modifier'       => 'min',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'countries' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'shipping_country',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
					'states'         => $condition_values[ 'states' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'billing_countries' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'billing_country',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'methods' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'shipping_method',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'categories' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'category_in_cart',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'package_categories' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'category_in_package',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'shipping_classes' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'shipping_class_in_cart',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'package_shipping_classes' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'shipping_class_in_package',
					'modifier'       => 'in',
					'value'          => $condition_values[ 'value' ],
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'quantity_min' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'cart_item_quantity',
					'modifier'       => 'min',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;

			} elseif ( $condition_field === 'quantity_max' && ! empty( $condition_values[ 'value' ] ) ) {
				$condition_content = array(
					'condition_id'   => 'cart_item_quantity',
					'modifier'       => 'max',
					'value'          => $condition_values[ 'value' ]
				);

				$updated_conditions[] = $condition_content;
			}
		}

		return $updated_conditions;
	}
}
