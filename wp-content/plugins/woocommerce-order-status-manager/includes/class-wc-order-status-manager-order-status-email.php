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

if ( ! class_exists( 'WC_Order_Status_Manager_Order_Status_Email' ) ) :

/**
 * Order Status Manager Order Status Email
 *
 * A generic email class for custom order status emails.
 *
 * @since 1.0.0
 */
class WC_Order_Status_Manager_Order_Status_Email extends WC_Email {


	/** @var string email body text */
	private $body_text;

	/** @var array trigger/dispatch conditions */
	public $dispatch_conditions;

	/** @var string|void default body text used for settings */
	public $default_body_text;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param string $id
	 * @param array $args {
	 *     An array of arguments. Required.
	 *
	 *     @type string $title               Email Title.
	 *     @type string $description         Email Description. Optional.
	 *     @type string $type                Email Type - one of admin or customer. Defaults to admin.
	 *     @type array  $dispatch_conditions An array of email dispatch conditions. Optional.
	 * }
	 */
	public function __construct( $id, array $args ) {

		$this->id                  = $id;
		$this->post_id             = $args['post_id']; // The related post ID
		$this->title               = $args['title'];
		$this->description         = isset( $args['description'] ) ? $args['description'] : '';
		$this->type                = $args['type'];
		$this->dispatch_conditions = $args['dispatch_conditions'];

		// Configure email defaults based on type
		switch ( $this->type ) {

			case 'customer':
				$this->heading = __( 'Order has been updated', WC_Order_Status_Manager::TEXT_DOMAIN );
				$this->subject = __( 'Regarding your {site_title} order from {order_date}', WC_Order_Status_Manager::TEXT_DOMAIN );

				$this->default_body_text = __( 'Your order is now {order_status}. Order details are shown below for your reference:', WC_Order_Status_Manager::TEXT_DOMAIN );

			break;

			case 'admin':
				$this->heading = __( 'Order has been updated', WC_Order_Status_Manager::TEXT_DOMAIN );
				$this->subject = __( '[{site_title}] Customer order ({order_number}) updated', WC_Order_Status_Manager::TEXT_DOMAIN );

				$this->default_body_text = __( 'This order is now {order_status}. Order details are as follows:', WC_Order_Status_Manager::TEXT_DOMAIN );

				$this->recipient = $this->get_option( 'recipient' );

				if ( ! $this->recipient ) {
					$this->recipient = get_option( 'admin_email' );
				}

			break;
		}

		$this->body_text = $this->get_option( 'body_text' );

		$this->template_html  = $this->locate_email_template();
		$this->template_plain = $this->locate_email_template( 'plain' );

		// Cue this email to be sent on hooks
		if ( ! empty( $this->dispatch_conditions ) ) {
			add_action( 'wc_order_status_manager_order_status_change_notification', array( $this, 'maybe_trigger' ), 10, 3 );
		}

		// Hack: prevent File not found error when displaying templates after deleting
		// a specific template file.
		// We are using the `gettext` filter hook to achieve this as this is the
		// only hook we can use that fires after the template file is deleted, but
		// before the template file is loaded
		//
		// TODO: refactor when 2.2 support is dropped {@link https://github.com/woothemes/woocommerce/pull/6837}
		if ( ! empty( $_GET['delete_template'] ) && ( $template = esc_attr( basename( $_GET['delete_template'] ) ) ) ) {

			if ( ! empty( $this->$template ) ) {

				if ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $this->$template ) ) {
					add_filter( 'gettext', array( $this, 'reset_templates' ), 20, 3 );
				}
			}
		}

		// Call parent constuctor
		parent::__construct();
	}


	/**
	 * Initialise email form fields
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {

		$form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable this email notification', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'default'     => 'no',
			),
			'recipient' => array(
				'title'       => __( 'Recipient(s)', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', WC_Order_Status_Manager::TEXT_DOMAIN ), esc_attr( get_option('admin_email') ) ),
				'placeholder' => '',
				'default'     => '',
			),
			'subject' => array(
				'title'       => __( 'Subject', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', WC_Order_Status_Manager::TEXT_DOMAIN ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading' => array(
				'title'       => __( 'Email Heading', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', WC_Order_Status_Manager::TEXT_DOMAIN ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),
			'body_text' => array(
				'title'       => __( 'Email Body', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'type'        => 'textarea',
				'description' => __( 'Optional email body text. You can use the following placeholders: <code>{order_date}, {order_number}, {order_status}, {billing_first_name}, {billing_last_name}, {billing_company}, {blogname}, {site_title}</code>', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'placeholder' => '',
				'default'     => $this->default_body_text,
			),
			'email_type' => array(
				'title'       => __( 'Email type', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', WC_Order_Status_Manager::TEXT_DOMAIN ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'       => __( 'Plain text', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'html'        => __( 'HTML', WC_Order_Status_Manager::TEXT_DOMAIN ),
					'multipart'   => __( 'Multipart', WC_Order_Status_Manager::TEXT_DOMAIN ),
				),
			),
		);

		// Customer emails do not use the recipient field
		if ( 'customer' == $this->type ) {
			unset( $form_fields['recipient'] );
		}

		/**
		 * Filter email form fields
		 *
		 * @since 1.0.0
		 *
		 * @param array $form_fields Default form fields.
		 * @param string $id E-mail ID
		 * @param string $type E-mail type
		 */
		$this->form_fields = apply_filters( 'wc_order_status_manager_order_status_email_form_fields', $form_fields, $this->id, $this->type );
	}


	/**
	 * Trigger function
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object = wc_get_order( $order_id );

			if ( 'customer' == $this->type ) {
				$this->recipient = $this->object->billing_email;
			}

			// Supported variables in subject, heading and body text
			$this->find['order-date']            = '{order_date}';
			$this->find['order-number']          = '{order_number}';
			$this->find['order-status']          = '{order_status}';
			$this->find['billing-first-name']    = '{billing_first_name}';
			$this->find['billing-last-name']     = '{billing_last_name}';
			$this->find['billing-company']       = '{billing_company}';

			/**
			 * Filter the supported variables in subject, heading and body text
			 *
			 * @since 1.1.1
			 *
			 * @param array $find Associative array of placeholders.
			 * @param string $id E-mail ID
			 * @param string $type E-mail type
			 */
			$this->find = apply_filters( 'wc_order_status_manager_order_status_email_form_fields', $this->find, $this->id, $this->type );

			$this->replace['order-date']         = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number']       = $this->object->get_order_number();
			$this->replace['order-status']       = wc_get_order_status_name( $this->object->get_status() );
			$this->replace['billing-first-name'] = $this->object->billing_first_name;
			$this->replace['billing-last-name']  = $this->object->billing_last_name;
			$this->replace['billing-company']    = $this->object->billing_company;

			/**
			 * Filter the the srtings to replace in subject, heading and body text.
			 *
			 * @since 1.1.1
			 *
			 * @param array $replace Associative array of strings.
			 * @param string $id E-mail ID
			 * @param string $type E-mail type
			 */
			$this->replace = apply_filters( 'wc_order_status_manager_order_status_email_form_fields', $this->replace, $this->id, $this->type );
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


	/**
	 * Get body text
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_body_text() {

		/**
		 * Filter the email body text
		 *
		 * @since 1.0.0
		 * @param string $body_text The email body text
		 * @param WC_Order $order The order object
		 */
		return apply_filters( "wc_order_status_manager_order_status_email_body_text_{$this->id}", $this->format_string( $this->body_text ), $this->object );
	}


	/**
	 * Get HTML content for the email
	 *
	 * @since 1.0.0.
	 * @see WC_Email::get_content_help
	 * @return string Email HTML content
	 */
	public function get_content_html() {

		ob_start();

		wc_get_template( $this->template_html, array(
			'order'           => $this->object,
			'email_heading'   => $this->get_heading(),
			'email_body_text' => $this->get_body_text(),
			'sent_to_admin'   => 'admin' == $this->type,
			'plain_text'      => false,
		) );

		return ob_get_clean();
	}


	/**
	 * Get plain content for the email
	 *
	 * @since 1.0.0.
	 * @return string Email plain content
	 */
	public function get_content_plain() {

		ob_start();

		wc_get_template( $this->template_plain, array(
			'order'           => $this->object,
			'email_heading'   => $this->get_heading(),
			'email_body_text' => $this->get_body_text(),
			'sent_to_admin'   => 'admin' == $this->type,
			'plain_text'      => true,
		) );

		return ob_get_clean();
	}


	/**
	 * Locate the template file for this email
	 *
	 * Looks for templates in the following order:
	 * 1. emails/{$type}-order-status-email-{$slug}.php
	 * 2. emails/{$type}-order-status-email-{$id}.php
	 * 3. emails/{$type}-order-status-email.php
	 *
	 * Templates are looked for in current theme, then our plugin and then WC core.
	 *
	 * @since 1.0.0
	 * @param string $type Optional. Type of template to locate. One of `html` or `plain`. Defaults to `html`
	 * @return string Path to template file
	 */
	public function locate_email_template( $type = 'html' ) {

		$type_path = 'plain' == $type ? 'plain/' : '';

		$templates = array(
			"emails/{$type_path}{$this->type}-order-status-email-{$this->post_id}.php",
			"emails/{$type_path}{$this->type}-order-status-email.php",
		);

		if ( $email_slug = sanitize_title( $this->title ) ) {
			array_unshift( $templates, "emails/{$type_path}{$this->type}-order-status-email-{$email_slug}.php" );
		}

		$located_template = '';

		// Try to locate the template file, starting from most specific
		foreach ( $templates as $template_path ) {

			$located = wc_locate_template( $template_path );

			if ( $located && file_exists( $located ) ) {

				$located_template = $template_path;
				break;
			}
		}

		return $located_template;
	}


	/**
	 * Re-set current email templates
	 *
	 * Note: this fuction is called as a filter, but it is not
	 * supposed to filter the original value, only reset our
	 * templates.
	 *
	 * TODO: refactor when 2.2 support is dropped {@link https://github.com/woothemes/woocommerce/pull/6837}
	 *
	 * @since 1.0.0
	 * @param string $value Original value
	 * @return string Unmodified value
	 */
	public function reset_templates( $translation, $text, $domain ) {

		// We currently use the original text to determine if we are
		// in the right place to reset the templates
		if ( 'Template file deleted from theme.' == $text ) {

			$this->template_html  = $this->locate_email_template();
			$this->template_plain = $this->locate_email_template( 'plain' );

			// Unhook as soon as we're done
			remove_filter( 'gettext', array( $this, 'reset_templates' ) );
		}

		return $translation;
	}


	/**
	 * Check conditions and trigger email
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function maybe_trigger( $order_id, $old_status, $new_status ) {

		// Possible conditions that the current status changes creates
		$status_changes = array(
			$old_status . '_to_' . $new_status,
			$old_status . '_to_any',
			'any_to_' . $new_status,
		);

		// Try to find a match between current changes and the dispatch conditions
		foreach ( $this->dispatch_conditions as $condition ) {

			if ( in_array( $condition, $status_changes ) ) {

				// Only trigger email once, even if multiple conditions match
				$this->trigger( $order_id );
				break;
			}
		}
	}


}

endif;
