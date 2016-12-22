<?php
/*
Plugin Name: WooCommerce Bulk Stock Management
Plugin URI: http://www.woothemes.com/products/bulk-stock-management/
Description: Bulk edit stock levels and print out stock reports right from WooCommerce admin.
Version: 1.9.3
Author: Mike Jolley
Author URI: http://mikejolley.com

Copyright: © 2009-2014 WooThemes.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if ( ! class_exists( 'WC_Stock_Management_List_Table' ) ) {
	require_once( 'classes/class-wc-stock-management-list-table.php' );
}
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '02f4328d52f324ebe06a78eaaae7934f', '18670' );

if ( is_woocommerce_active() && ! class_exists( 'WC_Bulk_Stock_Management' ) ) {

	/**
	 * WC_Bulk_Stock_Management class
	 */
	class WC_Bulk_Stock_Management {

		private $messages = array();

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'load_plugin_textdomain') );
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'init', array( $this, 'print_stock_report') );
			add_action( 'init', array( $this, 'process_qty') );
		}

		/**
		 * Handle localisation
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-bulk-stock-management', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Enqueue styles
		 */
		public function admin_css() {
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
			wp_enqueue_style( 'woocommerce_stock_management_css', plugins_url( basename( dirname( __FILE__ ) ) ) . '/css/admin.css' );
		}

		/**
		 * Add menus to WP admin
		 */
		public function register_menu() {
			$cap  = apply_filters( 'wc_bulk_stock_cap', 'edit_others_products' );
			$page = add_submenu_page( 'edit.php?post_type=product', __( 'Stock Management', 'woocommerce-bulk-stock-management' ), __( 'Stock Management', 'woocommerce-bulk-stock-management' ), $cap, 'woocommerce-bulk-stock-management', array( $this, 'stock_management_page' ) );

			add_action( 'admin_print_styles-' . $page, array( $this, 'admin_css' ) );
		}

		/**
		 * Output the stock management page
		 */
		public function stock_management_page() {
		    $list_table = new WC_Stock_Management_List_Table();
		    $list_table->prepare_items();
		    ?>
		    <div class="wrap">
		        <h2><?php _e('Stock Management', 'woocommerce-bulk-stock-management' ); ?> <a href="<?php echo wp_nonce_url( add_query_arg( 'print', 'stock_report' ), 'print-stock' ) ?>" class="add-new-h2"><?php _e('View stock report', 'woocommerce-bulk-stock-management'); ?></a></h2>
		        <form id="stock-management" method="post">
		        	<?php
		        		if ( $this->messages ) {
		        			echo '<div class="updated">';

		        			foreach ( $this->messages as $message ) {
		        				echo '<p>' . $message . '</p>';
		        			}

		        			echo '</div>';
		        		}
		        	?>
		            <input type="hidden" name="post_type" value="product" />
		            <input type="hidden" name="page" value="wc_stock_management" />
		            <?php $list_table->display() ?>
		            <?php wp_nonce_field( 'save', 'wc-stock-management' ); ?>
		        </form>
		    </div>
		    <?php
		}

		/**
		 * Output the stock report table
		 */
		public function print_stock_report() {
			if ( ! empty( $_GET['print'] ) && $_GET['print'] == 'stock_report' ) {

				check_admin_referer( 'print-stock' );

				ob_start();

		  		include( apply_filters( 'wc_stock_report_template', plugin_dir_path( __FILE__ ) . 'templates/stock-report.php' ) );

		  		$content = ob_get_clean();

		  		echo $content;

		  		die();
			}
		}

		/**
		 * Save quantities when form is posted
		 */
		public function process_qty() {
			if ( ! empty( $_POST['stock_quantity'] ) && ! empty( $_POST['save_stock'] ) ) {

				check_admin_referer( 'save', 'wc-stock-management' );

				$quantities 		= $_POST['stock_quantity'];
				$current_quantities = $_POST['current_stock_quantity'];

				foreach ( $quantities as $id => $qty ) {
					if ( $qty == '' ) {
						continue;
					}

					if ( isset( $current_quantities[$id] ) ) {

						// Check the qty has not changed since showing the form
						$current_stock = apply_filters( 'woocommerce_stock_amount', get_post_meta( $id, '_stock', true ) );

						if ( $current_stock == $current_quantities[$id] ) {

							do_action( 'wc_bulk_stock_before_process_qty', $id );

							$post = get_post( $id );

							// Format $qty
							$qty = apply_filters( 'woocommerce_stock_amount', $qty );

							// Update stock amount
							update_post_meta( $id, '_stock', $qty );

							// Update stock status
							update_post_meta( $id, '_manage_stock', 'yes' );

							$product = get_product( $post->ID );

							if ( $product->managing_stock() && ! $product->backorders_allowed() && $product->get_total_stock() <= 0 ) {
								update_post_meta( $post->ID, '_stock_status', 'outofstock' );
							} elseif ( $product->managing_stock() && ( $product->backorders_allowed() || $product->get_total_stock() > 0 ) ) {
								update_post_meta( $post->ID, '_stock_status', 'instock' );
							}

							wc_delete_product_transients( $post->ID );

							do_action( 'edit_post', $post->ID, $post );
							do_action( 'wc_bulk_stock_after_process_qty', $id );

						} else {
							$this->messages[] = sprintf( __( 'Product # %s was not updated - the stock amount has changed since posting.', 'woocommerce-bulk-stock-management' ), $id );
						}
					}
				}

				$this->messages[] = __( 'Stock quantities saved.', 'woocommerce-bulk-stock-management' );
			}
		}

	}
	$GLOBALS['WC_Bulk_Stock_Management'] = new WC_Bulk_Stock_Management();
}