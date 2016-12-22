<?php
/**
 * Plugin Name: WooCommerce Order Status Manager
 * Plugin URI: http://www.woothemes.com/products/woocommerce-order-status-manager/
 * Description: Easily create custom order statuses and trigger custom emails when order status changes
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.1.4
 * Text Domain: woocommerce-order-status-manager
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Order-Status-Manager
 * @author    SkyVerge
 * @category  Integration
 * @copyright Copyright (c) 2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '51fd9ab45394b4cad5a0ebf58d012342', '588398' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '3.1.0', __( 'WooCommerce Order Status Manager', 'woocommerce-order-status-manager' ), __FILE__, 'init_woocommerce_order_status_manager', array( 'minimum_wc_version' => '2.1', 'backwards_compatible' => '3.1.0' ) );

function init_woocommerce_order_status_manager() {


/**
 * # WooCommerce Order Status Manager Main Plugin Class
 *
 * ## Plugin Overview
 *
 * This plugin allows adding custom order statuses to WooCommerce
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.1.4';

	/** @var WC_Order_Status_Manager single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'order_status_manager';

	/** plugin meta prefix */
	const PLUGIN_PREFIX = 'wc_order_status_manager_';

	/** plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-order-status-manager';

	/** @var \WC_Order_Status_Manager_Admin instance */
	public $admin;

	/** @var \WC_Order_Status_Manager_Frontend instance */
	public $frontend;

	/** @var \WC_Order_Status_Manager_AJAX instance */
	public $ajax;

	/** @var \WC_Order_Status_Manager_Order_Statuses instance */
	public $order_statuses;

	/** @var \WC_Order_Status_Manager_Emails instance */
	public $emails;

	/** @var \WC_Order_Status_Manager_Icons instance */
	public $icons;


	/**
	 * Initializes the plugin
	 *
	 * @since 1.0.0
	 * @return \WC_Order_Status_Manager
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			self::TEXT_DOMAIN
		);

		add_action( 'init', array( $this, 'init' ) );

		// Make sure email template files are searched for in our plugin
		add_filter( 'woocommerce_locate_template',      array( $this, 'locate_template' ), 20, 3 );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_template' ), 20, 3 );
	}


	/**
	 * Include Order Status Manager required files
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		require_once( 'includes/class-wc-order-status-manager-order-statuses.php' );
		$this->order_statuses = new WC_Order_Status_Manager_Order_Statuses();

		require_once( 'includes/class-wc-order-status-manager-post-types.php' );
		WC_Order_Status_Manager_Post_Types::initialize();

		require_once( 'includes/class-wc-order-status-manager-emails.php' );
		require_once( 'includes/class-wc-order-status-manager-icons.php' );

		$this->emails         = new WC_Order_Status_Manager_Emails();
		$this->icons          = new WC_Order_Status_Manager_Icons();

		// Frontend includes
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			require_once( 'includes/class-wc-order-status-manager-frontend.php' );
			$this->frontend = new WC_Order_Status_Manager_Frontend();
		}

		// Admin includes
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$this->admin_includes();
		}

		if ( defined( 'DOING_AJAX' ) ) {
			$this->ajax_includes();
		}
	}


	/**
	 * Include required admin files
	 *
	 * @since 1.0.0
	 */
	private function admin_includes() {

		require_once( 'includes/admin/class-wc-order-status-manager-admin.php' );
		$this->admin = new WC_Order_Status_Manager_Admin();
	}


	/**
	 * Include required AJAX files
	 *
	 * @since 1.0.0
	 */
	private function ajax_includes() {

		include_once( 'includes/class-wc-order-status-manager-ajax.php' );
		$this->ajax = new WC_Order_Status_Manager_AJAX();
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::load_translation()
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-order-status-manager', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/**
	 * Initialize translation and post types
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Include required files
		$this->includes();

		$this->order_statuses->ensure_statuses_have_posts();
	}


	/**
	 * Locates the WooCommerce template files from our templates directory
	 *
	 * @since 1.0.0
	 * @param string $template Already found template
	 * @param string $template_name Searchable template name
	 * @param string $template_path Template path
	 * @return string Search result for the template
	 */
	public function locate_template( $template, $template_name, $template_path ) {

		// Tmp holder
		$_template = $template;

		if ( ! $template_path ) {
			$template_path = WC_TEMPLATE_PATH;
		}

		// Set our base path
		$plugin_path = $this->get_plugin_path() . '/templates/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);

		// Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $template ) {
			$template = $_template;
		}

		// Return what we found
		return $template;
	}


	/** Admin methods ******************************************************/


	/**
	 * Render a notice for the user to read the docs before using the plugin
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		$this->get_admin_notice_handler()->add_admin_notice(
			sprintf( __( 'Thanks for installing Order Status Manager! Before you get started, please take a moment to %sread through the documentation%s.', $this->text_domain ),
				'<a href="' . $this->get_documentation_url() . '">', '</a>' ),
				'read-the-docs',
				array( 'always_show_on_settings' => false, 'notice_class' => 'updated' )
		);
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Order Status Manager Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.1.0
	 * @see wc_order_status_manager()
	 * @return WC_Order_Status_Manager
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
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Order Status Manager', $this->text_domain );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'edit.php?post_type=wc_order_status' );
	}


	/**
	 * Returns true if on the Order Status Manager settings page
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['post_type'] ) && 'wc_order_status' == $_GET['post_type'];
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Install defaults
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {

		$this->icons->update_icon_options();
	}


	/**
	 * Perform any version-related changes.
	 *
	 * @since 1.0.0
	 * @param int $installed_version the currently installed version of the plugin
	 */
	protected function upgrade( $installed_version ) {

		// Always update icon options
		$this->icons->update_icon_options();

		if ( version_compare( $installed_version, '1.1.0', '<' ) ) {

			foreach( $this->order_statuses->get_core_order_statuses() as $slug => $core_status ) {

				$status  = new WC_Order_Status_Manager_Order_Status( $slug );
				$post_id = $status->get_id();

				$slug = str_replace( 'wc-', '', $slug );

				switch ( $slug ) {

					case 'processing':
						update_post_meta( $post_id, '_include_in_reports', 'yes' );
					break;

					case 'on-hold':
						update_post_meta( $post_id, '_include_in_reports', 'yes' );
					break;

					case 'completed':
						update_post_meta( $post_id, '_include_in_reports', 'yes' );
					break;

					case 'refunded':
						update_post_meta( $post_id, '_include_in_reports', 'yes' );
					break;

				}
			}
		}
	}


} // end \WC_Order_Status_Manager class


/**
 * Returns the One True Instance of <plugin>
 *
 * @since 1.1.0
 * @return <class name>
 */
function wc_order_status_manager() {
	return WC_Order_Status_Manager::instance();
}


/**
 * The WC_Order_Status_Manager global object
 *
 * @deprecated 1.1.0
 * @name $wc_order_status_manager
 * @global WC_Order_Status_Manager $GLOBALS['wc_order_status_manager']
 */
$GLOBALS['wc_order_status_manager'] = wc_order_status_manager();

} // init_woocommerce_order_status_manager()
