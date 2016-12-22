<?php
/**
 * Plugin Name: WooCommerce Checkout Add-Ons
 * Plugin URI: http://www.woothemes.com/products/wc-checkout-add-ons/
 * Description: Easily create paid add-ons for your WooCommerce checkout and display them in the Orders admin, the My Orders section, and even order emails!
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.4.2
 * Text Domain: woocommerce-checkout-add-ons
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2014-2015 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Checkout-Add-Ons
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '8fdca00b4000b7a8cc26371d0e470a8f', '466854' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '3.1.2', __( 'WooCommerce Checkout Add-Ons', 'woocommerce-checkout-add-ons' ), __FILE__, 'init_woocommerce_checkout_add_ons', array( 'minimum_wc_version' => '2.1', 'backwards_compatible' => '3.1.0' ) );

function init_woocommerce_checkout_add_ons() {


/**
 * # WooCommerce Checkout Add-Ons Main Plugin Class
 *
 * ## Plugin Overview
 *
 * This plugin allows merchants to define paid order add-ons, which are
 * represented by fields shown during checkout and attached to the order.  They
 * are shown in the customer's account order details, in order emails, and in
 * the order Admin.
 *
 * Checkout add-ons are implemented using the WooCommerce core fee API
 *
 * ## Features
 *
 * + Define paid order add-on fields to be displayed at checkout
 *
 * ## Frontend Considerations
 *
 * On the frontend the checkout add on fields are rendered on the checkout page.
 * The selected options/values are displayed on the "thank you" page, order
 * details, and emails (if so configured).
 *
 * ## Admin Considerations
 *
 * Adds a WooCommerce menu item named Checkout Add-Ons which displays a list
 * table of configured add-on fields, along with the ability to create/update/
 * delete them.
 *
 * ## Database
 *
 * ### Options table
 *
 * + `wc_checkout_add_ons`                - the defined checkout add-ons
 * + `wc_checkout_add_ons_next_add_on_id` - unique, sequential id generator
 *
 * ### Order Item Meta
 *
 * + `_wc_checkout_add_on_id`    - The checkout add on id
 * + `_wc_checkout_add_on_value` - The checkout add on value selected/entered by the customer
 * + `_wc_checkout_add_on_label` - The value, normalized
 *
 * @since 1.0
 */
