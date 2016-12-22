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
 * Order Status Manager Emails Admin
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_Admin_Order_Status_Emails {


	/** array possible email types **/
	protected $email_types = array();


	/**
	 * Setup admin class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->email_types = array(
			'customer' => __( 'Customer', WC_Order_Status_Manager::TEXT_DOMAIN ),
			'admin'    => __( 'Admin', WC_Order_Status_Manager::TEXT_DOMAIN ),
		);

		add_filter( 'manage_edit-wc_order_email_columns', array( $this, 'order_status_email_columns' ) );

		add_filter( 'post_row_actions', array( $this, 'order_status_email_actions' ), 10, 2 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'wc_order_status_manager_process_wc_order_email_meta', array( $this, 'save_order_status_email_meta' ), 10, 2 );

		add_action( 'manage_wc_order_email_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );

	}


	/**
	 * Customize order status email columns
	 *
	 * @since 1.0.0
	 * @param array $columns
	 * @return array
	 */
	public function order_status_email_columns( $columns ) {

		$columns['type']        = __( 'Type', WC_Order_Status_Manager::TEXT_DOMAIN );
		$columns['description'] = __( 'Description', WC_Order_Status_Manager::TEXT_DOMAIN );
		$columns['status']      = __( 'Status', WC_Order_Status_Manager::TEXT_DOMAIN );

		return $columns;
	}


	/**
	 * Customize order status email row actions
	 *
	 * @since 1.0.0
	 * @param array $actions
	 * @param WP_Post $post
	 * @return array
	 */
	public function order_status_email_actions( $actions, WP_Post $post ) {

		$actions['customize_email'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			esc_attr__( 'Customize Email', WC_Order_Status_Manager::TEXT_DOMAIN ),
			admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_order_status_email_' . esc_attr( $post->ID ) ),
			__( 'Customize Email', WC_Order_Status_Manager::TEXT_DOMAIN )
		);

		return $actions;
	}


	/**
	 * Add meta boxes to the order status email edit page
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {

		// Order Status data meta box
		add_meta_box(
			'woocommerce-order-status-email-data',
			__( 'Order Status Email Data', WC_Order_Status_Manager::TEXT_DOMAIN ),
			array( $this, 'order_status_email_data_meta_box' ),
			'wc_order_email',
			'normal',
			'high'
		);

		// Order Status actions meta box
		add_meta_box(
			'woocommerce-order-status-email-actions',
			__( 'Order Status Email Actions', WC_Order_Status_Manager::TEXT_DOMAIN ),
			array( $this, 'order_status_email_actions_meta_box' ),
			'wc_order_email',
			'side',
			'high'
		);

		remove_meta_box( 'slugdiv', 'wc_order_email', 'normal' );
	}


	/**
	 * Display the order status email data meta box
	 *
	 * @since 1.0.0
	 */
	public function order_status_email_data_meta_box() {
		global $post;

		wp_nonce_field( 'wc_order_status_manager_save_data', 'wc_order_status_manager_meta_nonce' );
		?>

		<div id="order_status_email_options" class="panel woocommerce_options_panel">
			<div class="options_group">

			<?php
			// Status Email Name
			woocommerce_wp_text_input( array(
				'id'    => 'post_title',
				'label' => __( 'Name', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'value' => $post->post_title,
			) );

			// Status Email Type
			woocommerce_wp_select( array(
				'id'          => '_email_type',
				'label'       => __( 'Type', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'options'     => $this->email_types,
				'desc_tip'    => true,
				'description' => __( "A customer email is dispatched to the order's customer, and admin email is sent to the store admin (you can define individual recipient's).", WC_Order_Status_Manager::TEXT_DOMAIN ),
			) );

			// Status Email Description
			woocommerce_wp_textarea_input( array(
				'id'          => 'post_excerpt',
				'label'       => __( 'Description', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'desc_tip'    => true,
				'description' => __( 'Optional email description. This is for informational purposes only.', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'value'       => htmlspecialchars_decode( $post->post_excerpt, ENT_QUOTES ),
			) );

			// Status Email Dispatch conditions
			// TODO: Should we prefix 'any' with an underscore, or somehow reserve it?
			$status_options = array(
				'any' => __( 'Any', WC_Order_Status_Manager::TEXT_DOMAIN )
			);

			foreach ( wc_get_order_statuses() as $slug => $name ) {
				$status_options[ str_replace( 'wc-', '', $slug ) ] = $name;
			}

			$conditions = get_post_meta( $post->ID, '_email_dispatch_condition' );

			// Parse existing condition parts
			if ( ! empty( $conditions ) ) {

				foreach ( $conditions as $key => $condition ) {
					$parts = explode( '_to_', $condition );
					$conditions[ $key ] = array(
						'from' => $parts[0],
						'to'   => $parts[1],
					);
				}
			}

			?>
			<fieldset class="form-field dispatch_field">
				<label for="_email_dispatch_condition"><?php _e( 'When to dispatch', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></label>

				<table class="dispatch_conditions">

					<thead <?php if ( empty( $conditions ) ) : ?>style="display:none;"<?php endif; ?>>
						<tr>
							<th><?php _e( 'From Status', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></th>
							<th colspan="2"><?php _e( 'To Status', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>

					<tbody <?php if ( empty( $conditions ) ) : ?>style="display:none;"<?php endif; ?>>
						<?php if ( ! empty( $conditions ) ) : foreach ( $conditions as $key => $condition ) : ?>
						<tr class="condition">
							<td>
								<select name="_email_dispatch_condition[<?php echo $key; ?>][from]">
									<?php foreach ( $status_options as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $condition['from'] ); ?>><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="_email_dispatch_condition[<?php echo $key; ?>][to]">
									<?php foreach ( $status_options as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $condition['to'] ); ?>><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<button type="button" class="button remove-condition"><?php _e( 'Remove', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></button>
							</td>
						</tr>
						<?php endforeach; endif; ?>
					</tbody>

					<tfoot>
						<td colspan="3">
							<button type="button" class="button add-condition"><?php _e( 'Add Condition', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></button>
						</td>
					</tfoot>

				</table>

			</fieldset>

			</div><!-- // .options_group -->
		</div><!-- // .woocommerce_options_panel -->
		<?php
	}


	/**
	 * Display the order status email actions meta box
	 *
	 * @since 1.0.0
	 */
	public function order_status_email_actions_meta_box() {
		global $post, $pagenow;

		?>
		<ul class="order_status_email_actions submitbox">

			<?php
				/**
				 * Fires at the start of the order status email actions meta box
				 *
				 * @since 1.0.0
				 * @param int $post_id The post id of the wc_order_email post
				 */
				do_action( 'wc_order_status_manager_order_status_email_actions_start', $post->ID );
			?>

			<?php if ( 'post-new.php' !== $pagenow ) : ?>
				<li class="wide"><a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_order_status_email_' . esc_attr( $post->ID ) ); ?>"><?php _e( 'Customize Email', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></a></li>
			<?php endif; ?>

			<li class="wide">
				<div id="delete-action">
					<?php
						if ( current_user_can( "delete_post", $post->ID ) ) {
							?><a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID, '', true ); ?>"><?php _e( 'Delete Permanently', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></a><?php
						}
					?>
				</div>

				<input type="submit" class="button save_order_status_email save_action button-primary tips" name="publish" value="<?php _e( 'Save Email', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" data-tip="<?php _e( 'Save/update the order status email', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" />
			</li>

			<?php
				/**
				 * Fires at the end of the order status email actions meta box
				 *
				 * @since 1.0.0
				 * @param int $post_id The post id of the wc_order_email post
				 */
				do_action( 'wc_order_status_manager_order_status_email_actions_end', $post->ID );
			?>

		</ul>
		<?php
	}


	/**
	 * Process and save order status email meta
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function save_order_status_email_meta( $post_id, WP_Post $post ) {

		update_post_meta( $post_id, '_email_type',  $_POST['_email_type'] );

		// Remove any previously saved dispatch conditions
		delete_post_meta( $post_id, '_email_dispatch_condition' );

		// Add in new dispatch conditions
		if ( ! empty( $_POST['_email_dispatch_condition'] ) ) {

			foreach ( $_POST['_email_dispatch_condition'] as $condition ) {
				add_post_meta( $post_id, '_email_dispatch_condition', $condition['from'] . '_to_' . $condition['to'] );
			}
		}
	}


	/**
	 * Output custom column content
	 *
	 * @since 1.0.0
	 * @param string $column
	 * @param int $post_id
	 */
	public function custom_column_content( $column, $post_id ) {
		global $post;

		switch ( $column ) {

			case 'type':

				if ( $type = get_post_meta( $post_id, '_email_type', true ) ) {
					echo isset( $this->email_types[ $type ] ) ? $this->email_types[ $type ] : '';
				}

			break;

			case 'description':
				echo isset( $post->post_excerpt ) ? $post->post_excerpt : '';
			break;

			case 'status':

				$settings            = get_option( "woocommerce_wc_order_status_email_{$post_id}_settings" );
				$dispatch_conditions = get_post_meta( $post_id, '_email_dispatch_condition' );

				$url = admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_order_status_email_' . esc_attr( $post_id ) );

				if ( ! $dispatch_conditions ) {

					$status = __( 'Inactive', WC_Order_Status_Manager::TEXT_DOMAIN );
					$tip    = __( 'No dispatch rules set for this email.', WC_Order_Status_Manager::TEXT_DOMAIN );
					$url    = get_edit_post_link( $post_id );

				} else if ( isset( $settings['enabled'] ) && $settings['enabled'] === 'yes' ) {

					$status = __( 'Enabled', WC_Order_Status_Manager::TEXT_DOMAIN );
					$tip    = __( 'This email is enabled.', WC_Order_Status_Manager::TEXT_DOMAIN );

				} else {

					$status = __( 'Disabled', WC_Order_Status_Manager::TEXT_DOMAIN );
					$tip    = __( 'This email is disabled.', WC_Order_Status_Manager::TEXT_DOMAIN );
				}

				printf(
					'<a href="%s" class="tips badge %s" data-tip="%s">%s</a>',
					$url,
					sanitize_title( $status ),
					$tip,
					$status
				);
			break;
		}
	}


}
