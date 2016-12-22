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
 * Admin class
 *
 * @since 1.0
 */
class WC_Checkout_Add_Ons_Admin {


	/** @var string page suffix ID */
	public $page_id;

	/** @var WC_Checkout_Add_Ons_Shop_Order_CPT instance */
	public $cpt;


	/**
	 * Init the class
	 *
	 * @since 1.0
	 * @return \WC_Checkout_Add_Ons_Admin
	 */
	public function __construct() {

		/**
		 * Filter the valid add-on types.
		 *
		 * @since 1.0
		 * @param array $add_on_types The valid add-on types.
		 */
		$this->add_on_types = apply_filters( 'wc_checkout_add_ons_add_on_types', array(
			'text'          => __( 'Text', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'textarea'      => __( 'Text Area', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'select'        => __( 'Select', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'multiselect'   => __( 'Multiselect', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'radio'         => __( 'Radio', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'checkbox'      => __( 'Checkbox', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'multicheckbox' => __( 'Multi-checkbox', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'file'          => __( 'File', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
		) );

		/**
		 * Filter the valid add-on attributes.
		 *
		 * @since 1.0
		 * @param array $add_on_attributes The valid add-on attributes.
		 */
		$this->add_on_attributes = apply_filters( 'wc_checkout_add_ons_add_on_attributes', array(
			'required'   => __( 'Required', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'listable'   => __( 'Display in View Orders screen', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'sortable'   => __( 'Allow Sorting on View Orders screen', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'filterable' => __( 'Allow Filtering on View Orders screen', WC_Checkout_Add_Ons::TEXT_DOMAIN )
		) );

		// load view order list table / edit order screen customizations
		require_once( 'post-types/class-wc-checkout-add-ons-shop-order-cpt.php' );
		$this->cpt = new WC_Checkout_Add_Ons_Shop_Order_CPT();

		// load styles/scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		// load WC styles / scripts on editor screen
		add_filter( 'woocommerce_screen_ids', array( $this, 'load_wc_scripts' ) );

		// add 'checkout add-ons' link under WooCommerce menu
		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );
	}


	/**
	 * Load admin styles and scripts
	 *
	 * @since 1.0
	 * @param string $hook_suffix the current URL filename, ie edit.php, post.php, etc
	 */
	public function load_styles_scripts( $hook_suffix ) {
		global $post_type, $wp_scripts;

		// load admin css only on view orders / edit order screens
		if ( ( 'shop_order' == $post_type && in_array( $hook_suffix, array( 'edit.php', 'post.php', 'post-new.php' ) ) ) || $this->page_id === $hook_suffix ) {

			// admin CSS
			wp_enqueue_style( 'wc-checkout-add-ons-admin', wc_checkout_add_ons()->get_plugin_url() . '/assets/css/admin/wc-checkout-add-ons.min.css', array( 'woocommerce_admin_styles' ), WC_Checkout_Add_Ons::VERSION );

			// admin JS
			wp_enqueue_script( 'wc-checkout-add-ons-admin', wc_checkout_add_ons()->get_plugin_url() . '/assets/js/admin/wc-checkout-add-ons.min.js', array( 'jquery', 'jquery-ui-sortable', 'woocommerce_admin' ), WC_Checkout_Add_Ons::VERSION );

			$params = array(
				'new_row'                             => str_replace( array( "\n", "\t" ), '', $this->get_row_html() ),
				'name_required_text'                  => __( 'Name is a required field', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
				'option_required_text'                => __( 'A select/multiselect/checkbox/radio field must have at least one option', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
				'options_costs_placeholder_text'      => __( 'Pipe (|) separates options', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
				'options_costs_placeholder_text_cost' => __( 'Cost of add-on', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
				'chosen_placeholder_single'           => __( 'Select an Option', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
				'chosen_placeholder_multi'            => __( 'Select Some Options', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
				'chosen_no_results_text'              => __( 'No results match', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			);

			// add HTML for adding new fields
			wp_localize_script( 'wc-checkout-add-ons-admin', 'wc_checkout_add_ons_params', $params );

			// Load jQuery UI only on editor screen
			if ( $this->page_id === $hook_suffix ) {

				// get jQuery UI version
				$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

				// enqueue UI CSS
				wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
			}
		}
	}


	/**
	 * Add  screen ID to the list of pages for WC to load its JS on
	 *
	 * @since 1.0
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_scripts( $screen_ids ) {

		// sub-menu page screen ID
		$screen_ids[] = 'woocommerce_page_wc_checkout_add_ons';

		return $screen_ids;
	}


	/**
	 * Add 'Order add-ons' sub-menu link under 'WooCommerce' top level menu
	 *
	 * @since 1.0
	 */
	public function add_menu_link() {

		$this->page_id = add_submenu_page(
			'woocommerce',
			__( 'Checkout Add-Ons', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			__( 'Checkout Add-Ons', WC_Checkout_Add_Ons::TEXT_DOMAIN ),
			'manage_woocommerce',
			'wc_checkout_add_ons',
			array( $this, 'render_editor_screen' )
		);
	}


	/**
	 * Render the checkout add-ons editor
	 *
	 * @since 1.0
	 */
	public function render_editor_screen() {

		?>
		<div class="wrap woocommerce">
			<form method="post" id="mainform" action="" enctype="multipart/form-data" class="wc-checkout-add-ons">
				<div id="icon-woocommerce" class="icon32"><br /></div>
				<h2><?php _e( 'Checkout Add-Ons Editor', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?></h2> <?php

				// save add-ons
				if ( ! empty( $_POST ) ) {
					$this->save_add_ons();
				}

				// show add-on editor
				$this->render_editor();

				?>
			</form>
		</div><?php
	}


	/**
	 * Render the checkout add-ons editor table
	 *
	 * @since 1.0
	 */
	private function render_editor() {
		?>
			<div class="wc-checkout-add-ons-editor-content">
				<table class="widefat wc-checkout-add-ons-editor">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th class="wc-checkout-add-on-name">
								<?php _e( 'Name', WC_Checkout_Add_Ons::TEXT_DOMAIN); ?> <abbr class="required" title="required">*</abbr>
								<img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Add-on name displayed in the admin and order details, e.g., "Gift Message"', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							</th>
							<th class="wc-checkout-add-on-label">
								<?php _e( 'Checkout Label', WC_Checkout_Add_Ons::TEXT_DOMAIN); ?>
								<img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Optional descriptive label shown on checkout, e.g., "Add a Gift Message?". This will default to Name if blank.', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							</th>
							<th width="1%" class="wc-checkout-add-on-type"><?php _e( 'Type', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?></th>
							<th class="wc-checkout-add-on-options-costs">
								<?php _e( 'Options / Costs', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?>
								<img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Use Pipe (|) to separate options and surround options with double stars (**) to set as a default. To set a price for an option, append it to the option with an equation mark. Example: Yes=5.00|No', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							</th>
							<th class="wc-checkout-add-on-attributes"><?php _e( 'Attributes', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?></th>
							<th class="wc-checkout-add-on-taxes"><?php _e( 'Taxes', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?></th>
							<th class="js-wc-checkout-add-on-draggable"></th>
						</tr>
					</thead>
					<tfoot>
					<tr>
						<th colspan="3">
							<button type="button" class="button button-secondary js-wc-checkout-add-ons-add-new">&nbsp;&#43; <?php _e( 'New Add-On', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?></button>
							<button type="button" class="button button-secondary js-wc-checkout-add-ons-remove"><?php _e( 'Remove Selected', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?></button>
						</th>
						<th colspan="5"><input type="submit" class="button-primary" value="<?php _e( 'Save Add-Ons', WC_Checkout_Add_Ons::TEXT_DOMAIN ); ?>"/></th>
					</tr>
					</tfoot>
					<tbody>
						<?php
							$index = 0;

							foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on_id => $add_on ) {

								echo $this->get_row_html( $index, $add_on_id, $add_on );

								$index++;
							}
						?>
					</tbody>
				</table>
			</div>
		<?php

		wp_nonce_field( __FILE__ );
	}


	/**
	 * Return the HTML for a new add-on row
	 *
	 * @since 1.0
	 * @param int $index Optional. the row index
	 * @param int $add_on_id Optional. the ID of the add-on
	 * @param array $add_on Optional. the add-on data
	 * @return string the HTML
	 */
	private function get_row_html( $index = null, $add_on_id = null, $add_on = null ) {

		// used in table row view
		$add_on_types      = $this->add_on_types;
		$add_on_attributes = $this->add_on_attributes;

		// Get tax class options
		$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
		$classes_options = array();
		$classes_options[''] = __( 'Standard', WC_Checkout_Add_Ons::TEXT_DOMAIN );
		if ( $tax_classes ) {
			foreach ( $tax_classes as $class ) {
				$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
			}
		}

		if ( is_object( $add_on ) ) {

			// convert options and costs back into a simple string
			if ( $add_on->has_options() ) {

				$values = array();

				foreach ( $add_on->get_options() as $option ) {

					// skip blank option added for non-required select
					if ( empty( $option['label'] ) ) {
						continue;
					}

					// add back default indicators
					$value = $option['selected'] ? '**' . $option['label'] . '**' : $option['label'];

					// add cost
					$value = $value . ( isset( $option['cost'] ) && $option['cost'] !== '' ? '=' . $option['cost'] : '' );

					$values[] = $value;
				}

				$add_on->options_costs = implode( ' | ', $values );
			} else {

				$add_on->options_costs = $add_on->cost;
			}
		}

		ob_start();

		require( 'views/html-add-on-editor-table-row.php' );

		return ob_get_clean();
	}


	/**
	 * Save the add-ons
	 *
	 * @since 1.0
	 */
	private function save_add_ons() {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], __FILE__ ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', WC_Checkout_Add_Ons::TEXT_DOMAIN ) );
		}

		$add_ons = array();

		if ( ! empty( $_POST['wc-checkout-add-on-id'] ) ) {

			for ( $index = 0; $index < count( $_POST['wc-checkout-add-on-id'] ); $index++ ) {

				// ID - assigned if empty
				$add_on_id = ( empty( $_POST['wc-checkout-add-on-id'][ $index ] ) ) ? $this->get_next_add_on_id() : absint( $_POST['wc-checkout-add-on-id'][ $index ] );

				$add_ons[ $add_on_id ] = array();

				// name
				$add_ons[ $add_on_id ]['name'] = sanitize_text_field( stripslashes( $_POST['wc-checkout-add-on-name'][ $index ] ) );

				// label
				$add_ons[ $add_on_id ]['label'] = sanitize_text_field( stripslashes( $_POST['wc-checkout-add-on-label'][ $index ] ) );

				// type
				$add_ons[ $add_on_id ]['type'] = ( in_array( $_POST['wc-checkout-add-on-type'][ $index ], array_keys( $this->add_on_types ) ) ) ? $_POST['wc-checkout-add-on-type'][ $index ] : 'text';

				// options / costs
				if ( isset( $_POST['wc-checkout-add-on-options-costs'][ $index ] ) ) {

					switch ( $add_ons[ $add_on_id ]['type'] ) {

						// text/textarea/file/chekbox fields have simple costs and no options
						case 'text':
						case 'textarea':
						case 'file':
						case 'checkbox':
							$cost = $_POST['wc-checkout-add-on-options-costs'][ $index ];
							$add_ons[ $add_on_id ]['cost'] = $cost === '' ? $cost : floatval( $cost );
						break;

						// select/multiselect/multicheckbox/radio fields have multiple options and a single default, multiselect/multicheckbox have multiple options and multiple defaults
						case 'select':
						case 'multiselect':
						case 'multicheckbox':
						case 'radio':
							$options = array_map( 'sanitize_text_field', explode( '|', $_POST['wc-checkout-add-on-options-costs'][ $index ] ) );

							foreach ( $options as $option ) {

								// break each option label in two: option label and cost associated with that option
								$label_parts = explode( '=', str_replace( '**', '', $option ) );

								$add_ons[ $add_on_id ]['options'][] = array(
									'default' => false !== strpos( $option, '**' ),
									'label'   => stripslashes( $label_parts[0] ),
									'value'   => sanitize_key( $label_parts[0] ),
									'cost'    => isset( $label_parts[1] ) ? floatval( $label_parts[1] ) : ''
								);
							}

						break;

						// allow custom add-on types
						default:

							/**
							 * Filter the costs of custom add-on types
							 *
							 * @since 1.0
							 * @param stirng $add_on_costs The add-on cost.
							 * @param int $add_on_id The add-on id.
							 */
							$add_ons[ $add_on_id ]['cost']    = apply_filters( 'wc_checkout_add_ons_' . $add_ons[ $add_on_id ]['type'] . '_cost',    '',      $_POST['wc-checkout-add-on-options-costs'][ $index ], $add_on_id );

							/**
							 * Filter the options of custom add-on types
							 *
							 * @since 1.0
							 * @param stirng $add_on_options The add-on options.
							 * @param int $add_on_id The add-on id.
							 */
							$add_ons[ $add_on_id ]['options'] = apply_filters( 'wc_checkout_add_ons_' . $add_ons[ $add_on_id ]['type'] . '_options', array(), $_POST['wc-checkout-add-on-options-costs'][ $index ], $add_on_id );
					}

				} else {

					$add_ons[ $add_on_id ]['cost'] = null;
				}

				// attributes - true/false for each
				if ( ! empty( $_POST['wc-checkout-add-on-attributes'][ $index ] ) ) {

					foreach ( array( 'required', 'listable', 'sortable', 'filterable' ) as $attribute ) {
						$add_ons[ $add_on_id ][ $attribute ] = ( in_array( $attribute, $_POST['wc-checkout-add-on-attributes'][ $index ] ) );
					}

					// add the listable attribute if either sortable or filterable were added
					if ( ! $add_ons[ $add_on_id ]['listable'] && ( $add_ons[ $add_on_id ]['sortable'] || $add_ons[ $add_on_id ]['filterable'] ) ) {
						$add_ons[ $add_on_id ]['listable'] = true;
					}
				}

				// tax_status and tax class
				$add_ons[ $add_on_id ]['tax_status'] = sanitize_text_field( $_POST['wc-checkout-add-on-tax_status'][ $index ] );
				$add_ons[ $add_on_id ]['tax_class']  = sanitize_text_field( $_POST['wc-checkout-add-on-tax_class'][ $index ] );

				// scope (not exposed in editor right now)
				$add_ons[ $add_on_id ]['scope'] = 'order';
			}
		}

		if ( true === update_option( 'wc_checkout_add_ons', $add_ons ) ) {
			echo '<div class="updated"><p>' . __( 'Add-Ons Saved', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . '</p></div>';
		}
	}


	/**
	 * Get the next available add-on ID
	 *
	 * @since 1.0
	 * @return int the next available add-on ID
	 */
	private function get_next_add_on_id() {

		$next_add_on_id = get_option( 'wc_checkout_add_ons_next_add_on_id' );

		update_option( 'wc_checkout_add_ons_next_add_on_id', ++$next_add_on_id );

		return $next_add_on_id;
	}


} // end \WC_Checkout_Add_Ons_Admin class
