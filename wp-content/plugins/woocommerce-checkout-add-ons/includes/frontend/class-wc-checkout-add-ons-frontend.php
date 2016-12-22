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
 * Frontend class
 *
 * @since 1.0
 */
class WC_Checkout_Add_Ons_Frontend {


	/**
	 * Separator used between add-on name and selected/entered value
	 * in order review area
	 */
	private $label_separator = ' - ';

	/** Are we currently in checkout order review? **/
	private $is_checkout_order_review = false;


	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// Load frontend styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		// Add add-on fields to checkout fields
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_checkout_fields' ) );

		// Render add-on fields after customer details
		add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'render_add_ons' ) );

		// Add any selected add-ons as fees to cart
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees' ) );

		// Handle file uploads for the `file` add-on
		add_action( 'wp_ajax_wc_checkout_add_on_upload_file',        array( $this, 'upload_file' ) );
		add_action( 'wp_ajax_nopriv_wc_checkout_add_on_upload_file', array( $this, 'upload_file' ) );
		add_action( 'wp_ajax_wc_checkout_add_on_remove_file',        array( $this, 'remove_file' ) );
		add_action( 'wp_ajax_nopriv_wc_checkout_add_on_remove_file', array( $this, 'remove_file' ) );

		// Add support for radio, file and multichecbox form field types
		add_filter( 'woocommerce_form_field_wc_checkout_add_ons_multicheckbox',  array( $this, 'form_field' ), 11, 4 );
		add_filter( 'woocommerce_form_field_wc_checkout_add_ons_multiselect',    array( $this, 'form_field' ), 11, 4 );
		add_filter( 'woocommerce_form_field_wc_checkout_add_ons_radio',          array( $this, 'form_field' ), 11, 4 );
		add_filter( 'woocommerce_form_field_wc_checkout_add_ons_file',           array( $this, 'form_field' ), 11, 4 );

		// Save add-on meta on checkout
		add_action( 'woocommerce_add_order_fee_meta', array( $this, 'save_add_on_meta' ), 10, 3 );

		// Initialize session
		add_action( 'woocommerce_init', array( $this, 'init_session' ) );

		// Get correct checkout-add-on value
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'checkout_get_add_on_value' ), 10, 2 );

		// Clear session after checkout has been processed
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'clear_session' ) );

		// Handle displaying selected/entered add-on values in checkout order review area
		add_action( 'woocommerce_checkout_order_review', array( $this, 'indicate_checkout_order_review' ), 1 );
		add_action( 'woocommerce_after_checkout_form',   array( $this, 'clear_checkout_order_review_indicator' ) );
		add_filter( 'esc_html', array( $this, 'display_add_on_value_in_checkout_order_review' ), 10, 2 );

		// Handle displaying selected/entered add-on values in my-account/view-order screen
		add_filter( 'woocommerce_get_order_item_totals',                array( $this, 'append_order_add_on_fee_meta' ), 10, 2 );
		add_filter( 'woocommerce_get_order_item_totals_excl_free_fees', array( $this, 'include_free_order_add_on_fee_meta' ), 10, 2 );
	}


	/**
	 * Handle add-on file uploads
	 *
	 * @since 1.0
	 */
	public function upload_file() {

		check_ajax_referer( 'wc-checkout-add-ons-upload-file', 'security' );

		if ( empty( $_FILES ) || $_FILES['file']['error'] ) {
			return false;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );
		}

		$file_info = pathinfo( $_FILES['file']['name'] );

		$_FILES['file']['name'] = $file_info['filename'] . '-' . uniqid() . '.' . $file_info['extension'];

		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {

			echo json_encode( $attachment_id );

		} else {

			$this->store_uploaded_file_in_session( $attachment_id );

			echo json_encode( array(
				'id'    => $attachment_id,
				'title' => get_the_title( $attachment_id ) . '.' . strtolower( $file_info['extension'] ),
				'url'   => wp_get_attachment_url( $attachment_id ),
			) );
		}

		exit;
	}


	/**
	 * Handle add-on file removals
	 *
	 * @since 1.0
	 */
	public function remove_file() {

		check_ajax_referer( 'wc-checkout-add-ons-remove-file', 'security' );

		$file = $_POST['file'];

		// Bail out if no file
		if ( ! $file ) {
			return;
		}

		// Security: bail out if not uploaded in the same session
		if ( ! in_array( $file, WC()->session->checkout_add_ons['files'] ) ) {
			return;
		}

		// Delete the attachment
		wp_delete_attachment( $file, true );
		$this->remove_uploaded_file_from_session( $file );

		exit;
	}


	/**
	 * Loads frontend styles and scripts on checkout page
	 *
	 * @since 1.0
	 * @param array $add_on_fields array of add on field definitions
	 */
	public function load_styles_scripts( $add_on_fields ) {

		$load_styles_scripts = is_checkout();

		if ( ! $load_styles_scripts && function_exists( 'is_wcopc_checkout' ) ) {
			$load_styles_scripts =  is_wcopc_checkout();
		}

		/**
		 * Filter if Checkout Add-ons scripts and styles should be loaded
		 *
		 * @since 1.3.0
		 * @param bool $load_styles_scripts true if scripts and styles should be loaded; false otherwise
		 */
		if ( ! apply_filters( 'wc_checkout_add_ons_load_styles_scripts', $load_styles_scripts ) ) {
			return;
		}

		// Determine if we need to load Plupload
		$has_files = false;

		if ( ! empty( $add_on_fields ) ) {
			foreach ( $add_on_fields as $add_on_field ) {
				if ( 'wc_checkout_add_ons_file' == $add_on_field['type'] ) {
					$has_files = true;
				}
			}
		}

		if ( $has_files ) {
			wp_enqueue_script( 'plupload-all' );
		}

		// Register and load our styles and scripts
		wp_register_script( 'wc-checkout-add-ons-frontend', wc_checkout_add_ons()->get_plugin_url() . '/assets/js/frontend/wc-checkout-add-ons.min.js', array( 'jquery', 'plupload-all' ), WC_Checkout_Add_Ons::VERSION, true );

		wp_localize_script( 'wc-checkout-add-ons-frontend', 'wc_checkout_add_ons', array(
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'max_file_size'             => wp_max_upload_size(),
			'max_files'                 => 1,
			'mime_types'                => implode( ', ', get_allowed_mime_types() ),
			'upload_nonce'              => wp_create_nonce('wc-checkout-add-ons-upload-file'),
			'remove_nonce'              => wp_create_nonce('wc-checkout-add-ons-remove-file'),
			'chosen_placeholder_single' => __( 'Select an Option', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'chosen_placeholder_multi'  => __( 'Select Some Options', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'chosen_no_results_text'    => __( 'No results match', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
		) );

		wp_enqueue_script( 'wc-checkout-add-ons-frontend' );

		wp_enqueue_style( 'wc-checkout-add-ons-frontend', wc_checkout_add_ons()->get_plugin_url() . '/assets/css/frontend/wc-checkout-add-ons.min.css', array(), WC_Checkout_Add_Ons::VERSION );
	}


	/**
	 * Add add-on fields to checkout fields
	 *
	 * Adding add-on fields to checkout fields provides
	 * automatic validation and helps to keep our code
	 * more maintainable.
	 *
	 * @since 1.0
	 * @param array $checkout_fields associative array of field id to definition
	 * @return array associative array of field id to definition
	 */
	public function add_checkout_fields( $checkout_fields ) {

		$add_ons = wc_checkout_add_ons()->get_add_ons();
		$is_processing = defined( 'WOOCOMMERCE_CHECKOUT' );

		if ( ! empty( $add_ons ) ) {

			$checkout_fields['add_ons'] = array();

			foreach ( $add_ons as $add_on ) {

				switch ( $add_on->type ) {
					case 'file':

						$checkout_fields['add_ons'][ 'wc_checkout_add_ons_' . $add_on->id ] = array(
							'type'     => 'wc_checkout_add_ons_file',
							'label'    => $is_processing ? $add_on->name : $this->get_formatted_label( $add_on->name, $add_on->label, $add_on->get_cost_html() ),
							'required' => $add_on->is_required(),
						);
					break;

					case 'text':
					case 'textarea':
					case 'checkbox':

						$checkout_fields['add_ons'][ 'wc_checkout_add_ons_' . $add_on->id ] = array(
							'type'     => $add_on->type,
							'label'    => $is_processing ? $add_on->name : $this->get_formatted_label( $add_on->name, $add_on->label, $add_on->get_cost_html() ),
							'required' => $add_on->is_required(),
						);
					break;

					case 'select':
					case 'radio':

						$options = array();
						$default = null;

						foreach ( $add_on->get_options() as $option ) {

							$value           = wp_strip_all_tags( $this->get_formatted_label( null, $option['label'], $add_on->get_cost_html( $option['cost'] ) ) );
							$key             = sanitize_title( $option['label'] );

							$options[ $key ] = $value;

							if ( $option['selected'] ) {
								$default = $key;
							}
						}


						$checkout_fields['add_ons'][ 'wc_checkout_add_ons_' . $add_on->id ] = array(
							'type'     => 'radio' == $add_on->type ? 'wc_checkout_add_ons_radio' : $add_on->type,
							'label'    => $is_processing ? $add_on->name : $this->get_formatted_label( $add_on->name, $add_on->label ),
							'required' => $add_on->is_required(),
							'options'  => $options,
							'default'  => $default,
							'placeholder' => $default,
						);
					break;

					case 'multiselect':
					case 'multicheckbox':

						// Create special `wc_checkout_add_ons_multicheckbox` type for checkboxes with multiple options
						$options = array();
						$defaults = array();

						foreach ( $add_on->get_options() as $option ) {
							$value         = wp_strip_all_tags( $this->get_formatted_label( null, $option['label'], $add_on->get_cost_html( $option['cost'] ) ) );
							$key           = sanitize_title( $option['label'] );
							$options[ $key ] = $value;

							if ( $option['selected'] ) {
								$defaults[] = $key;
							}
						}

						$checkout_fields['add_ons'][ 'wc_checkout_add_ons_' . $add_on->id ] = array(
							'type'     => 'wc_checkout_add_ons_' . $add_on->type,
							'label'    => $is_processing ? $add_on->name : $this->get_formatted_label( $add_on->name, $add_on->label ),
							'required' => $add_on->is_required(),
							'options'  => $options,
							'default'  => $defaults,
						);
					break;
				}
			}

		}

		return $checkout_fields;
	}


	/**
	 * Add add-ons as fees to cart
	 *
	 * For file and text inputs, simply the cost is added.
	 * For selects and radio buttons, the cost of the selected option is added.
	 * For checkboxes, the cost of each checked option is added.
	 *
	 * @since 1.0
	 * @param object $cart
	 */
	public function add_cart_fees( $cart ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) || ! $_POST ) {
			return;
		}

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'] );
		}

		$add_ons = wc_checkout_add_ons()->get_add_ons();

		foreach ( $add_ons as $add_on ) {

			$id    = esc_attr( $add_on->id );
			$name  = __( $add_on->name, WC_Checkout_Add_Ons::TEXT_DOMAIN );
			$field = WC_Checkout_Add_Ons::PLUGIN_PREFIX . $id;
			$value = isset( $$field ) ? $$field : ( isset( $_POST[ $field ] ) ? $_POST[ $field ] : null );

			switch ( $add_on->type ) {

				case 'text':
				case 'textarea':
				case 'checkbox':
				case 'file':
					if ( $value ) {
						$cost      = $add_on->get_cost();
						$taxable   = $add_on->is_taxable();
						$tax_class = $add_on->get_tax_class();

						WC()->cart->add_fee( $name, $cost, $taxable, $tax_class );

						if ( in_array( $add_on->type, array( 'text', 'textarea' ) ) ) {
							$value = stripslashes( $value );
						}

						$this->store_add_on_in_session( $field, $id, $name, $value );
					}
				break;

				case 'select':
				case 'radio':
					if ( $value ) {

						foreach ( $add_on->get_options() as $option ) {

							$key = sanitize_title( $option['label'] );

							if ( $value == $key ) {

								$cost      = $option['cost'];
								$taxable   = $add_on->is_taxable();
								$tax_class = $add_on->get_tax_class();

								WC()->cart->add_fee( $name, $cost, $taxable, $tax_class );

								$this->store_add_on_in_session( $field, $id, $name, $value );
							}

						}
					}
				break;

				case 'multiselect':
				case 'multicheckbox':

					$has_value = false;
					$cost = 0;
					$value = is_array( $value ) ? $value : array( $value );

					foreach ( $add_on->get_options() as $option ) {

						$key = sanitize_title( $option['label'] );

						if ( in_array( $key, $value ) ) {

							$has_value = true;
							$cost += $option['cost'];
						}
					}

					if ( $has_value ) {

						$taxable   = $add_on->is_taxable();
						$tax_class = $add_on->get_tax_class();

						WC()->cart->add_fee( $name, $cost, $taxable, $tax_class );

						$this->store_add_on_in_session( $field, $id, $name, $value );
					} else {
						// Set value to null to make sure that if there is no value, the add-on is removed from session
						$value = null;
					}
				break;
			}

			// Remove add-on from session if it has no value
			if ( ! $value ) {
				$this->remove_add_on_from_session( $field, $name );
			}
		}
	}


	/**
	 * Get formatted label, using $label if set, otherwise $name. Includes
	 * $cost if provided
	 *
	 * @since 1.0
	 * @param string $name field name
	 * @param string $label optional descriptive field label (default: empty string)
	 * @param string $cost optional field cost (default: empty string)
	 * @return string formatted label field
	 */
	public function get_formatted_label( $name, $label = '', $cost = '' ) {
		return ( $label ? esc_html( $label ) : esc_html( $name ) ) . ( $cost ? ' (' . $cost . ')' : '' );
	}


	/**
	 * Render add-ons after customer details
	 *
	 * @since 1.0
	 */
	public function render_add_ons() {

		$checkout_add_on_fields = isset( WC()->checkout()->checkout_fields['add_ons'] ) ? WC()->checkout()->checkout_fields['add_ons'] : null;

		// load the template
		wc_get_template(
			'checkout/add-ons.php',
			array(
				'add_on_fields' => $checkout_add_on_fields,
			),
			'',
			wc_checkout_add_ons()->get_plugin_path() . '/templates/'
		);
	}


	/**
	 * Support radio, file and multicheckbox field types in woocommerce_form_field
	 *
	 * @since 1.0
	 * @param string $field
	 * @param string $key
	 * @param array $args
	 * @param mixed $value
	 * @return string form field markup
	 */
	public function form_field( $field, $key, $args, $value ) {

		if ( ( ! empty( $args['clear'] ) ) ) {
			$after = '<div class="clear"></div>';
		} else {
			$after = '';
		}

		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required = ' <abbr class="required" title="' . esc_attr__( 'required', WC_Checkout_Add_Ons::TEXT_DOMAIN  ) . '">*</abbr>';
		} else {
			$required = '';
		}

		// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		switch ( $args['type'] ) {
			case "wc_checkout_add_ons_radio" :

				if ( ! empty( $args['options'] ) ) {
					$field .= '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) .'" id="' . esc_attr( $key ) . '_field">';

					if ( $args['label'] ) {
						$field .= '<label for="' . esc_attr( current( array_keys( $args['options'] ) ) ) . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required  . '</label>';
					}

					foreach ( $args['options'] as $option_key => $option_text ) {

						$field .= '<input type="radio" class="input-checkbox" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';

						$field .= '<label for="' . esc_attr( $key ) . '_' . esc_attr( $option_key ) . '" class="checkbox ' . implode( ' ', $args['label_class'] ) .'">' . $option_text . '</label><br>';

					}
					$field .= '</p>' . $after;
				}

			break;

			case "wc_checkout_add_ons_multiselect" :

				$value = is_array( $value ) ? $value : array( $value );

				if ( ! empty( $args['options'] ) ) {

					$options = '';
					foreach ( $args['options'] as $option_key => $option_text ) {

						$options .= '<option value="' . esc_attr( $option_key ) . '" '. selected( in_array( $option_key, $value ), 1, false ) . '>' . esc_attr( $option_text ) .'</option>';
					}

					$field = '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) .'" id="' . esc_attr( $key ) . '_field">';

					if ( $args['label'] ) {
						$field .= '<label for="' . esc_attr( $key ) . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required . '</label>';
					}

					$field .= '<select name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $key ) . '" class="select" multiple="multiple" ' . implode( ' ', $custom_attributes ) . '>'
							. $options
							. ' </select></p>'
							. $after;
				}

			break;

			case "wc_checkout_add_ons_multicheckbox" :

				$value = is_array( $value ) ? $value : array( $value );

				if ( ! empty( $args['options'] ) ) {
					$field .= '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) .'" id="' . esc_attr( $key ) . '_field">';

					if ( $args['label'] ) {
						$field .= '<label for="' . esc_attr( current( array_keys( $args['options'] ) ) ) . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required  . '</label>';
					}

					foreach ( $args['options'] as $option_key => $option_text ) {

						$field .= '<input type="checkbox" class="input-checkbox" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $key ) . '_' . esc_attr( $option_key ) . '"' . checked( in_array( $option_key, $value ), 1, false ) . ' />';
						$field .= '<label for="' . esc_attr( $key ) . '_' . esc_attr( $option_key ) . '" class="checkbox ' . implode( ' ', $args['label_class'] ) .'">' . $option_text . '</label><br>';
					}

					$field .= '</p>' . $after;
				}

			break;

			case "wc_checkout_add_ons_file" :

				$field .= '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) .'" id="' . esc_attr( $key ) . '_field">';

				if ( $args['label'] ) {
					$field .= '<label for="' . esc_attr( current( array_keys( $args['options'] ) ) ) . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required  . '</label>';
				}

				$url   = '';
				$title = '';

				// Value == attachment ID
				if ( $value ) {
					if ( ! in_array( $value, WC()->session->checkout_add_ons['files'] ) ) {
						// The file was not uploaded in the current session. Clear value
						$value = '';
					} else {
						// Get the url and title for the provided attachment
						$url   = wp_get_attachment_url( $value );
						$title = get_the_title( $value );
					}
				}

				$field .= '<div class="input-file-plupload ' . implode( ' ', $args['input_class'] ) .'">';

				$field .= '<a class="dropzone ' . ( $url ? 'hide' : '' ) . '">';
				$field .= __( 'Drag file here or click to upload', WC_Checkout_Add_Ons::TEXT_DOMAIN );
				$field .= '<div class="progress hide"><div class="bar"></div></div>';
				$field .='</a>';

				$field .= '<div class="preview ' . ( ! $url ? 'hide' : '' ) . '">';
				$field .= '<a href="' . $url . '" class="file">' . $title  . '</a>';
				$field .= '<a href="#" class="remove-file">' . __( 'Remove', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . '</a>';
				$field .= '</div>';

				$field .= '<div class="feedback hide"></div>';
				$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . $value . '" />';
				$field .= '<noscript>' . __( 'You need to enable Javascript to upload files', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . '</noscript>';
				$field .= '</div>';

				$field .= '</p>' . $after;

			break;
		}

		return $field;
	}


	/**
	 * Initialize checkout add-ons session
	 *
	 * Gives us an array in WooCommerce session where
	 * we can store state about the currently selected
	 * add-ons as well as cart fees and add-on relations.
	 *
	 * @since 1.0
	 */
	public function init_session() {
		if ( isset( WC()->session ) && ! WC()->session->checkout_add_ons ) {
			WC()->session->checkout_add_ons = array(
				'fields' => array(),
				'fees'   => array(),
				'files'  => array(),
			);
		}
	}


	/**
	 * Get add-on checkout value
	 *
	 * @since 1.0
	 * @param mixed $value
	 * @param string $input
	 * @return mixed
	 */
	public function checkout_get_add_on_value( $value, $input ) {

		// Get add-on value from session
		if ( isset( WC()->session->checkout_add_ons['fields'][ $input ] ) ) {
			$value = WC()->session->checkout_add_ons['fields'][ $input ]['value'];
		}

		return $value;
	}


	/**
	 * Store add-on value and related fee in session
	 *
	 * @since 1.0
	 * @param string $field name used in checkout
	 * @param int $id of the add-on
	 * @param string $name localized name of the add-on
	 * @param mixed $value add-on value
	 */
	public function store_add_on_in_session( $field, $id, $name, $value ) {

		$session_data = WC()->session->checkout_add_ons;

		// Store add-on data with field as key for easy lookup on checkout_get_add_on_value
		$data = array( 'id' => $id, 'value' => $value );
		$session_data['fields'][ $field ] = $session_data['fees'][ sanitize_title( $name ) ] = $data;

		WC()->session->checkout_add_ons = $session_data;
	}


	/**
	 * Remove add-on data from session
	 *
	 * @since 1.0
	 * @param string $field name used in checkout
	 */
	public function remove_add_on_from_session( $field, $name ) {

		$session_data = WC()->session->checkout_add_ons;

		if ( isset( $session_data['fields'][ $field ] ) ) {
			unset( $session_data['fields'][ $field ] );
		}

		$name = sanitize_title( $name );

		if ( isset( $session_data['fees'][ $name ] ) ) {
			unset( $session_data['fees'][ $name ] );
		}

		WC()->session->checkout_add_ons = $session_data;
	}


	/**
	 * Store a reference to the uploaded file in session
	 *
	 * @since 1.0
	 * @param string $file attachment title
	 */
	public function store_uploaded_file_in_session( $file ) {

		$session_data = WC()->session->checkout_add_ons;

		$session_data['files'][] = $file;

		WC()->session->checkout_add_ons = $session_data;
	}


	/**
	 * Remove reference to the uploaded file from session
	 *
	 * @since 1.0
	 * @param string $file attachment title
	 */
	public function remove_uploaded_file_from_session( $file ) {

		$session_data = WC()->session->checkout_add_ons;

		$key = array_search( $file, $session_data['files'] );
		if ( $key !== false ) {
			unset( $session_data['files'][ $key ] );
		}

		WC()->session->checkout_add_ons = $session_data;
	}


	/**
	 * Clear WC Checkout Add-Ons data from session
	 *
	 * @since 1.0
	 */
	public function clear_session() {
		unset( WC()->session->checkout_add_ons );
	}


	/**
	 * Store the fact that checkout order review is being displayed
	 *
	 * This helps us to limit the use of esc_html filter for appending
	 * add-on values to names in checkout order review area
	 *
	 * @since 1.0
	 */
	public function indicate_checkout_order_review() {
		$this->is_checkout_order_review = true;
	}


	/**
	 * Indicate that we are not in order review area anymore
	 *
	 * @since 1.0
	 */
	public function clear_checkout_order_review_indicator() {
		$this->is_checkout_order_review = false;
	}


	/**
	 * Display add-on values in order review area
	 *
	 * Works by filtering the esc_html'ed name of the add-on/fee
	 * and appending the add-on value to the name for display
	 * purposes only
	 *
	 * @since 1.0
	 * @param string $safe_text
	 * @param string $text
	 * @return string $safe_text
	 */
	public function display_add_on_value_in_checkout_order_review( $safe_text, $text ) {

		// Bail out if not in checkout order review area
		if ( ! $this->is_checkout_order_review ) {
			return $safe_text;
		}

		if ( isset( WC()->session->checkout_add_ons['fees'][ sanitize_title( $text ) ] ) ) {

			$session_data = WC()->session->checkout_add_ons['fees'][ sanitize_title( $text ) ];

			// Get add-on value from session and set it for add-on
			$add_on = wc_checkout_add_ons()->get_add_on( $session_data['id'] );

			// Format add-on value
			$value = $add_on->normalize_value( $session_data['value'], true );

			// Append value to add-on name
			if ( $value ) {

				if ( 'text' == $add_on->type || 'textarea' == $add_on->type ) {
					$value = $add_on->truncate_label( $value );
				}

				$safe_text .= $this->label_separator . $value;
			}
		}

		return $safe_text;
	}


	/**
	 * Save meta data for add-on fees
	 *
	 * @since 1.0
	 * @param string $order_id the order identifier
	 * @param string $item_id the order item identifier
	 * @param object $fee fee object
	 */
	public function save_add_on_meta( $order_id, $item_id, $fee ) {

		if ( isset( WC()->session->checkout_add_ons['fees'][ $fee->id ] ) ) {

			$session_data = WC()->session->checkout_add_ons['fees'][ $fee->id ];

			// Get add-on value(s) from session
			$add_on = wc_checkout_add_ons()->get_add_on( $session_data['id'] );
			$value = $session_data['value'];

			// Sanitize add-on value(s) for saving
			$value = is_array( $value ) ? array_map( 'wc_clean', $value ) : wc_clean( $value );

			if ( 'file' == $add_on->type ) {

				// Files get no label - it will be generated when displayed
				$label = '';

				$files = explode( ',', $value );

				foreach ( $files as $file_id )	{

					// Attach the file to the order
					wp_update_post( array(
						'ID'          => $file_id,
						'post_parent' => $order_id,
					) );

				}

			} else {

				// Label(s) - for select, radio, etc
				$label = $add_on->normalize_value( $value, false );
			}

			// Save add-on meta: id, label(s), value(s)
			wc_add_order_item_meta( $item_id, '_wc_checkout_add_on_id', $add_on->id );
			wc_add_order_item_meta( $item_id, '_wc_checkout_add_on_value', $value );
			wc_add_order_item_meta( $item_id, '_wc_checkout_add_on_label', $label );
		}
	}


	/**
	 * Add checkout add-on meta to order row label for display purposes in
	 * my-account/view-order and order emails.
	 *
	 * @since 1.0
	 * @param array $total_rows
	 * @param object $order
	 * @return array $total_rows
	 */
	public function append_order_add_on_fee_meta( $total_rows, $order ) {

		foreach ( $total_rows as $row_key => $row ) {

			$parts = explode( '_', $row_key );
			$item_type = $parts[0];
			$item_id = isset( $parts[1] ) ? $parts[1] : null;

			if ( 'fee' == $item_type ) {

				$add_on_id = wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_id' );

				if ( $add_on_id ) {

					$value = wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_value' );
					$label = wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_label' );

					// Get label (link) for file add-on
					if ( ! $label ) {

						$add_on = wc_checkout_add_ons()->get_add_on( $add_on_id );

						if ( 'file' == $add_on->type ) {

							$label = $add_on->normalize_value( $value, false );
						}
					}

					if ( $label ) {
						$total_rows[ $row_key ]['label'] .= $this->label_separator . ( is_array( $label ) ? implode( ', ', $label ) : $label );
					}
				}
			}
		}

		return $total_rows;
	}


	/**
	 * Include free order add-on fee meta
	 *
	 * @since 1.0
	 * @param bool $excl_free true is free item meta should be excluded
	 * @param int $item_id the item meta id
	 * @return bool $excl_free
	 */
	public function include_free_order_add_on_fee_meta( $excl_free, $item_id ) {

		$excl_free = wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_id' ) ? false : $excl_free;

		return $excl_free;
	}


} // end \WC_Checkout_Add_Ons_Frontend class
