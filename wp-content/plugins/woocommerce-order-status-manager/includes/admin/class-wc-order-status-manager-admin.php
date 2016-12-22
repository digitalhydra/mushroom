<?php
/**
 * WooCommerce Order Status Manager
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Order Status Manager to newer
 * versions in the future. If you wish to customize WooCommerce Order Status Manager for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-order-status-manager/ for more information.
 *
 * @package     WC-Order-Status-Manager/Admin
 * @author      SkyVerge
 * @copyright   Copyright (c) 2015, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin class
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_Admin {


	/** @var SV_WP_Admin_Message_Handler instance **/
	private $message_handler;


	/**
	 * Setup admin class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Ensure that message handler loaded
		$this->message_handler = wc_order_status_manager()->get_message_handler();

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this->message_handler, 'load_messages' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		// Remove all and any views from order statuses & order status emails list screens
		add_filter( 'views_edit-wc_order_status', '__return_empty_array' );
		add_filter( 'views_edit-wc_order_email',  '__return_empty_array' );

		// Remove bulk actions
		add_filter( 'bulk_actions-edit-wc_order_status', '__return_empty_array' );
		add_filter( 'bulk_actions-edit-wc_order_email',  '__return_empty_array' );

		// Remove date filter from list screen
		add_filter( 'months_dropdown_results', array( $this, 'remove_months_dropdown'), 10, 2 );

		// Normalize columns
		add_filter( 'manage_edit-wc_order_status_columns', array( $this, 'normalize_columns' ) );
		add_filter( 'manage_edit-wc_order_email_columns',  array( $this, 'normalize_columns' ) );

		// Normalize row actions
		add_filter( 'post_row_actions', array( $this, 'normalize_row_actions' ), 100, 2 );

		// Force-delete statuses and emails instead of trashing
		add_action( 'load-edit.php', array( $this, 'force_delete' ) );

		// Hide title field and default publishing box
		add_action( 'post_submitbox_misc_actions', array( $this, 'normalize_edit_screen' ) );

		// Hide search & filter on list screen
		add_action( 'restrict_manage_posts', array( $this, 'normalize_list_screen' ) );

		// Save meta boxes
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

		// Disable autosave for the out post types
		add_action( 'admin_footer', array( $this, 'disable_autosave' ) );

		// Add Order Statuses tab to settings
		add_action( 'woocommerce_settings_tabs', array( $this, 'print_settings_tabs'), 1 );

		// Display WooCommerce settings tabs on order status & emails pages
		add_action( 'all_admin_notices', array( $this, 'print_woocommerce_settings_tabs' ), 1 );
		add_action( 'all_admin_notices', array( $this, 'output_sections' ) );
		add_action( 'all_admin_notices', array( $this->message_handler, 'show_messages' ) );

		// Highlight WooCommerce -> Settings when on order status manager pages
		add_filter( 'parent_file', array( $this, 'highlight_admin_menu' ) );

		// Include custom statuses in order reports
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'reports_order_statuses' ) );

		// Include custom statuses in the partial refund caluclations of the reports
		add_filter( 'woocommerce_reports_get_order_report_data_args', array( $this, 'order_report_data_args' ) );
	}


	/**
	 * Add the Order Statuses tab to WooCommerce settings
	 *
	 * @since 1.0.0
	 */
	public function print_settings_tabs() {
		global $typenow;

		echo '<a href="' . admin_url( 'edit.php?post_type=wc_order_status' ) . '" class="nav-tab ' . ( 'wc_order_status' == $typenow ? 'nav-tab-active' : '' ) . '">' . __( 'Order Statuses', WC_Order_Status_Manager::TEXT_DOMAIN ) . '</a>';
	}


	/**
	 * Print WooCommerce settings tabs on order status manager screens
	 *
	 * Simulates a simplified version of WC_Admin_Settings::output and
	 * `html-admin-settings.php` from WC core
	 *
	 * @since 1.0.0
	 */
	public function print_woocommerce_settings_tabs() {

		if ( ! $this->is_order_status_manager_screen() ) {
			return;
		}

		WC_Admin_Settings::get_settings_pages();

		// Get tabs for the settings page
		$tabs = apply_filters( 'woocommerce_settings_tabs_array', array() );

		?>
			<div class="wrap woocommerce">
				<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php
						foreach ( $tabs as $name => $label )
							echo '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $name ) . '" class="nav-tab">' . $label . '</a>';

						do_action( 'woocommerce_settings_tabs' );
					?>
				</h2>
			</div>
		<?php
	}


	/**
	 * Output Order Status Manager sections
	 *
	 * Simulates WC_Settings_Page->output_sections
	 *
	 * @since 1.0.0
	 */
	public function output_sections() {

		if ( ! $this->is_order_status_manager_screen() ) {
			return;
		}

		global $typenow;

		echo '<ul class="subsubsub">';

			echo '<li><a href="' . admin_url( 'edit.php?post_type=wc_order_status' ) . '" class="' . ( 'wc_order_status' == $typenow ? 'current' : '' ) . '">' . __( 'Statuses', WC_Order_Status_Manager::TEXT_DOMAIN ) . '</a> | </li>';
			echo '<li><a href="' . admin_url( 'edit.php?post_type=wc_order_email' ) . '" class="' . ( 'wc_order_email' == $typenow ? 'current' : '' ) . '">' . __( 'Emails', WC_Order_Status_Manager::TEXT_DOMAIN ) . '</a></li>';

		echo '</ul>';
		echo '<br class="clear" />';
	}


	/**
	 * Show Messages
	 *
	 * @since 1.0.0
	 */
	public function show_messages() {

		print_r(wc_order_status_manager()->get_message_handler());

		wc_order_status_manager()->get_message_handler()->show_messages();
	}


	/**
	 * Highlight WooCommerce -> Settings admin menu item when editing an order
	 * status or order status email
	 *
	 * Besides modifying the filterable $parent_file, this function modifies the
	 * global $submenu_file variable.
	 *
	 * @since 1.0.0
	 * @param string $parent_file
	 * @return string $parent_file
	 */
	public function highlight_admin_menu( $parent_file ) {
		global $submenu_file;

		if ( $this->is_order_status_manager_screen() ) {

			$parent_file  = 'woocommerce';
			$submenu_file = 'wc-settings';
		}

		return $parent_file;
	}


	/**
	 * Initialize the admin, adding actions to properly display and handle
	 * the Order Status and Email custom post type add/edit pages
	 *
	 * @since 1.0.0
	 */
	public function init() {
		global $pagenow, $typenow;

		if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow || 'edit.php' == $pagenow ) {

			if ( 'wc_order_status' === $typenow || isset( $_GET['post'] ) && 'wc_order_status' === get_post_type( $_GET['post'] ) ) {
				require_once( 'class-wc-order-status-manager-admin-order-statuses.php' );
				$this->admin_order_statuses = new WC_Order_Status_Manager_Admin_Order_Statuses();
			}

			if ( 'wc_order_email' === $typenow || isset( $_GET['post'] ) && 'wc_order_email' === get_post_type( $_GET['post'] ) ) {
				require_once( 'class-wc-order-status-manager-admin-order-status-emails.php' );
				$this->admin_order_statuses = new WC_Order_Status_Manager_Admin_Order_Status_Emails();
			}

			if ( 'shop_order' === $typenow || isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] ) ) {
				require_once( 'class-wc-order-status-manager-admin-orders.php' );
				$this->admin_orders = new WC_Order_Status_Manager_Admin_Orders();
			}
		}
	}

	/**
	 * Load admin js/css
	 *
	 * @since 1.0.0
	 */
	public function load_styles_scripts() {

		// Get admin screen id
		$screen = get_current_screen();

		// order status edit screen specific styles & scripts
		if ( 'wc_order_status' == $screen->id ) {

			// color picker script/styles
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			// jquery fonticonpicker
			wp_enqueue_script( 'jquery-fonticonpicker', wc_order_status_manager()->get_plugin_url() . '/assets/js/jquery-fonticonpicker/jquery.fonticonpicker.min.js', array( 'jquery' ), WC_Order_Status_Manager::VERSION );

			wp_enqueue_style( 'wc-order-status-manager-jquery-fonticonpicker', wc_order_status_manager()->get_plugin_url() . '/assets/css/admin/wc-order-status-manager-jquery-fonticonpicker.min.css', null, WC_Order_Status_Manager::VERSION );

			wp_enqueue_media();
		}

		if ( 'edit-shop_order' == $screen->id ) {

			// Font Awesome font & classes
			wp_enqueue_style( 'font-awesome', wc_order_status_manager()->get_plugin_url() . '/assets/css/font-awesome.min.css', null, WC_Order_Status_Manager::VERSION );
		}

		// Load styles and scripts on order status screens
		if ( $this->is_order_status_manager_screen() ) {

			// load WC admin CSS
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );

			// admin CSS
			wp_enqueue_style( 'wc-order-status-manager-admin', wc_order_status_manager()->get_plugin_url() . '/assets/css/admin/wc-order-status-manager-admin.min.css', array( 'woocommerce_admin_styles' ), WC_Order_Status_Manager::VERSION );

			// WooCommerce font class declarations
			wp_enqueue_style( 'woocommerce-font-classes', wc_order_status_manager()->get_plugin_url() . '/assets/css/woocommerce-font-classes.min.css', array( 'woocommerce_admin_styles' ), WC_Order_Status_Manager::VERSION );

			// Font Awesome font & classes
			wp_enqueue_style( 'font-awesome', wc_order_status_manager()->get_plugin_url() . '/assets/css/font-awesome.min.css', null, WC_Order_Status_Manager::VERSION );

			// admin JS
			wp_enqueue_script( 'wc-order-status-manager-admin', wc_order_status_manager()->get_plugin_url() . '/assets/js/admin/wc-order-status-manager-admin.min.js', array( 'jquery', 'jquery-tiptip', SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ? 'select2' : 'chosen' ),  WC_Order_Status_Manager::VERSION );

			// localize admin JS
			$order_statuses = array();
			foreach ( wc_get_order_statuses() as $slug => $name ) {
				$order_statuses[ str_replace( 'wc-', '', $slug ) ] = $name;
			}

			$script_data = array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'order_statuses' => $order_statuses,
				'i18n' => array(
					'remove_this_condition' => __( 'Remove this condition?', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'from_status'           => __( 'From Status', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'to_status'             => __( 'To Status', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'remove'                => __( 'Remove', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'any'                   => __( 'Any', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'select_icon'           => __( 'Select Icon', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'all_icon_packages'     => __( 'All icon packages', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'search_icons'          => __( 'Search Icons', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'choose_file'           => __( 'Choose a file', WC_Order_Status_Manager::TEXT_DOMAIN ),
				),
			);

			if ( 'wc_order_status' == $screen->id ) {

				// Create a flat list of icon classes for icon options, we
				// do not need the glyphs there
				$icon_options = array();
				foreach ( wc_order_status_manager()->icons->get_icon_options() as $package => $icons ) {
					$icon_options[ $package ] = array_keys( $icons );
				}

				$script_data['icon_options'] = $icon_options;
			}

			wp_localize_script( 'wc-order-status-manager-admin', 'wc_order_status_manager', $script_data );
		}
	}


	/**
	 * Remove date filter from list screen
	 *
	 * @since 1.0.0
	 * @param array $months
	 * @param string $post_type
	 * @return mixed
	 */
	public function remove_months_dropdown( $months, $post_type ) {

		return $this->is_order_status_manager_post_type( $post_type ) ? null : $months;
	}


	/**
	 * Force-delete any trashed order statuses or emails
	 *
	 * @since 1.0.0
	 */
	public function force_delete() {
		global $typenow;

		if ( $this->is_order_status_manager_post_type( $typenow ) && isset( $_REQUEST['action'] ) && 'trash' == $_REQUEST['action'] ) {
			$_REQUEST['action'] = 'delete';
		}
	}


	/**
	 * Normalize order status / email columns
	 *
	 * @since 1.0.0
	 * @param array $columns
	 * @return array
	 */
	public function normalize_columns( $columns ) {

		// Change the title column name
		$columns['title'] = __( 'Name', WC_Order_Status_Manager::TEXT_DOMAIN );

		// Remove checkbox and date columns
		unset( $columns['cb'] );
		unset( $columns['date'] );

		return $columns;
	}


	/**
	 * Order status & email row actions
	 *
	 * @since 1.0.0
	 * @param array $actions
	 * @param WP_Post $post
	 * @return array
	 */
	public function normalize_row_actions( $actions, WP_Post $post ) {

		if ( $this->is_order_status_manager_post_type( get_post_type( $post->ID ) ) ) {

			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['trash'] );

			$order_status = new WC_Order_Status_Manager_Order_Status( $post->ID );

			if ( current_user_can( 'delete_post', $post->ID ) && ! $order_status->is_core_status() ) {
				$actions['delete'] = sprintf(
					'<a class="submitdelete" title="%s" href="%s">%s</a>',
					esc_attr__( 'Delete this item permanently', WC_Order_Status_Manager::TEXT_DOMAIN ),
					get_delete_post_link( $post->ID, '', true ),
					__( 'Delete Permanently', WC_Order_Status_Manager::TEXT_DOMAIN )
				);

			}
		}

		return $actions;
	}


	/**
	 * Hide title field and default publishing box
	 *
	 * @since 1.0.0
	 */
	public function normalize_edit_screen() {
		global $post;

		if ( $this->is_order_status_manager_post_type( get_post_type( $post->ID ) ) ) {
			?>
				<style type="text/css">
					#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv, #woocommerce-order-status-data .handlediv, #woocommerce-order-status-data h3.hndle, #woocommerce-order-status-email-data .handlediv, #woocommerce-order-status-email-data h3.hndle { display:none }
				</style>
			<?php
		}

	}


	/**
	 * Hide title field and default publishing box
	 *
	 * @since 1.0.0
	 */
	public function normalize_list_screen() {
		global $typenow;

		if ( $this->is_order_status_manager_post_type( $typenow ) ) {
			?>
				<style type="text/css">
					#posts-filter .search-box, #posts-filter .actions, #posts-filter .view-switch { display:none }
				</style>
			<?php
		}

	}


	/**
	 * Check if we're saving, then trigger an action based on the post type
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function save_meta_boxes( $post_id, WP_Post $post ) {

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves
		if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce
		if ( empty( $_POST['wc_order_status_manager_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wc_order_status_manager_meta_nonce'], 'wc_order_status_manager_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( $this->is_order_status_manager_post_type( $post->post_type ) ) {

			/**
			 * Process order status / email meta
			 *
			 * @param int $post_id
			 * @param object $post
			 */
			do_action( "wc_order_status_manager_process_{$post->post_type}_meta", $post_id, $post );
		}
	}


	/**
	 * Disable autosave for the Order Status Manager post types
	 *
	 * @since 1.0.0
	 */
	public function disable_autosave() {
		global $typenow;

		if ( $this->is_order_status_manager_post_type( $typenow ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}


	/**
	 * Check if the post type is either order status or order status email
	 *
	 * @since 1.0.0
	 * @param string $post_type
	 * @return bool
	 */
	private function is_order_status_manager_post_type( $post_type ) {

		return in_array( $post_type, array( 'wc_order_status', 'wc_order_email' ) );
	}


	/**
	 * Check if the current screen is one of order status manager screens
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_order_status_manager_screen() {

		if ( ! function_exists( 'get_current_screen') ) {
			return false;
		}

		$screen = get_current_screen();

		return in_array( $screen->id, array(
			'wc_order_status',
			'edit-wc_order_status',
			'wc_order_email',
			'edit-wc_order_email',
		) );
	}


	/**
	 * Add custom statuses to order reports
	 *
	 * @since 1.1.0
	 * @param array $report_statuses
	 * @return array $report_statuses
	 */
	public function reports_order_statuses( $report_statuses ) {

		// don't alter the order statuses if it's not an array or if 'refunded' is the only status
		if ( ! is_array( $report_statuses ) || ( 1 === count( $report_statuses ) && 'refunded' === $report_statuses[0] ) ) {
			return $report_statuses;
		}

		$order_statuses = new WC_Order_Status_Manager_Order_Statuses();

		$status_posts = get_posts( array(
			'post_type'      => 'wc_order_status',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $status_posts as $post_id ) {

			$status = new WC_Order_Status_Manager_Order_Status( $post_id );

			if ( $status->include_in_reports() ) {

				$report_statuses[] = $status->get_slug();

			} else {

				if ( ( $key = array_search( $status->get_slug(), $report_statuses ) ) !== false ) {
					unset( $report_statuses[ $key ] );
				}
			}
		}

		return $report_statuses;
	}


	/**
	 * Ensure orders with custom statuses are included in partial refund report caulculations
	 *
	 * @since 1.1.0
	 * @param array $args
	 * @return array $args
	 */
	public function order_report_data_args( $args ) {

		if ( isset( $args['parent_order_status'] ) && is_array( $args['parent_order_status'] ) ) {

			$order_statuses = new WC_Order_Status_Manager_Order_Statuses();

			$status_posts = get_posts( array(
				'post_type'      => 'wc_order_status',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );

			foreach ( $status_posts as $post_id ) {

				$status = new WC_Order_Status_Manager_Order_Status( $post_id );

				if ( $status->include_in_reports() ) {

					$args['parent_order_status'][] = $status->get_slug();

				} else {

					if ( ( $key = array_search( $status->get_slug(), $args['parent_order_status'] ) ) !== false ) {
						unset( $args['parent_order_status'][ $key ] );
					}
				}
			}
		}

		return $args;
	}


}
