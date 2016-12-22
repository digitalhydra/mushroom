<?php
/**
 * Plugin Name: WooCommerce Order Status Control
 * Plugin URI: http://www.woothemes.com/products/woocommerce-order-status-control/
 * Description: Automatically change order status to complete for all orders or just virtual orders when payment is successful
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.3.0
 * Text Domain: woocommerce-order-status-control
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2013-2014 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Order-Status-Control
 * @author    SkyVerge
 * @category  Utility
 * @copyright Copyright (c) 2013-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '32400e509c7c36dcc1cd368e8267d981', '439037' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '3.1.0', __( 'WooCommerce Order Status Control', 'woocommerce-order-status-control' ), __FILE__, 'init_woocommerce_order_status_control', array( 'minimum_wc_version' => '2.1', 'backwards_compatible' => '3.1.0' ) );

function init_woocommerce_order_status_control() {

/**
 * # WooCommerce Order Status Control Main Plugin Class
 *
 * ## Plugin Overview
 *
 * Control the status that orders are changed to when payment is complete
 *
 * ## Admin Considerations
 *
 * Global settings are added to WooCommerce > Settings > General, under the 'Downloadable Products' section
 *
 * ## Database
 *
 * ### Global Settings
 *
 * + `wc_order_status_control_auto_complete_orders` - determines which types of orders are auto-completed, either 'all' for every order, or 'virtual' for only orders containing virtual products
 *
 * ### Options table
 *
 * + `wc_order_status_control_version` - the current plugin version, set on install/upgrade
 *
 */
class WC_Order_Status_Control extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.3.0';

	/** @var WC_Order_Status_Control single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'order_status_control';

	/** plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-order-status-control';


	/**
	 * Initializes the plugin
	 *
	 * @since 1.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			self::TEXT_DOMAIN
		);

		// Hook for order status when payment is complete
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'handle_payment_complete_order_status' ), 10, 2 );

		// admin
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// add general settings
			add_filter( 'woocommerce_general_settings', array( $this, 'add_global_settings' ) );
		}
	}


	/**
	 * Handles completing orders when payment is completed
	 *
	 * @since 1.0
	 * @param string $order_status the default order status to change the order to
	 * @param int $order_id the ID of the order
	 * @return string the (maybe) modified order status to change to
	 */
	public function handle_payment_complete_order_status( $order_status, $order_id ) {

		$order = SV_WC_Plugin_Compatibility::wc_get_order( $order_id );

		$order_type_to_complete = get_option( 'wc_order_status_control_auto_complete_orders' );

		// auto-complete all orders
		if ( 'all' == $order_type_to_complete ) {

			$order_status = 'completed';

		// auto-complete virtual order
		} elseif ( 'virtual' == $order_type_to_complete ) {

			// only modify orders that are being changed to 'processing', which indicates they are not a downloadable-virtual order
			if ( 'processing' == $order_status &&
			  ( in_array( SV_WC_Plugin_Compatibility::get_order_status( $order ), array( 'on-hold', 'pending', 'failed' ) ) ) ) {

				$virtual_order = false;

				$order_items = $order->get_items();

				if ( count( $order_items ) > 0 ) {

					foreach( $order_items as $item ) {

							$product = $order->get_product_from_item( $item );

							// once we've found one non-virtual product we know we're done, break out of the loop
							if ( ! $product->is_virtual() ) {
								$virtual_order = false;
								break;
							} else {
								$virtual_order = true;
							}
					}
				}

				// virtual order, mark as completed
				if ( $virtual_order ) {
					$order_status = 'completed';
				}
			}
		}

		return $order_status;
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 1.2
	 * @see SV_WC_Plugin::load_translation()
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-order-status-control', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/** Admin methods ******************************************************/


	/**
	 * Inject global settings into the Settings > General page, immediately after the 'Customer Accounts' section
	 *
	 * @since 1.0
	 * @param array $settings associative array of WooCommerce settings
	 * @return array associative array of WooCommerce settings
	 */
	public function add_global_settings( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $setting ) {

			$updated_settings[] = $setting;

			if ( isset( $setting['id'] ) && 'woocommerce_demo_store' === $setting['id'] ) {
				$updated_settings = array_merge( $updated_settings, $this->get_global_settings() );
			}
		}

		return $updated_settings;
	}


	/**
	 * Returns the global settings array for the plugin
	 *
	 * @since 1.0
	 * @return array the global settings
	 */
	public function get_global_settings() {

		return apply_filters( 'wc_order_status_control_global_settings', array(

			// complete all orders upon payment complete
			array(
				'title'    => __( 'Orders to Auto-Complete', self::TEXT_DOMAIN ),
				'desc_tip' => __( 'Select which types of orders should be changed to completed when payment is received.', self::TEXT_DOMAIN ),
				'id'       => 'wc_order_status_control_auto_complete_orders',
				'default'  => 'none',
				'type'     => 'select',
				'options'  => array(
					'none'    => __( 'None', self::TEXT_DOMAIN ),
					'all'     => __( 'All Orders', self::TEXT_DOMAIN ),
					'virtual' => __( 'Virtual Orders', self::TEXT_DOMAIN ),
				),
			),

		) );
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Order Status Control Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.3.0
	 * @see wc_order_status_control()
	 * @return WC_Order_Status_Control
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.2
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Order Status Control', self::TEXT_DOMAIN );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.2
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 1.2
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc-settings' );
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Install default settings
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {

		// install default settings, terms, etc
		foreach ( $this->get_global_settings() as $setting ) {

			if ( isset( $setting['default'] ) ) {
				add_option( $setting['id'], $setting['default'] );
			}
		}
	}


} // end WC_Order_Status_Control


/**
 * Returns the One True Instance of <plugin>
 *
 * @since 1.3.0
 * @return WC_Order_Status_Control
 */
function wc_order_status_control() {
	return WC_Order_Status_Control::instance();
}


/**
 * The WC_Order_Status_Control global object, exists only for backwards compat
 *
 * @deprecated 1.3.0
 * @name $wc_order_status_control
 * @global WC_Order_Status_Control $GLOBALS['wc_order_status_control']
 */
$GLOBALS['wc_order_status_control'] = wc_order_status_control();


} // init_woocommerce_order_status_control()
