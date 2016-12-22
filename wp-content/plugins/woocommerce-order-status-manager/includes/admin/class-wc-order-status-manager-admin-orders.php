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
 * Order Status Manager Orders Admin
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_Admin_Orders {


	/**
	 * Setup admin class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add order status "next" actions
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'custom_order_actions' ), 10, 2 );

		add_action( 'admin_head', array( $this, 'custom_order_status_icons' ) );

		add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ), 10 );

		// TODO: Do not hook this if on > 2.3.x
		add_action( 'load-edit.php', array( $this, 'bulk_action' ) );

		// TODO: Do not hook this if on > 2.3.x
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
	}


	/**
	 * Add custom order actions in order list view
	 *
	 * @since 1.0.0
	 * @param array $actions
	 * @param \WC_Order $order
	 * @return array
	 */
	public function custom_order_actions( $actions, WC_Order $order ) {

		$status = new WC_Order_Status_Manager_Order_Status( $order->get_status() );

		// Sanity check: bail if no status was found.
		// This can happen if some statuses are registered late
		if ( ! $status || ! $status->get_id() ) {
			return $actions;
		}

		$next_statuses  = $status->get_next_statuses();
		$order_statuses = wc_get_order_statuses();
		$custom_actions = array();

		// TODO: Change action to `woocommerce_mark_order_status` for 2.3.x compatibility
		// TODO: Change nonce_action to `woocommerce-mark-order-status` for 2.3.x compatibility
		$action       = 'wc_order_status_manager_mark_order_status';
		$nonce_action = 'wc-order-status-manager-mark-order-status';

		// Add next statuses as actions
		if ( ! empty( $next_statuses ) ) {

			foreach ( $next_statuses as $next_status ) {

				$custom_actions[ $next_status ] = array(
					'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=' . $action . '&status=' . $next_status . '&order_id=' . $order->id ), $nonce_action ),
					'name'   => $order_statuses[ 'wc-' . $next_status ],
					'action' => $next_status,
				);

				// Remove duplicate built-in complete action
				if ( 'completed' === $next_status ) {
					unset( $actions['complete'] );
				}
			}
		}

		// Next status actions are prepended to any existing actions
		return $custom_actions + $actions;
	}


	/**
	 * Print styles for custom order status icons
	 *
	 * @since 1.0.0
	 */
	public function custom_order_status_icons() {

		$custom_status_colors = array();
		$custom_status_badges = array();
		$custom_status_icons  = array();
		$custom_action_icons  = array();

		foreach ( wc_get_order_statuses() as $slug => $name ) {

			$status = new WC_Order_Status_Manager_Order_Status( $slug );

			// Sanity check: bail if no status was found.
			// This can happen if some statuses are registered late
			if ( ! $status || ! $status->get_id() ) {
				continue;
			}

			$color       = $status->get_color();
			$icon        = $status->get_icon();
			$action_icon = $status->get_action_icon();

			if ( $color ) {
				$custom_status_colors[ esc_attr( $status->get_slug() ) ] = $color;
			}

			// Font icon
			if ( $icon && $icon_details = wc_order_status_manager()->icons->get_icon_details( $icon ) ) {
				$custom_status_icons[ esc_attr( $status->get_slug() ) ] = $icon_details;
			}

			// Image icon
			elseif ( is_numeric( $icon ) && $icon_src = wp_get_attachment_image_src( $icon, 'wc_order_status_icon' ) ) {
				$custom_status_icons[ esc_attr( $status->get_slug() ) ] = $icon_src[0];
			}

			// Badge
			elseif ( ! $icon ) {
				$custom_status_badges[] = esc_attr( $status->get_slug() );
			}

			// Font action icon
			if ( $action_icon && $action_icon_details = wc_order_status_manager()->icons->get_icon_details( $action_icon ) ) {
				$custom_action_icons[ esc_attr( $status->get_slug() ) ] = $action_icon_details;
			}

			// Image action icon
			elseif ( is_numeric( $action_icon ) && $action_icon_src = wp_get_attachment_image_src( $action_icon, 'wc_order_status_icon' ) ) {
				$custom_action_icons[ esc_attr( $status->get_slug() ) ] = $action_icon_src[0];
			}

		}

		?>
		<!-- Custom Order Status Icon styles -->
		<style type="text/css">
			/*<![CDATA[*/

			<?php // General styles for status badges ?>
			<?php if ( ! empty( $custom_status_badges ) ) : ?>
				.widefat .column-order_status mark.<?php echo implode( ', .widefat .column-order_status mark.', $custom_status_badges ); ?> {
					display: inline-block;
					font-size: 0.8em;
					line-height: 1.1;
					text-indent: 0;
					background-color: #666;
					width: auto;
					height: auto;
					padding: 0.4em;
					color: #fff;
					border-radius: 2px;
					word-wrap: break-word;
					max-width: 100%;
				}

				.widefat .column-order_status mark.<?php echo implode( ':after, .widefat .column-order_status mark.', $custom_status_badges ); ?>:after {
					display: none;
				}
			<?php endif; ?>

			<?php // General styles for status icons ?>
			<?php if ( ! empty( $custom_status_icons ) ) : ?>

				<?php $custom_status_font_icons = array_filter( $custom_status_icons, 'is_array' ); ?>

				<?php if ( ! empty( $custom_status_font_icons ) ) : ?>

					.widefat .column-order_status mark.<?php echo implode( ':after, .widefat .column-order_status mark.', array_keys( $custom_status_font_icons ) ); ?>:after {
						speak: none;
						font-weight: normal;
						font-variant: normal;
						text-transform: none;
						line-height: 1;
						-webkit-font-smoothing: antialiased;
						margin: 0;
						text-indent: 0;
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
						text-align: center;
					}

				<?php endif; ?>
			<?php endif; ?>

			<?php // General styles for action icons ?>
			.widefat .column-order_actions a.button {
				padding: 0 0.5em;
				height: 2em;
				line-height: 1.9em;
			}

			<?php if ( ! empty( $custom_action_icons ) ) : ?>

				<?php $custom_action_font_icons = array_filter( $custom_action_icons, 'is_array' ); ?>
				<?php if ( ! empty( $custom_action_font_icons ) ) : ?>

					.order_actions .<?php echo implode( ', .order_actions .', array_keys( $custom_action_icons ) ); ?> {
						display: block;
						text-indent: -9999px;
						position: relative;
						padding: 0!important;
						height: 2em!important;
						width: 2em;
					}
					.order_actions .<?php echo implode( ':after, .order_actions .', array_keys( $custom_action_icons ) ); ?>:after {
						speak: none;
						font-weight: 400;
						font-variant: normal;
						text-transform: none;
						-webkit-font-smoothing: antialiased;
						margin: 0;
						text-indent: 0;
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
						text-align: center;
						line-height: 1.85;
					}

				<?php endif; ?>
			<?php endif; ?>

			<?php // Specific status icon styles ?>
			<?php if ( ! empty( $custom_status_icons ) ) : ?>
				<?php foreach ( $custom_status_icons as $status => $value ) : ?>

					<?php if ( is_array( $value ) ) : ?>
						.widefat .column-order_status mark.<?php echo $status; ?>:after {
							font-family: "<?php echo $value['font']; ?>";
							content:     "<?php echo $value['glyph']; ?>";
						}
					<?php else : ?>
						.widefat .column-order_status mark.<?php echo $status; ?> {
							background-size: 100% 100%;
							background-image: url( <?php echo $value; ?> );
						}
					<?php endif; ?>

				<?php endforeach; ?>
			<?php endif; ?>

			<?php // Specific status color styles ?>
			<?php if ( ! empty( $custom_status_colors ) ) : ?>
				<?php foreach ( $custom_status_colors as $status => $color ) : ?>

					<?php if ( in_array( $status, $custom_status_badges ) ) : ?>
						.widefat .column-order_status mark.<?php echo $status; ?> {
							background-color: <?php echo $color; ?>;
							color: <?php echo wc_order_status_manager()->icons->get_contrast_text_color( $color ); ?>;
						}
					<?php endif; ?>

					<?php if ( isset( $custom_status_icons[$status] ) ) : ?>
						.widefat .column-order_status mark.<?php echo $status; ?>:after {
							color: <?php echo $color; ?>;
						}
					<?php endif; ?>

				<?php endforeach; ?>
			<?php endif; ?>

			<?php // Specific  action icon styles ?>
			<?php if ( ! empty( $custom_action_icons ) ) : ?>
				<?php foreach ( $custom_action_icons as $status => $value ) : ?>

					<?php if ( is_array( $value ) ) : ?>
						.order_actions .<?php echo $status; ?>:after {
							font-family: "<?php echo $value['font']; ?>";
							content:     "<?php echo $value['glyph']; ?>";
						}
					<?php else : ?>
						.order_actions .<?php echo $status; ?>,
						.order_actions .<?php echo $status; ?>:focus,
						.order_actions .<?php echo $status; ?>:hover {
							background-size: 69% 69%;
							background-position: center center;
							background-repeat: no-repeat;
							background-image: url( <?php echo $value; ?> );
						}
					<?php endif; ?>

				<?php endforeach; ?>
			<?php endif; ?>

			/*]]>*/
		</style>
		<?php
	}


	/**
	 * Add extra bulk action options to mark orders with custom statuses
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @since 1.0.0
	 */
	public function bulk_admin_footer() {
		global $post_type;

		if ( 'shop_order' == $post_type ) {

			$custom_order_statuses = get_posts( array(
				'post_type'      => 'wc_order_status',
				'posts_per_page' => -1,
				'meta_key'       => '_bulk_action',
				'meta_value'     => 'yes',
			) );

			if ( ! $custom_order_statuses ) {
				return;
			}

			?>
			<script type="text/javascript">
			jQuery(function() {
				<?php foreach ( $custom_order_statuses as $status ) : ?>
					jQuery( '<option>' ).val( 'mark_<?php echo sanitize_html_class( $status->post_name ); ?>' ).text( '<?php printf( __( "Mark %s", WC_Order_Status_Manager::TEXT_DOMAIN ), strtolower( get_the_title( $status->ID ) ) ); ?>' ).appendTo( "select[name='action']" );
					jQuery( '<option>' ).val( 'mark_<?php echo sanitize_html_class( $status->post_name ); ?>' ).text( '<?php printf( __( "Mark %s", WC_Order_Status_Manager::TEXT_DOMAIN ), strtolower( get_the_title( $status->ID ) ) ); ?>' ).appendTo( "select[name='action2']" );
				<?php endforeach; ?>
			});
			</script>
			<?php
		}
	}


	/**
	 * Process the new bulk actions for changing order status
	 *
	 * TODO: remove once 2.2 support is dropped {@link https://github.com/woothemes/woocommerce/pull/6791}
	 *
	 * @since 1.0.0
	 */
	public function bulk_action() {

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();
		$builtin       = array( 'mark_complete', 'mark_processing', 'mark_on-hold' );

		// Skip if the current action is a built-in WC action
		if ( in_array( $action, $builtin ) ) {
			return;
		}

		// Bail out if this is not a status-changing action
		if ( strpos( $action, 'mark_' ) === false ) {
			return;
		}

		$order_statuses = wc_get_order_statuses();

		$new_status    = substr( $action, 5 );
		$report_action = 'marked_' . $new_status;

		// Sanity check: bail out if this is a non-registered order status
		if ( ! isset( $order_statuses[ 'wc-' . $new_status ] ) ) {
			return;
		}

		$changed = 0;

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		foreach ( $post_ids as $post_id ) {
			$order = wc_get_order( $post_id );
			$order->update_status( $new_status, __( 'Order status changed by bulk edit:', WC_Order_Status_Manager::TEXT_DOMAIN ) );
			$changed++;
		}

		$sendback = add_query_arg( array( 'post_type' => 'shop_order', $report_action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );
		wp_redirect( $sendback );
		exit;
	}


	/**
	 * Show confirmation message that order status changed for number of orders
	 *
	 * TODO: remove once 2.2 support is dropped {@link https://github.com/woothemes/woocommerce/pull/6791}
	 *
	 * @since 1.0.0
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order edit page
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) {
			return;
		}

		// Bail out if dealing with built-in bulk actions
		if ( isset( $_REQUEST['marked_complete'] ) || isset( $_REQUEST['marked_processing'] ) || isset( $_REQUEST['marked_on-hold'] ) ) {
			return;
		}

		$order_statuses = wc_get_order_statuses();

		// Check if any status changes happened
		foreach ( $order_statuses as $slug => $name ) {

			if ( isset( $_REQUEST[ 'marked_' . str_replace( 'wc-', '', $slug ) ] ) ) {

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
				$message = sprintf( _n( 'Order status changed.', '%s order statuses changed.', $number, WC_Order_Status_Manager::TEXT_DOMAIN ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . $message . '</p></div>';

				break;
			}
		}
	}


}
