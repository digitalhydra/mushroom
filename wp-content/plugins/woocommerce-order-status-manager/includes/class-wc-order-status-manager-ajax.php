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
 * Order Status Manager AJAX handler
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_AJAX {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_wc_order_status_manager_mark_order_status',  array( $this, 'mark_order_status' ) );
		add_action( 'wp_ajax_wc_order_status_manager_get_icon_image_url', array( $this, 'render_icon_image_url' ) );
	}


	/**
	 * Mark an order with a status
	 *
	 * TODO: Remove once 2.2 compatibility is dropped {@link https://github.com/woothemes/woocommerce/pull/6791}
	 *
	 * @since 1.0.0
	 */
	public static function mark_order_status() {

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', WC_Order_Status_Manager::TEXT_DOMAIN ), '', array( 'response' => 403 ) );
		}

		if ( ! check_admin_referer( 'wc-order-status-manager-mark-order-status' ) ) {
			wp_die( __( 'You have taken too long. Please go back and retry.', WC_Order_Status_Manager::TEXT_DOMAIN ), '', array( 'response' => 403 ) );
		}

		$status = isset( $_GET['status'] ) ? esc_attr( $_GET['status'] ) : '';
		$order_statuses = wc_get_order_statuses();

		if ( ! $status || ! isset( $order_statuses[ 'wc-' . $status ] ) ) {
			die;
		}

		$order_id = isset( $_GET['order_id'] ) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
		if ( ! $order_id ) {
			die;
		}

		$order = wc_get_order( $order_id );
		$order->update_status( $status );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );

		die;
	}


	/**
	 * Render the icon image attachment src
	 *
	 * @since 1.0.0
	 */
	public function render_icon_image_url() {


		if ( ! isset( $_REQUEST[ 'attachment_id' ] ) ) {
			return;
		}

		$icon_attachment_src = wp_get_attachment_image_src( $_REQUEST['attachment_id'], 'wc_order_status_icon' );

		if ( empty( $icon_attachment_src ) ) {
			return;
		}

		echo $icon_attachment_src[0];

		die;
	}


}
