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
 * Order Status Manager Order Statuses Admin
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_Admin_Order_Statuses {


	/**
	 * Setup admin class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'manage_edit-wc_order_status_columns', array( $this, 'order_status_columns' ) );

		add_filter( 'post_row_actions', array( $this, 'order_status_actions' ), 10, 2 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'wc_order_status_manager_process_wc_order_status_meta', array( $this, 'save_order_status_meta' ), 10, 2 );

		add_action( 'manage_wc_order_status_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );

	}


	/**
	 * Customize order status columns
	 *
	 * @since 1.0.0
	 * @param array $columns
	 * @return array
	 */
	public function order_status_columns( $columns ) {

		$columns['slug']        = __( 'Slug', WC_Order_Status_Manager::TEXT_DOMAIN );
		$columns['description'] = __( 'Description', WC_Order_Status_Manager::TEXT_DOMAIN );
		$columns['type']        = __( 'Type', WC_Order_Status_Manager::TEXT_DOMAIN );

		$first_column = array( 'icon' => __( 'Icon', WC_Order_Status_Manager::TEXT_DOMAIN ) );

		return $first_column + $columns;
	}


	/**
	 * Customize order status row actions
	 *
	 * @since 1.0.0
	 * @param array $actions
	 * @param WP_Post $post
	 * @return array
	 */
	public function order_status_actions( $actions, WP_Post $post ) {

		$status = new WC_Order_Status_Manager_Order_Status( $post->ID );

		// remove delete for core statuses
		if ( $status->is_core_status() ) {
			unset( $actions['delete'] );
		}

		return $actions;
	}

	/**
	 * Add meta boxes to the order status edit page
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {

		// Order Status data meta box
		add_meta_box(
			'woocommerce-order-status-data',
			__( 'Order Status Data', WC_Order_Status_Manager::TEXT_DOMAIN ),
			array( $this, 'order_status_data_meta_box' ),
			'wc_order_status',
			'normal',
			'high'
		);

		// Order Status actions meta box
		add_meta_box(
			'woocommerce-order-status-actions',
			__( 'Order Status Actions', WC_Order_Status_Manager::TEXT_DOMAIN ),
			array( $this, 'order_status_actions_meta_box' ),
			'wc_order_status',
			'side',
			'high'
		);

		remove_meta_box( 'slugdiv', 'wc_order_status', 'normal' );
	}


	/**
	 * Display the order status data meta box
	 *
	 * @since 1.0.0
	 */
	public function order_status_data_meta_box() {
		global $post;

		$status = new WC_Order_Status_Manager_Order_Status( $post->ID );

		wp_nonce_field( 'wc_order_status_manager_save_data', 'wc_order_status_manager_meta_nonce' );
		?>

		<div id="order_status_options" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php

				// Status Name
				woocommerce_wp_text_input( array(
					'id'    => 'post_title',
					'label' => __( 'Name', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'value' => $post->post_title,
				) );

				// Disable slug editing for core statuses
				$custom_attributes = array();
				if ( $status->is_core_status() ) {
					$custom_attributes['disabled'] = 'disabled';
				}

				// Slug
				woocommerce_wp_text_input( array(
					'id'                => 'post_name',
					'label'             => __( 'Slug', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'value'             => $post->post_name,
					'custom_attributes' => $custom_attributes,
					'desc_tip'          => true,
					'description'       => __( 'Optional. If left blank, the slug will be automatically generated from the name. Maximum: 17 characters.', WC_Order_Status_Manager::TEXT_DOMAIN ),
				) );

				// Description
				woocommerce_wp_textarea_input( array(
					'id'          => 'post_excerpt',
					'label'       => __( 'Description', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'desc_tip'    => true,
					'description' => __( 'Optional status description. If set, this will be shown to customers while viewing an order.', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'value'       => htmlspecialchars_decode( $post->post_excerpt, ENT_QUOTES ),
				) );

				?>
			</div><!-- // .options_group -->

			<div class="options_group">
				<?php

				// Color
				woocommerce_wp_text_input( array(
					'id'          => '_color',
					'label'       => __( 'Color', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'type'        => 'text',
					'class'       => 'colorpick',
					'default'     => '#000000',
					'desc_tip'    => true,
					'description' => __( 'Color displayed behind the order status image or name', WC_Order_Status_Manager::TEXT_DOMAIN ),
				) );

				// Status Icon
				$icon = $status->get_icon();
				$icon_attachment_src = '';

				if ( is_numeric( $icon ) ) {
					$icon_attachment_src = wp_get_attachment_image_src( $icon, 'wc_order_status_icon' );
				}
				?>
				<p class="form-field _icon_field">
					<label for="_icon"><?php _e( 'Icon', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></label>

					<input type="text" id="_icon" name="_icon" class="short" value="<?php echo esc_attr( $status->get_icon() ); ?>" data-icon-image="<?php echo esc_attr( $icon_attachment_src ? $icon_attachment_src[0] : '' ); ?>">

					<a href="#_icon" class="button upload-icon upload-icon-image" data-uploader-button-text="<?php _e( 'Set as status icon', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>"><?php _e( "Select File", WC_Order_Status_Manager::TEXT_DOMAIN ); ?></a>
					<img class="help_tip" data-tip="<?php _e( "Optional status icon. If not supplied, then Name will be displayed to represent the status", WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
				</p>

				<?php
				// Status Action Icon
				$action_icon = $status->get_action_icon();
				$action_icon_attachment_src = '';

				if ( is_numeric( $action_icon ) ) {
					$action_icon_attachment_src = wp_get_attachment_image_src( $action_icon, 'wc_order_status_icon' );
				}
				?>
				<p class="form-field _action_icon_field">
					<label for="_action_icon"><?php _e( 'Action Icon', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></label>

					<input type="text" id="_action_icon" name="_action_icon" class="short" value="<?php echo esc_attr( $status->get_action_icon() ); ?>" data-icon-image="<?php echo esc_attr( $action_icon_attachment_src ? $action_icon_attachment_src[0] : '' ); ?>">

					<a href="#_action_icon" class="button upload-icon upload-icon-image" data-uploader-button-text="<?php _e( 'Set as status icon', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>"><?php _e( "Select File", WC_Order_Status_Manager::TEXT_DOMAIN ); ?></a>
					<img class="help_tip" data-tip="<?php _e( "Optional action icon displayed in the action buttons for the next statuses.", WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
				</p>
			</div><!-- // .options_group -->

			<div class="options_group">
				<?php

				// Next statuses
				$next_status_options = array();
				$selected = $status->get_next_statuses();
				$selected = $selected ? $selected : array();

				foreach ( wc_get_order_statuses() as $slug => $name ) {

					if ( $status->get_slug( true ) !== $slug ) {
						$next_status_options[ str_replace( 'wc-', '', $slug ) ] = $name;
					}
				}

				?>
				<p class="form-field _next_statuses_field">
					<label for="_next_statuses"><?php _e( 'Next Statuses', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></label>
					<select id="_next_statuses" name="_next_statuses[]" class="select short" multiple>
						<?php foreach ( $next_status_options as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( in_array( $slug, $selected ), 1 ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<img class="help_tip" data-tip="<?php _e( 'Zero or more statuses that would be considered next during normal order status flow. Action buttons will be available to move an order with this custom status to these next statuses.', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
				</p>
				<?php

				// Bulk action
				woocommerce_wp_checkbox( array(
					'id'          => '_bulk_action',
					'label'       => __( 'Bulk action', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'description' => __( 'Check this to add this order status to the Orders list table Bulk Actions list.', WC_Order_Status_Manager::TEXT_DOMAIN ),
				) );

				// Include in reports
				woocommerce_wp_checkbox( array(
					'id'          => '_include_in_reports',
					'label'       => __( 'Include in reports', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'description' => __( 'Check this to include orders with this order status in the order reports.', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'value'       => 'auto-draft' === $post->post_status ? 'yes' : get_post_meta( $post->ID, '_include_in_reports', true ),
				) );

				?>
			</div><!-- // .options_group -->
		</div><!-- // .woocommerce_options_panel -->
		<?php

	}


	/**
	 * Display the order status actions meta box
	 *
	 * @since 1.0.0
	 */
	public function order_status_actions_meta_box() {
		global $post;

		$status = new WC_Order_Status_Manager_Order_Status( $post->ID );

		?>
		<ul class="order_status_actions submitbox">

			<?php
				/**
				 * Fires at the start of the order status actions meta box
				 *
				 * @since 1.0.0
				 * @param int $post_id The post id of the wc_order_status post
				 */
				do_action( 'wc_order_status_manager_order_status_actions_start', $post->ID );
			?>

			<li class="wide">
				<div id="delete-action">
					<?php
						if ( ! $status->is_core_status() && current_user_can( "delete_post", $post->ID ) ) {
							?><a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID, '', true ) ); ?>"><?php _e( 'Delete', WC_Order_Status_Manager::TEXT_DOMAIN ); ?></a><?php
						}
					?>
				</div>

				<input type="submit" class="button save_order_status save_action button-primary tips" name="publish" value="<?php _e( 'Save Order Status', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" data-tip="<?php _e( 'Save/update the order status', WC_Order_Status_Manager::TEXT_DOMAIN ); ?>" />
			</li>

			<?php
				/**
				* Fires at the end of the order status actions meta box
				*
				* @since 1.0.0
				* @param int $post_id The post id of the wc_order_status post
				*/
				do_action( 'wc_order_status_manager_order_status_actions_end', $post->ID );
			?>

		</ul>
		<?php
	}


	/**
	 * Process and save order status meta
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 */
	public function save_order_status_meta( $post_id ) {

		update_post_meta( $post_id, '_color',              $_POST['_color'] ? $_POST['_color'] : '#000000' ); // provide a default color
		update_post_meta( $post_id, '_next_statuses',      isset( $_POST['_next_statuses'] ) ? $_POST['_next_statuses'] : '' );
		update_post_meta( $post_id, '_bulk_action',        isset( $_POST['_bulk_action'] ) && $_POST['_bulk_action'] ? 'yes' : 'no' );
		update_post_meta( $post_id, '_include_in_reports', isset( $_POST['_include_in_reports'] ) && $_POST['_include_in_reports'] ? 'yes' : 'no' );
		update_post_meta( $post_id, '_icon',               $_POST['_icon'] );
		update_post_meta( $post_id, '_action_icon',        $_POST['_action_icon'] );
	}


	/**
	 * Output custom column content
	 *
	 * @since 1.0.0
	 * @param string $column
	 * @param int $post_id
	 */
	public function custom_column_content( $column, $post_id ) {

		$status = new WC_Order_Status_Manager_Order_Status( $post_id );

		switch ( $column ) {

			case 'icon';
				$color = $status->get_color();
				$icon  = $status->get_icon();
				$style = '';

				if ( $color ) {

					if ( $icon ) {
						$style = 'color: ' . $color . ';';
					} else {
						$style = 'background-color: ' . $color . '; color: ' . wc_order_status_manager()->icons->get_contrast_text_color( $color ) . ';';
					}
				}

				if ( is_numeric( $icon ) ) {

					$icon_src = wp_get_attachment_image_src( $icon, 'wc_order_status_icon' );

					if ( $icon_src ) {
						$style .= 'background-image: url( ' . $icon_src[0] . ');';
					}
				}

				printf( '<mark class="%s %s tips" style="%s" data-tip="%s">%s</mark>', sanitize_title( $status->get_slug() ), ( $icon ? 'has-icon ' . $icon : '' ), $style, esc_attr( $status->get_name() ), esc_html( $status->get_name() ) );

			break;

			case 'slug':
				echo esc_html( $status->get_slug() );
			break;

			case 'description':
				echo esc_html( $status->get_description() );
			break;

			case 'type':

				printf(
					'<span class="badge %s">%s</span>',
					sanitize_title( $status->get_type() ),
					esc_html( $status->get_type() )
				);
			break;
		}
	}


}