class WC_Checkout_Add_Ons extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.4.2';

	/** @var WC_Checkout_Add_Ons single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'checkout_add_ons';

	/** plugin meta prefix */
	const PLUGIN_PREFIX = 'wc_checkout_add_ons_';

	/** plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-checkout-add-ons';

	/** @var \WC_Checkout_Add_Ons_Admin instance */
	public $admin;

	/** @var \WC_Checkout_Add_Ons_Frontend instance */
	public $frontend;


	/**
	 * Initializes the plugin
	 *
	 * @since 1.0
	 * @return \WC_Checkout_Add_Ons
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			self::TEXT_DOMAIN
		);

		// Initialize
		add_action( 'init', array( $this, 'includes' ) );

		// custom ajax handler for AJAX search
		add_action( 'wp_ajax_wc_checkout_add_ons_json_search_field', array( $this, 'add_json_search_field' ) );

		// add checkout add-ons value column header to order items table
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'add_order_item_headers' ) );

		// add checkout add-ons value column to order items table
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'add_order_item_values' ), 10, 3 );

		// save checkout add-ons value
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'process_shop_order_meta' ), 10, 2 );

		// save checkout add-ons value via ajax
		add_action( 'wp_ajax_wc_checkout_add_ons_save_order_items', array( $this, 'save_order_item_values_ajax' ) );
	}


	/**
	 * Include required files
	 *
	 * @since 1.0
	 */
	public function includes() {

		require_once( 'includes/class-wc-checkout-add-on.php' );
		require_once( 'includes/frontend/class-wc-checkout-add-ons-frontend.php' );
		require_once( 'includes/class-wc-checkout-add-ons-export-handler.php' );

		$this->frontend = new WC_Checkout_Add_Ons_Frontend();

		$this->export_handler = new WC_Checkout_Add_Ons_Export_Handler();

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$this->admin_includes();
		}
	}


	/**
	 * Include required admin files
	 *
	 * @since 1.0
	 */
	private function admin_includes() {

		// load order list table/edit order customizations
		require_once( 'includes/admin/class-wc-checkout-add-ons-admin.php' );
		$this->admin = new WC_Checkout_Add_Ons_Admin();
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::load_translation()
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-checkout-add-ons', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/** Admin methods ******************************************************/


	/**
	 * Render a notice for the user to read the docs before adding add-ons
	 *
	 * @since 1.1.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		$this->get_admin_notice_handler()->add_admin_notice(
			sprintf( __( 'Thanks for installing Checkout Add-Ons! Before you get started, please take a moment to %sread through the documentation%s.', $this->text_domain ),
				'<a href="' . $this->get_documentation_url() . '">', '</a>' ),
				'read-the-docs',
				array( 'always_show_on_settings' => false )
		);
	}


	/**
	 * AJAX search handler for chosen fields.  Searches for checkout add-ons
	 * and returns the results.
	 *
	 * @since 1.0
	 */
	public function add_json_search_field() {
		global $wpdb;

		check_ajax_referer( 'search-field', 'security' );

		// the search term
		$term = isset( $_GET['term'] ) ? urldecode( stripslashes( strip_tags( $_GET['term'] ) ) ) : '';

		// the field to search
		$id = isset( $_GET['request_data']['add_on_id'] ) ? urldecode( stripslashes( strip_tags( $_GET['request_data']['add_on_id'] ) ) ) : '';

		// required parameters
		if ( empty( $term ) || empty( $id ) ) {
			die;
		}

		$default = isset( $_GET['request_data']['default'] ) ? $_GET['request_data']['default'] : '';

		$found_values = SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ? array() : array( '' => $default );

		$query = $wpdb->prepare( "
			SELECT woim_value.meta_value
			FROM {$wpdb->prefix}woocommerce_order_itemmeta woim_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim_value ON woim_id.order_item_id = woim_value.order_item_id
			WHERE 1=1
				AND woim_id.meta_key = '_wc_checkout_add_on_id'
				AND woim_id.meta_value = %d
				AND woim_value.meta_key = '_wc_checkout_add_on_value'
				AND woim_value.meta_value LIKE %s
		", $id, '%' . $term . '%' );

		$results = $wpdb->get_results( $query );

		if ( $results ) {
			foreach ( $results as $result ) {
				$found_values[ $result->meta_value ] = $result->meta_value;
			}
		}

		echo json_encode( $found_values );

		exit;
	}


	/**
	 * Add checkout add-ons headers to the order items table
	 *
	 * @since 1.1.0
	 */
	public function add_order_item_headers() {
		global $post;

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_2() ) {
			echo '<th class="wc-checkout-add-ons-value">&nbsp;</th>';

			// enqueue ajax for saving add-on values
			$javascript = "
				jQuery( 'body' ).on( 'items_saved', 'button.save-action', function() {
					jQuery.ajax({
						type: 'POST',
						url: '" . admin_url( 'admin-ajax.php' ) . "',
						data: {
							action: 'wc_checkout_add_ons_save_order_items',
							security: '" . wp_create_nonce( "save-checkout-add-ons" ) . "',
							order_id: '" . ( isset( $post->ID ) ? $post->ID : '' ) . "',
							items: jQuery('table.woocommerce_order_items :input[name], .wc-order-totals-items :input[name]').serialize()
						}
					});
				});";

			wc_enqueue_js( $javascript );
		}
	}


	/**
	 * Add checkout add-ons values to the order items table
	 *
	 * @param null $_ unused
	 * @param array $item
	 * @since 1.1.0
	 */
	public function add_order_item_values( $_, $item, $item_id ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_2() ) {

			echo '<td class="wc-checkout-add-ons-value">';

			if ( is_array( $item ) && isset( $item['wc_checkout_add_on_id'] ) && $add_on = $this->get_add_on( $item['wc_checkout_add_on_id'] ) ) {

				$is_editable = in_array( $add_on->type, array( 'text', 'textarea' ) );

				if ( 'file' === $add_on->type ) {
					$value = $add_on->normalize_value( $item['wc_checkout_add_on_value'], false );
				} else {
					$value = esc_html( $add_on->normalize_value( maybe_unserialize( $item['wc_checkout_add_on_label'] ), true ) );
				}

				ob_start();
				?>

				<div class="view">
					<?php echo $value; ?>
				</div>

				<?php if ( $is_editable ) : ?>
				<div class="edit" style="display: none;">
					<input type="text" placeholder="<?php _e( 'Checkout Add-on Value', $this->text_domain ); ?>" name="checkout_add_on_value[<?php echo $item_id; ?>]" value="<?php echo $value; ?>" />
					<input type="hidden" class="checkout_add_on_id" name="checkout_add_on_item_id[]" value="<?php echo $item_id; ?>" />
					<input type="hidden" class="checkout_add_on_id" name="checkout_add_on_id[<?php echo $item_id; ?>]" value="<?php echo $add_on->id; ?>" />
				</div>
				<?php endif; ?>

				<?php
				echo ob_get_clean();
			}

			echo '</td>';
		}
	}


	/**
	 * Save checkout add-on values
	 *
	 * @param int $order_id Order ID
	 * @param array $items Order items to save
	 * @since 1.2.0
	 */
	public function save_order_item_values( $order_id, $items ) {

		if ( isset( $items['checkout_add_on_item_id'] ) ) {

			$item_ids = $items['checkout_add_on_item_id'];

			foreach( $item_ids as $item_id ) {

				$item_id = absint( $item_id );

				if ( isset( $items['checkout_add_on_value'][ $item_id ] ) && isset( $items['checkout_add_on_id'][ $item_id ] ) ) {

					$add_on = $this->get_add_on( $items['checkout_add_on_id'][ $item_id ] );

					wc_update_order_item_meta( $item_id, '_wc_checkout_add_on_value', $items['checkout_add_on_value'][ $item_id ] );
					wc_update_order_item_meta( $item_id, '_wc_checkout_add_on_label', $add_on->normalize_value( $items['checkout_add_on_value'][ $item_id ], true ) );
				}
			}
		}
	}


	/**
	 * Process checkout add-on values when order is saved
	 *
	 * @param int $order_id Order ID
	 * @param object $post
	 * @since 1.2.0
	 */
	public function process_shop_order_meta( $order_id, $post  ) {
		$this->save_order_item_values( $order_id, $_POST );
	}


	/**
	 * Save checkout add-on values
	 *
	 * @since 1.2.0
	 */
	public static function save_order_item_values_ajax() {
		check_ajax_referer( 'save-checkout-add-ons', 'security' );

		if ( isset( $_POST['order_id'] ) && isset( $_POST['items'] ) ) {
			$order_id = absint( $_POST['order_id'] );

			// Parse the jQuery serialized items
			$items = array();
			parse_str( $_POST['items'], $items );

			// Save order items
			wc_checkout_add_ons()->save_order_item_values( $order_id, $items );
		}

		exit;
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Checkout Add-Ons Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.4.0
	 * @see wc_checkout_add_ons()
	 * @return WC_Checkout_Add_Ons
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Convenience methods for other plugins to easily get add-ons for a given
	 * order
	 *
	 * @since 1.0
	 * @param int $order_id WC_Order ID
	 * @return array of WC_Checkout_Add_On objects
	 */
	public function get_order_add_ons( $order_id ) {

		$order = SV_WC_Plugin_Compatibility::wc_get_order( $order_id );

		$add_ons = get_option( 'wc_checkout_add_ons', array() );

		$order_add_ons = array();

		foreach ( $order->get_items( 'fee' ) as $fee_id => $fee ) {

			// bail for fees that aren't add-ons or deleted add-ons
			if ( empty( $fee['wc_checkout_add_on_id'] ) || ! isset( $add_ons[ $fee['wc_checkout_add_on_id'] ] ) ) {
				continue;
			}

			$add_on = new WC_Checkout_Add_On( $fee['wc_checkout_add_on_id'], $add_ons[ $fee['wc_checkout_add_on_id'] ] );

			$order_add_ons[ $fee['wc_checkout_add_on_id'] ] = array(
				'name'             => $add_on->name,
				'checkout_label'   => $add_on->label,
				'value'            => $fee['wc_checkout_add_on_value'],
				'normalized_value' => maybe_unserialize( $fee['wc_checkout_add_on_label'] ),
				'total'            => $fee['line_total'],
				'total_tax'        => $fee['line_tax'],
				'fee_id'           => $fee_id,
			);
		}

		return $order_add_ons;
	}


	/**
	 * Returns any globally configured add-ons
	 *
	 * @since 1.0
	 * @return array of WC_Checkout_Add_On objects
	 */
	public function get_add_ons() {

		$add_ons = array();

		$checkout_add_ons = get_option( 'wc_checkout_add_ons', array() );

		foreach ( $checkout_add_ons as $add_on_id => $add_on ) {

			$add_on = new WC_Checkout_Add_On( $add_on_id, $add_on );
			$add_ons[ $add_on_id ] = $add_on;
		}

		return $add_ons;
	}


	/**
	 * Get a single add-on by ID
	 *
	 * @param int $id
	 * @return object WC_Checkout_Add_On if found, otherwise void
	 */
	public function get_add_on( $id ) {
		foreach ( $this->get_add_ons() as $add_on ) {
			if ( $id == $add_on->id ) {
				return $add_on;
			}
		}
		return null;
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Checkout Add-Ons', $this->text_domain );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc_checkout_add_ons' );
	}


	/**
	 * Returns true if on the gateway settings page
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'] ) && 'wc_checkout_add_ons' == $_GET['page'];
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Install default settings
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {

		add_option( 'wc_checkout_add_ons_next_add_on_id', 1 );
	}


} // end \WC_Checkout_Add_Ons class


/**
 * Returns the One True Instance of Checkout Add-Ons
 *
 * @since 1.4.0
 * @return WC_Checkout_Add_Ons
 */
function wc_checkout_add_ons() {
	return WC_Checkout_Add_Ons::instance();
}


/**
 * The WC_Checkout_Add_Ons global object
 *
 * @deprecated 1.4.0
 * @name $wc_checkout_add_ons
 * @global WC_Checkout_Add_Ons $GLOBALS['wc_checkout_add_ons']
 */
$GLOBALS['wc_checkout_add_ons'] = wc_checkout_add_ons();

} // init_woocommerce_checkout_add_ons()
