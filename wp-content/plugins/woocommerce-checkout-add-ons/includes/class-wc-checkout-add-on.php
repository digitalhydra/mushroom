<?php
/**
 * WooCommerce Checkout Add-Ons
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Checkout Add-Ons to newer
 * versions in the future. If you wish to customize WooCommerce Checkout Add-Ons for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-checkout-add-ons/ for more information.
 *
 * @package     WC-Checkout-Add-Ons/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Checkout Add-On class
 *
 * @since 1.0
 */
class WC_Checkout_Add_On {


	/** @var array the add-on raw data */
	private $data;

	/** @var string the add-on ID */
	private $id;

	/** @var boolean have we run the add-on options filter already? */
	private $has_run_add_on_options_filter = false;


	/**
	 * Setup the add-on
	 *
	 * @since 1.0
	 * @param int $id the add-on ID
	 * @param array $data the add-on raw data
	 * @return \WC_Checkout_Add_On
	 */
	public function __construct( $id, array $data ) {

		$this->id   = $id;
		$this->data = $data;
	}


	/**
	 * Magic method for getting add-on properties
	 *
	 * @since 1.0
	 * @param string $key the class member name
	 * @return mixed
	 */
	public function __get( $key ) {

		switch ( $key ) {

			case 'id':
				return $this->id;

			case 'name':
				return $this->data['name'];

			case 'label':
				return $this->data['label'];

			case 'type':
				return $this->data['type'];

			case 'cost':
				return isset( $this->data['cost'] ) ? $this->data['cost'] : null;

			case 'required':
				return $this->is_required();

			case 'listable':
				return $this->is_listable();

			case 'sortable':
				return $this->is_sortable();

			case 'filterable':
				return $this->is_filterable();

			case 'taxable':
				return $this->is_taxable();

			case 'tax_class':
				return $this->get_tax_class();

			default:
				return null;
		}
	}


	/**
	 * Magic method for checking if add-on properties are set
	 *
	 * @since 1.0
	 * @param string $key the class member name
	 * @return bool
	 */
	public function __isset( $key ) {

		switch( $key ) {

			// add-on properties are always set
			case 'required':
			case 'visible':
			case 'taxable':
			case 'listable':
			case 'sortable':
			case 'filterable':
				return true;

			case 'value':
				return isset( $this->value );

			default:
				return isset( $this->data[ $key ] );
		}
	}


