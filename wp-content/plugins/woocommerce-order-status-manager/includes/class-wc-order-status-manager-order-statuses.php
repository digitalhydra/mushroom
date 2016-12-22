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
 * @package     WC-Order-Status-Manager/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2015, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Order Status Manager Order Statuses class
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_Order_Statuses {


	/** @var array Core/built-in/manually registered order statuses **/
	private $core_order_statuses = array();

	/** var string Previous order status slug. Used when changing an order status's slug. **/
	private $_previous_order_status_slug;

	/** var string Associative array of reassigned status. Used when deleting a status. **/
	private $_reassigned_statuses = array();


	/**
	 * Set up custom order statuses
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Include the Order Status class
		require_once( 'class-wc-order-status-manager-order-status.php' );

		// Store core order statuses before introducing custom order statuses
		$this->core_order_statuses = wc_get_order_statuses();

		// Add in custom order statuses
		add_filter( 'wc_order_statuses', array( $this, 'order_statuses' ) );

		// truncate slugs to 17 chars
		add_filter( 'wp_unique_post_slug', array( $this, 'truncate_order_status_slug' ), 10, 4 );

		// Handle order status changes
		add_action( 'pre_post_update', array( $this, 'queue_orders_update' ), 10, 2 );
		add_action( 'delete_post',     array( $this, 'handle_order_status_delete' ) );

	}


	/**
	 * Get core order statuses
	 *
	 * @since 1.0.0
	 * @return array of order status slug to name, ie 'wc-pending' => 'Pending Payment'
	 */
	public function get_core_order_statuses() {

		return $this->core_order_statuses;
	}


	/**
	 * Check if a status is a core status
	 *
	 * @since 1.0.0
	 * @param string $status status slug (with or without prefix)
	 * @return boolean true if this is a core status
	 */
	public function is_core_status( $status ) {

		$status              = str_replace( 'wc-', '', $status );
		$core_order_statuses = $this->get_core_order_statuses();

		return isset( $core_order_statuses[ 'wc-' . $status ] );
	}


	/**
	 * Get order status posts
	 *
	 * @since 1.0.0
	 * @return array of WP_Post objects
	 */
	public function get_order_status_posts() {

		return get_posts( array(
			'post_type'        => 'wc_order_status',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'suppress_filters' => 1
		) );
	}


	/**
	 * Ensure that all wc order statuses have posts associated with them
	 *
	 * This way, all statuses are customizable
	 *
	 * @since 1.0.0
	 */
	public function ensure_statuses_have_posts() {

		$status_posts = $this->get_order_status_posts();

		foreach ( wc_get_order_statuses() as $slug => $name ) {

			$slug_without_prefix = str_replace( 'wc-', '', $slug );
			$has_post = false;

			foreach ( $status_posts as $status_post ) {

				if ( $slug_without_prefix == $status_post->post_name ) {
					$has_post = true;
					break;
				}
			}

			if ( ! $has_post ) {
				$this->create_post_for_status( $slug_without_prefix, $name );
			}
		}
	}


	/**
	 * Create a custom post type (wc_order_status) for an order status
	 *
	 * @since 1.0.0
	 * @param string $slug Slug. Example: `processing`
	 * @param string $name Name. Example: `Processing`
	 * @return int Post ID
	 */
	public function create_post_for_status( $slug, $name ) {

		$post_id = wp_insert_post( array(
			'post_name'   => $slug,
			'post_title'  => $name,
			'post_type'   => 'wc_order_status',
			'post_status' => 'publish'
		) );

		$core_order_statuses = $this->get_core_order_statuses();

		// Create default settings for core statuses. These are set here
		// manually based on WC core statuses implementation
		if ( isset( $core_order_statuses[ 'wc-' . $slug ] ) ) {

			switch ( $slug ) {

				case 'pending':
					update_post_meta( $post_id, '_icon', 'wcicon-status-pending' );
					update_post_meta( $post_id, '_color', '#ffba00' );
					update_post_meta( $post_id, '_next_statuses', array( 'processing', 'completed' ) );
				break;

				case 'processing':
					update_post_meta( $post_id, '_icon', 'wcicon-status-processing' );
					update_post_meta( $post_id, '_color', '#73a724' );
					update_post_meta( $post_id, '_action_icon', 'wcicon-processing' );
					update_post_meta( $post_id, '_next_statuses', array( 'completed' ) );
					update_post_meta( $post_id, '_include_in_reports', 'yes' );
				break;

				case 'on-hold':
					update_post_meta( $post_id, '_icon',  'wcicon-on-hold' );
					update_post_meta( $post_id, '_color', '#999999' );
					update_post_meta( $post_id, '_next_statuses', array( 'processing', 'completed' ) );
					update_post_meta( $post_id, '_include_in_reports', 'yes' );
				break;

				case 'completed':
					update_post_meta( $post_id, '_icon',  'wcicon-status-completed' );
					update_post_meta( $post_id, '_color', '#2ea2cc' );
					update_post_meta( $post_id, '_action_icon', 'wcicon-check' );
					update_post_meta( $post_id, '_include_in_reports', 'yes' );
				break;

				case 'cancelled':
					update_post_meta( $post_id, '_icon', 'wcicon-status-cancelled' );
					update_post_meta( $post_id, '_color', '#aa0000' );
				break;

				case 'refunded':
					update_post_meta( $post_id, '_icon', 'wcicon-status-refunded' );
					update_post_meta( $post_id, '_color', '#999999' );
					update_post_meta( $post_id, '_include_in_reports', 'yes' );
				break;

				case 'failed':
					update_post_meta( $post_id, '_icon', 'wcicon-status-failed' );
					update_post_meta( $post_id, '_color', '#d0c21f' );
				break;

			}
		}

		return $post_id;
	}


	/**
	 * Add custom order statuses to wc order statuses
	 *
	 * @since 1.0.0
	 * @param array $order_statuses
	 * @return array
	 */
	public function order_statuses( array $order_statuses ) {

		foreach ( $this->get_order_status_posts() as $status ) {

			$order_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
		}

		return $order_statuses;
	}


	/**
	 * Truncate the order status slug to a maximum of 17 characters
	 *
	 * @since 1.1.1
	 * @param string $slug          The post slug.
	 * @param int    $post_ID       Post ID.
	 * @param string $post_status   The post status.
	 * @param string $post_type     Post type.
	 */
	public function truncate_order_status_slug( $slug, $post_ID, $post_status, $post_type ) {

		$max_slug_length = 17;

		if ( 'wc_order_status' !== $post_type ) {
			return $slug;
		}

		if ( strlen( $slug ) <= $max_slug_length ) {
			return $slug;
		}

		$slug = _truncate_post_slug( $slug, $max_slug_length );

		// The following was borrowed from WP core function wp_unique_post_slug()
		global $wpdb;

		// Post slugs must be unique across all posts.
		$check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_type, $post_ID ) );

		if ( $post_name_check ) {
			$suffix = 2;
			do {
				$alt_post_name = _truncate_post_slug( $slug, $max_slug_length - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_type, $post_ID ) );
				$suffix++;
			} while ( $post_name_check );
			$slug = $alt_post_name;
		}

		return $slug;
	}


	/**
	 * Queue orders to be updated when status slug is changed
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param array $data
	 */
	public function queue_orders_update( $post_id, $data ) {

		// Skip if doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		// Sanity check
		if ( ! $post_id || ! isset( $data['post_name'] ) || ! $data['post_name'] ) {
			return;
		}

		// Bail out if not an order status
		if ( 'wc_order_status' !== get_post_type( $post_id ) ) {
			return;
		}

		$order_status = new WC_Order_Status_Manager_Order_Status( $post_id );

		// Bail out if order status does not exist
		if ( ! $order_status->get_id() ) {
			return;
		}

		// If the slug has changed, queue orders to be updated
		if ( $data['post_name'] !== $order_status->get_slug() ) {

			$this->_previous_order_status_slug = $order_status->get_slug();

			add_action( 'save_post_wc_order_status', array( $this, 'handle_slug_change' ), 10, 2 );
		}

	}


	/**
	 * Handle order status slug change
	 *
	 * This function will find any orders with the previous slug
	 * and update them with the new slug. It also updates the slug
	 * in any "next statuses".
	 *
	 * @since 1.0.0
	 * @param int $post_id the order status post id
	 * @param WP_Post $post the order status post object
	 */
	public function handle_slug_change( $post_id, WP_Post $post ) {

		// Check if the previous slug was stored
		if ( ! $this->_previous_order_status_slug ) {
			return;
		}

		$order_status = new WC_Order_Status_Manager_Order_Status( $post_id );

		global $wpdb;

		$wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->posts}
			SET post_status = %s
			WHERE post_type = 'shop_order'
			AND post_status = %s
		", $order_status->get_slug( true ), 'wc-' . $this->_previous_order_status_slug ) );

		// If any other order statuses have specified this status
		// as a 'next status', update the slug in their meta, so that
		// the 'next status' keeps functioning
		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT pm.post_id
			FROM {$wpdb->postmeta} pm
			RIGHT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE post_type = 'wc_order_status'
			AND meta_key = '_next_statuses'
			AND meta_value LIKE %s
		", '%' . $wpdb->esc_like( $this->_previous_order_status_slug ) . '%' ) );

		if ( $rows ) {
			foreach ( $rows as $row ) {

				$next_statuses = get_post_meta( $row->post_id, '_next_statuses', true );

				// Update the next status slug
				if ( ( $key = array_search( $this->_previous_order_status_slug, $next_statuses ) ) !== false ) {
					$next_statuses[ $key ] = $order_status->get_slug();
				}

				update_post_meta( $row->post_id, '_next_statuses', $next_statuses );
			}
		}

	}


	/**
	 * Handle deleting an order status
	 *
	 * Will assign all orders that have the to-be deleted status
	 * a replacement status, which defaults to `wc-on-hold`.
	 * Also removes the status form any next statuses.
	 *
	 * @since 1.0.0
	 * @param int $post_id the order status post id
	 */
	public function handle_order_status_delete( $post_id ) {

		global $wpdb;

		// Bail out if not an order status
		if ( 'wc_order_status' !== get_post_type( $post_id ) ) {
			return;
		}

		$order_status = new WC_Order_Status_Manager_Order_Status( $post_id );

		if ( ! $order_status->get_id() ) {
			return;
		}

		/**
		 * Filter the replacement status when an order status is deleted
		 *
		 * This filter is applied just before the order status is deleted,
		 * but after the order status meta has already been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param string $replacement Replacement order status slug.
		 * @param string $original Original order status slug.
		 */
		$replacement_status = apply_filters( 'wc_order_status_manager_deleted_status_replacement', 'on-hold', $order_status->get_slug() );
		$replacement_status = str_replace( 'wc-', '', $replacement_status );

		$old_status_name = $order_status->get_name();

		$order_rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID FROM {$wpdb->posts}
			WHERE post_type = 'shop_order' AND post_status = %s
		", $order_status->get_slug( true ) ), ARRAY_A );

		$num_updated = 0;

		if ( ! empty( $order_rows ) ) {

			foreach ( $order_rows as $order_row ) {

				$order = SV_WC_Plugin_Compatibility::wc_get_order( $order_row['ID'] );

				$order->update_status( $replacement_status, __( "Order status updated because the previous status was deleted.", WC_Order_Status_Manager::TEXT_DOMAIN ) );

				$num_updated++;
			}

		}

		// If any other order statuses have specified this status
		// as a 'next status', remove it from there
		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT pm.post_id
			FROM {$wpdb->postmeta} pm
			RIGHT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE post_type = 'wc_order_status'
			AND meta_key = '_next_statuses'
			AND meta_value LIKE %s
		", '%' . $wpdb->esc_like( $order_status->get_slug() ) . '%' ) );

		if ( $rows ) {
			foreach ( $rows as $row ) {

				$next_statuses = get_post_meta( $row->post_id, '_next_statuses', true );

				// Remove the next status slug
				if ( ( $key = array_search( $order_status->get_slug(), $next_statuses ) ) !== false ) {
					unset( $next_statuses[ $key ] );
				}

				update_post_meta( $row->post_id, '_next_statuses', $next_statuses );
			}
		}

		// Add admin notice
		if ( $num_updated && is_admin() && ! defined( 'DOING_AJAX' ) ) {

			$new_status = new WC_Order_Status_Manager_Order_Status( $replacement_status );

			$message = sprintf( _n(
					'%d order that was previously %s is now %s.',
					'%d orders that were previously %s are now %s.',
					$num_updated,
					WC_Order_Status_Manager::TEXT_DOMAIN
				), $num_updated, esc_html( $old_status_name ), esc_html( $new_status->get_name() ) );

			wc_order_status_manager()->get_message_handler()->add_message( $message );

		}
	}


}