	/**
	 * Normalize the add-on value
	 *
	 * Provided the value(s), looks up the proper label(s) and returns them
	 * as a comma-separated string or an array
	 *
	 * @since 1.0
	 * @param string|array $value sanitized key or array of keys
	 * @param bool $implode whether to glue labels together with commas
	 * @return mixed string|array label or array of labels matching the value
	 */
	public function normalize_value( $value, $implode ) {

		switch ( $this->type ) {

			case 'multiselect':
			case 'multicheckbox':

				$label = array();
				$options = $this->get_options();

				foreach ( (array) $value as $selected_option ) {
					foreach ( $options as $option ) {
						if ( sanitize_title( $selected_option ) == sanitize_title( $option['label'] ) ) {

							$label[] = $option['label'];
						}
					}
				}
			break;

			case 'radio':
			case 'select':

				$label = '';

				$options = $this->get_options();

				foreach ( $options as $option ) {
					if ( sanitize_title( $value ) == sanitize_title( $option['label'] ) ) {
						$label = $option['label'];
						break;
					}
				}
			break;

			// No label for simple checkboxes with no options
			case 'checkbox':
				$label = '';
			break;

			// Return a link for files
			case 'file':

				$file_ids = explode( ',', $value );

				// Multiple files
				if ( count( $file_ids ) > 1 ) {

					$label = __( 'Uploaded files:', WC_Checkout_Add_Ons::TEXT_DOMAIN );
					$file_labels = array();

					foreach ( $file_ids as $key => $file_id ) {

						if ( $url = wp_get_attachment_url( $file_id ) ) {
							$file_labels[] = '<a href="' . $url . '">' . sprintf( __( 'File %d', WC_Checkout_Add_Ons::TEXT_DOMAIN ), $key + 1 ) . '</a>';
						} else {
							$file_labels[] = __( '(File has been removed)', WC_Checkout_Add_Ons::TEXT_DOMAIN );
						}
					}

					$label .= implode( ', ', $file_labels );
				} else {

					// Single file
					if ( $url = wp_get_attachment_url( $file_ids[0] ) ) {
						$label = '<a href="' . $url . '">' . __( 'Uploaded file', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . '</a>';
					}

					else
						$label = __( '(File has been removed)', WC_Checkout_Add_Ons::TEXT_DOMAIN );
				}
			break;

			default:
				$label = $value;

		}

		return is_array( $label ) && $implode ? implode( ', ', $label ) : $label;
	}


	/**
	 * Truncate label for display
	 *
	 * @since 1.0
	 * @param  string $label sanitized key or array of keys
	 * @return string truncated label
	 */
	public function truncate_label( $label ) {

		/**
		 * Filter the label length
		 *
		 * @since 1.0
		 * @param int $label_length The length of the truncated label.
		 */
		$label_length = apply_filters( 'wc_checkout_add_ons_add_on_label_length', 140 );

		/**
		 * Filter the label trim marker
		 *
		 * @since 1.0
		 * @param string $label_more The string that is added to the end of the label.
		 */
		$label_trimmarker = apply_filters( 'wc_checkout_add_ons_add_on_label_trimmarker', ' [&hellip;]');

		if ( $label_length < strlen( $label ) ) {
			$label = substr( $label, 0, $label_length ) . $label_trimmarker;
		}

		return $label;
	}


	/**
	 * Returns the add-on cost
	 *
	 * @since 1.0
	 * @return mixed the add-on cost
	 */
	public function get_cost() {

		$cost = $this->cost || is_numeric( $this->cost ) && $this->cost == 0 ? $this->cost : '';

		/**
		 * Filter the add-on cost.
		 *
		 * @since 1.0
		 * @param mixed $cost The add-on cost.
		 * @param object $add_on This instance of WC_Checkout_Add_On class.
		 */
		return apply_filters( 'wc_checkout_add_ons_add_on_get_cost', $cost, $this );
	}


	/**
	 * Returns the add-on cost (including tax)
	 *
	 * @since 1.0
	 * @param string $cost Optional. cost to calculate, leave blank to just use get_cost()
	 * @return mixed the add-on cost including any taxes
	 */
	public function get_cost_including_tax( $cost = null ) {
		$_tax  = new WC_Tax();

		if ( ! isset( $cost ) ) {
			$cost = $this->get_cost();
		}

		if ( $this->is_taxable() ) {

			// Get tax rates
			$tax_rates    = $_tax->get_rates( $this->get_tax_class() );
			$add_on_taxes = $_tax->calc_tax( $cost, $tax_rates, false );

			// add tax totals to the cost
			if ( ! empty( $add_on_taxes ) ) {
				$cost += array_sum( $add_on_taxes );
			}
		}

		return $cost;
	}


	/**
	 * Returns the cost in html format.
	 *
	 * Returns the formatted cost for the add-on, either with or
	 * without taxes, based on the `tax_display_cart` option.
	 *
	 * @access public
	 * @param string $cost Optional. Cost to use (default: $this->get_cost())
	 * @return string
	 */
	public function get_cost_html( $cost = null ) {

		if ( $cost === null ) {
			$cost = $this->get_cost();
		}

		$display_cost = 'incl' == WC()->cart->tax_display_cart ? $this->get_cost_including_tax( $cost ) : $cost;

		if ( $cost > 0 ) {

			$cost_html = wc_price( $display_cost );

			/**
			 * Filter the positive add-on cost html.
			 *
			 * @since 1.0
			 * @param string $cost_html The positive add-on cost html.
			 * @param object $add_on This instance of WC_Checkout_Add_On class.
			 */
			$cost_html = apply_filters( 'wc_checkout_add_on_cost_html', $cost_html, $this );

		} elseif ( $cost === '' ) {

			/**
			 * Filter the empty add-on cost html.
			 *
			 * @since 1.0
			 * @param string $cost_html The empty add-on cost html.
			 * @param object $add_on This instance of WC_Checkout_Add_On class.
			 */
			$cost_html = apply_filters( 'wc_checkout_add_on_empty_cost_html', '', $this );

		} elseif ( $cost == 0 ) {

			$cost_html = __( 'Free!', WC_Checkout_Add_Ons::TEXT_DOMAIN );

			/**
			 * Filter the free add-on cost html.
			 *
			 * @since 1.0
			 * @param string $cost_html The free add-on cost html.
			 * @param object $add_on This instance of WC_Checkout_Add_On class.
			 */
			$cost_html = apply_filters( 'woocommerce_free_cost_html', $cost_html, $this );
		}

		/**
		 * Filter the add-on cost html.
		 *
		 * @since 1.0
		 * @param string $cost_html The add-on cost html.
		 * @param object $add_on This instance of WC_Checkout_Add_On class.
		 */
		return apply_filters( 'wc_checkout_add_on_get_cost_html', $cost_html, $this );
	}


	/**
	 * Check if the add-on has any options
	 *
	 * Checks if get_options() returns an array with at least
	 * one item. This ensures that other plugins can tap in and
	 * add options even if there are no manually configured options.
	 *
	 * @since 1.0
	 * @return bool true if the add-on has any options
	 */
	public function has_options() {

		return count( $this->get_options() ) > 0;
	}


	/**
	 * Returns the options for the select, multiselect, radio and checkbox types.
	 * If no value has been set, items are marked as selected according to any
	 * configured defaults.
	 *
	 * @since 1.0
	 * @return array of arrays containing 'default', 'selected', 'label', 'value', 'cost' keys
	 */
	public function get_options() {

		// configured options
		$options = isset( $this->data['options'] ) && $this->data['options'] ? $this->data['options'] : array();

		// allow other plugins to hook in and supply their own options, but only run this filter once to avoid duplicate intensive operations
		if ( ! $this->has_run_add_on_options_filter ) {

			/**
			 * Filter the options for the select, multiselect, radio and checkbox types.
			 *
			 * @since 1.0
			 * @param array $options The add-on's options.
			 * @param object $add_on This instance of WC_Checkout_Add_On class.
			 */
			$this->data['options'] = $options = apply_filters( 'wc_checkout_add_ons_options', $options, $this );
			$this->has_run_add_on_options_filter = true;
		}

		// set default values
		foreach ( $options as $key => $option ) {

			if ( $option['default'] ) {
				$options[ $key ]['selected'] = true;
			} else {
				$options[ $key ]['selected'] = false;
			}
		}

		// add an empty option for non-required select
		if ( ! $this->is_required() && ( 'select' === $this->type ) ) {
			array_unshift( $options, array( 'default' => false, 'label' => '', 'value' => '', 'cost' => '', 'selected' => false ) );
		}

		return $options;
	}


	/**
	 * Returns the tax class for this add-on
	 *
	 * @since 1.0
	 * @return string|int tax class
	 */
	public function get_tax_class() {

		/**
		 * Filter the the tax class for add-on.
		 *
		 * @since 1.0
		 * @param string|int $tax_class The add-on's tax class.
		 * @param object $add_on This instance of WC_Checkout_Add_On class.
		 */
		return apply_filters( 'wc_checkout_add_ons_add_on_tax_class', isset( $this->data['tax_class'] ) ? $this->data['tax_class'] : 0, $this );
	}


	/**
	 * Get the key for this add-on
	 *
	 * @return string add-on key
	 */
	public function get_key() {
		return WC_Checkout_Add_Ons::PLUGIN_PREFIX . $this->id;
	}


	/**
	 * Returns true if this is a required add-on, false otherwise
	 *
	 * @since 1.0
	 * @return bool true if this add-on is required, false otherwise
	 */
	public function is_required() {

		return isset( $this->data['required'] ) && $this->data['required'];
	}


	/**
	 * Returns true if this is a taxable add-on, false otherwise
	 *
	 * @since 1.0
	 * @return bool true if this add-on is taxable, false otherwise
	 */
	public function is_taxable() {

		return isset( $this->data['tax_status'] ) && $this->data['tax_status'] == 'taxable';
	}


	/**
	 * Returns true if this add-on is visible to the customer (in order emails/my account > order views), false otherwise
	 *
	 * @since 1.0
	 * @return bool true if this add-on is visible, false otherwise
	 */
	public function is_visible() {

		return isset( $this->data['visible'] ) && $this->data['visible'];
	}


	/**
	 * Returns true if this add-on should be displayed in the Order admin
	 * list
	 *
	 * @since 1.0
	 * @return bool true if the add-on should be displayed in the orders list
	 */
	public function is_listable() {

		return isset( $this->data['listable'] ) && $this->data['listable'];
	}


	/**
	 * Returns true if this listable add-on is also sortable
	 *
	 * @since 1.0
	 * @return bool true if the add-on should be sortable in the orders list
	 */
	public function is_sortable() {

		return $this->is_listable() && isset( $this->data['sortable'] ) && $this->data['sortable'];
	}


	/**
	 * Returns true if this listable add-on is also filterable in the
	 * Orders admin
	 *
	 * @since 1.0
	 * @return bool true if the add-on is both listable and filterable
	 */
	public function is_filterable() {

		return $this->is_listable() && isset( $this->data['filterable'] ) && $this->data['filterable'];
	}


} // end \WC_Checkout_Add_On class
