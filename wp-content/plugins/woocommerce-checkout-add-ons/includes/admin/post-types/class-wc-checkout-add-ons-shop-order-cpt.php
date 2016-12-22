<?php
/**
 * WooCommerce Checkout Add-Ons
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Checkout Add-Ons to newer
 * versions in the future. If you wish to customize WooCommerce Checkout Add-Ons for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-checkout-add-ons/ for more information.
 *
 * @package     WC-Checkout-Add-Ons/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Order CPT class
 *
 * Handles modifications to the shop order CPT on both View Orders list table and Edit Order screen
 *
 * @since 1.0
 */
class WC_Checkout_Add_Ons_Shop_Order_CPT {


	/**
	 * Separator used between add-on name and selected/entered value
	 * in order review area
	 */
	private $label_separator = ' - ';

	/** Formatted add-on labels container, used for displaying add-on values on the admin order screen **/
	private $formatted_names = array();

	/** File labels container, used for unescaping file labels **/
	private $file_labels = array();


	/**
	 * Add actions/filters for View Orders/Edit Order screen
	 *
	 * @since 1.0
	 * @return \WC_Checkout_Add_Ons_Shop_Order_CPT
 	 */
	public function __construct() {

		// add listable checkout add-on column titles to the orders list table
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'render_column_titles' ), 15 );

		// add listable checkout add-on column content to the orders list table
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column_content' ), 5 );

		// add sortable checkout add-ons
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'add_sortable_columns' ) );

		// process sorting
		add_filter( 'posts_orderby', array( $this, 'add_sortable_orderby' ), 10, 2 );

		// make add-ons filterable
		add_filter( 'posts_join',  array( $this, 'add_order_itemmeta_join' ) );
		add_filter( 'posts_where', array( $this, 'add_filterable_where' ) );

		// handle filtering
		add_action( 'restrict_manage_posts', array( $this, 'restrict_orders' ), 15 );

		// make add-ons searchable
		add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'add_search_fields' ) );

		// display add-on values in order edit screen
		add_filter( 'woocommerce_order_get_items', array( $this, 'build_formatted_names' ), 10, 2 );
		add_filter( 'esc_html',                    array( $this, 'append_add_on_fee_meta' ), 10, 2 );
		add_filter( 'esc_html',                    array( $this, 'unescape_file_link_html' ), 20, 2 );
	}


	/** Listable Columns ******************************************************/


	/**
	 * Add any listable columns
	 *
	 * @since 1.0
	 * @param array $columns associative array of column id to display name
	 * @return array of column id to display name
	 */
	public function render_column_titles( $columns ) {

		// get all columns up to and excluding the 'order_actions' column
		$new_columns = array();

		foreach ( $columns as $name => $value ) {

			if ( 'order_actions' == $name ) {
				prev( $columns );
				break;
			}

			$new_columns[ $name ] = $value;
		}

		// inject our columns
		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {

			if ( $add_on->is_listable() ) {
				$new_columns[ $add_on->get_key() ] = $add_on->name;
			}
		}

		// add the 'order_actions' column, and any others
		foreach ( $columns as $name => $value ) {
			$new_columns[ $name ] = $value;
		}

		return $new_columns;
	}


	/**
	 * Display the values for the listable columns
	 *
	 * @since 1.0
	 * @param string $column the column name
	 */
	public function render_column_content( $column ) {
		global $post, $wpdb;

		foreach ( wc_checkout_add_ons()->get_add_ons( $post->ID ) as $add_on ) {

			if ( $column == $add_on->get_key() ) {

				$query = $wpdb->prepare( "
					SELECT
						woi.order_item_id
					FROM {$wpdb->prefix}woocommerce_order_itemmeta woim
					RIGHT JOIN {$wpdb->prefix}woocommerce_order_items woi ON woim.order_item_id = woi.order_item_id
					WHERE 1=1
						AND woi.order_id = %d
						AND woim.meta_key = '_wc_checkout_add_on_id'
						AND woim.meta_value = %d
					",
					$post->ID,
					$add_on->id
				);

				$item_id = $wpdb->get_var( $query );

				if ( $item_id ) {
					switch ( $add_on->type ) {

						case 'checkbox':
							echo wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_value', true ) ? '&#x2713;' : '';
						break;

						case 'file':
							$file_ids    = explode( ',', wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_value', true ) );
							$files_count = count( $file_ids );
							$file_labels = array();

							echo '<a href="#" class="wc-checkout-add-ons-files-toggle">' . sprintf( _n( '%d file', '%d files', $files_count, WC_Checkout_Add_Ons::TEXT_DOMAIN ), $files_count ) . '</a>';

							echo '<ul class="wc-checkout-add-ons-files">';
							foreach ( $file_ids as $key => $file_id ) {
								if ( $url = get_edit_post_link( $file_id ) ) {
									echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $file_id ) ) . '</a></li>';
								} else {
									echo '<li>' . __( '(File has been removed)', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . '</li>';
								}
							}
							echo '</ul>';

						break;

						case 'text':
						case 'textarea':
							$label = wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_label', true );
							echo $add_on->truncate_label( $label );
						break;

						default:
							$label = wc_get_order_item_meta( $item_id, '_wc_checkout_add_on_label', true );
							echo is_array( $label ) ? implode( ', ', $label ) : $label;
					}
				}
				break;
			}
		}
	}


	/** Sortable Columns ******************************************************/


	/**
	 * Make order columns sortable
	 *
	 * @since 1.0
	 * @param array $columns associative array of column name to id
	 * @return array of column name to id
	 */
	public function add_sortable_columns( $columns ) {

		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {

			if ( $add_on->is_sortable() ) {
				$columns[ $add_on->get_key() ] = $add_on->get_key();
			}
		}

		return $columns;
	}


	/**
	 * Modify SQL ORDEBY clause for sorting the orders by any sortable checkout add-ons
	 *
	 * @since 1.0
	 * @param string $orderby ORDERBY part of the sql query
	 * @return string $orderby modified ORDERBY part of sql query
	 */
	public function add_sortable_orderby( $orderby, $wp_query ) {
		global $typenow, $wpdb;

		if ( 'shop_order' != $typenow ) {
			return $orderby;
		}

		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {
			// if the add-on is filterable and selected by the user, and the join has not bee altered yet
			if ( $add_on->is_sortable() && isset( $wp_query->query['orderby'] ) && $wp_query->query['orderby'] == $add_on->get_key() ) {

				// Sort by subquery results
				$orderby = $wpdb->prepare("(
					SELECT
						woim_value.meta_value
					FROM {$wpdb->prefix}woocommerce_order_items woi
					RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim_id ON woi.order_item_id = woim_id.order_item_id
					RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim_value ON woi.order_item_id = woim_value.order_item_id
					WHERE 1=1
						AND woi.order_id = {$wpdb->prefix}posts.ID
						AND woim_id.meta_key = '_wc_checkout_add_on_id'
						AND woim_id.meta_value = %d
						AND woim_value.meta_key = '_wc_checkout_add_on_value'
					)",
					$add_on->id
				);

				// Sorting order
				$orderby .= 'asc' == $wp_query->query['order'] ? ' ASC' : ' DESC';

				break;
			}

		}

		return $orderby;
	}


	/** Filterable Columns ******************************************************/


	/**
	 * Render dropdowns for any filterable checkout add-ons
	 *
	 * @since 1.0
	 */
	public function restrict_orders() {
		global $typenow;

		if ( 'shop_order' != $typenow ) {
			return;
		}

		$javascript = '';

		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {

			// if the add-on is filterable
			if ( $add_on->is_filterable() ) {

				if ( $add_on->has_options() ) {

					// filterable multi item add-on field (select, multiselect, radio, checkbox), provide a dropdown
					?>
					<select name="<?php echo esc_attr( $add_on->get_key() ); ?>" id="<?php echo esc_attr( $add_on->get_key() ); ?>" class="wc-enhanced-select" data-placeholder="<?php echo __( "Show all ", WC_Checkout_Add_Ons::TEXT_DOMAIN ). $add_on->name; ?>" data-allow_clear="true" style="min-width:200px;">
						<option value=""></option>
						<?php
						foreach ( $add_on->get_options() as $option ) :
							if ( '' === $option['value'] && '' === $option['label'] ) continue;
							echo '<option value="' . $option['value'] . '" ' . ( isset( $_GET[ $add_on->get_key() ] ) ? selected( $option['value'], $_GET[ $add_on->get_key() ] ) : '' ) . '>' . __( $option['label'], WC_Checkout_Add_Ons::TEXT_DOMAIN ) . '</option>';
						endforeach;
						?>
					</select>
					<?php
					$javascript .= SV_WC_Plugin_Compatibility::is_wc_version_lt_2_3() ? "if ( ! $().select2 && $().chosen ) { $('select#" . $add_on->get_key() . "').chosen( {  allow_single_deselect: true  } ); }" : "";

				} elseif ( $add_on->type == 'text' ) {

					if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ) {
						?>
						<input type="hidden" class="sv-wc-enhanced-search" name="<?php echo esc_attr( $add_on->get_key() ); ?>" style="min-width:200px;"
							data-placeholder="<?php echo __( "Show all ", WC_Checkout_Add_Ons::TEXT_DOMAIN ) . $add_on->label; ?>"
							data-selected="<?php echo ( empty( $_GET[ $add_on->get_key() ] ) ? '' : $_GET[ $add_on->get_key() ] ); ?>"
							value="<?php echo ( empty( $_GET[ $add_on->get_key() ] ) ? '' : esc_attr( $_GET[ $add_on->get_key() ] ) ); ?>"
							data-allow_clear="true"
							data-action="wc_checkout_add_ons_json_search_field"
							data-nonce="<?php echo wp_create_nonce( 'search-field' ); ?>"
							data-request_data = "<?php echo esc_attr( json_encode( array( 'add_on_id' => $add_on->id, 'default' => addslashes( __( 'Show all ', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . $add_on->name ) ) ) ) ?>"
							/>
						<?php

						SV_WC_Helper::render_select2_ajax();

					} else {

						// search box dropdown
						?>
						<select id="<?php echo esc_attr( $add_on->get_key() ); ?>" name="<?php echo esc_attr( $add_on->get_key() ); ?>" data-placeholder="<?php echo __( "Show all ", WC_Checkout_Add_Ons::TEXT_DOMAIN ) . $add_on->name; ?>" data-allow_clear="true" style="min-width:200px;">
							<option value=""></option>
							<?php
							if ( ! empty( $_GET[ $add_on->get_key() ] ) ) {
								echo '<option value="' . esc_attr( $_GET[ $add_on->get_key() ] ) . '" ';
								selected( 1, 1 );
								echo '>' . $_GET[ $add_on->get_key() ] . '</option>';
							}
							?>
						</select>
						<?php

						$javascript .= "
							$( 'select#" . $add_on->get_key() . "' ).ajaxChosen( {
								method:         'GET',
								url:            '" . admin_url( 'admin-ajax.php' ) . "',
								dataType:       'json',
								afterTypeDelay: '100',
								minTermLength:  1,
								data: {
									action:     'wc_checkout_add_ons_json_search_field',
									security:   '" . wp_create_nonce( "search-field" ) . "',
									request_data: { 'add_on_id' : '" . $add_on->id . "', 'default' : '" . addslashes( __( 'Show all ', WC_Checkout_Add_Ons::TEXT_DOMAIN ) . $add_on->name ) . "' },
								}
							}, function ( data ) {

								var terms = {};

								$.each( data, function ( i, val ) {
									terms[ i ] = val;
								} );

								return terms;
							}, {  allow_single_deselect: true } );";

					}

				} elseif ( $add_on->type == 'checkbox' || $add_on->type == 'file' ) {

					$checked = isset( $_GET[ $add_on->get_key() ] ) && $_GET[ $add_on->get_key() ];
					?>
					<label class="wc-checkout-add-on-checkbox-filter">
						<input type="checkbox" id="<?php echo esc_attr( $add_on->get_key() ); ?>" name="<?php echo esc_attr( $add_on->get_key() ); ?>" value="1" <?php checked( $checked ); ?>>
						<?php echo $add_on->name; ?>
					</label>
					<?php

				}

			}
		}

		// filterable dropdown javascript
		wc_enqueue_js( $javascript );
	}


	/**
	 * Modify SQL JOIN for filtering the orders by any filterable checkout add-ons
	 *
	 * @since 1.0
	 * @param string $join JOIN part of the sql query
	 * @return string $join modified JOIN part of sql query
	 */
	public function add_order_itemmeta_join( $join ) {
		global $typenow, $wpdb;

		if ( 'shop_order' != $typenow ) {
			return $join;
		}

		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {

			$filtering = $add_on->is_filterable() && isset( $_GET[ $add_on->get_key() ] ) && $_GET[ $add_on->get_key() ];

			// if the join has not been altered yet, and the add-on is filterable
			if ( $filtering ) {

				$join .=  "
					LEFT JOIN {$wpdb->prefix}woocommerce_order_items woi ON {$wpdb->posts}.ID = woi.order_id
					LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim_id ON woi.order_item_id = woim_id.order_item_id
					JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim_value ON woi.order_item_id = woim_value.order_item_id";

				// Break the foreach loop - we only need to alter the join clause once
				break;
			}

		}

		return $join;
	}


	/**
	 * Modify SQL WHERE for filtering the orders by any filterable checkout add-ons
	 *
	 * @since 1.0
	 * @param string $where WHERE part of the sql query
	 * @return string $where modified WHERE part of sql query
	 */
	public function add_filterable_where( $where ) {
		global $typenow, $wpdb;

		if ( 'shop_order' != $typenow ) {
			return $where;
		}

		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {

			// if the add-on is filterable and selected by the user, and the join has not bee altered yet
			if ( $add_on->is_filterable() && isset( $_GET[ $add_on->get_key() ] ) && $_GET[ $add_on->get_key() ] ) {

				$value = $_GET[ $add_on->get_key() ];

				// Main WHERE query part
				$where .= $wpdb->prepare( " AND woim_id.meta_key='_wc_checkout_add_on_id' AND woim_id.meta_value=%d AND woim_value.meta_key='_wc_checkout_add_on_value'", $add_on->id );

				// Add-on type specific comparison logic
				switch ( $add_on->type ) {

					case 'file':
						$where .= " AND woim_value.meta_value IS NOT NULL";
					break;

					case 'multiselect':
					case 'multicheckbox':
						$like = '%' . $wpdb->esc_like( $value ) . '%';
						$where .= $wpdb->prepare( " AND woim_value.meta_value LIKE %s ", $like );
					break;

					default:
						$where .= $wpdb->prepare( " AND woim_value.meta_value='%s' ", $value );
				}
			}
		}

		return $where;
	}


	/** Searchable ******************************************************/


	/**
	 * Add our checkout add-ons to the set of search fields so that
	 * the admin search functionality is maintained
	 *
	 * @since 1.0
	 * @param array $search_fields array of post meta fields to search by
	 * @return array of post meta fields to search by
	 */
	public function add_search_fields( $search_fields ) {

		foreach ( wc_checkout_add_ons()->get_add_ons() as $add_on ) {

			array_push( $search_fields, $add_on->get_key() );
		}

		return $search_fields;
	}


	/**
	 * Build the formatted_names array for use in displaying on the admin order screen
	 *
	 * @since 1.0
	 * @param array $items
	 * @param object $order
	 * @return array $items
	 */
	public function build_formatted_names( $items, $order ) {

		// Bail out if WC 2.2+
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_2() ) {
			return $items;
		}

		// Bail out if not in admin
		if ( ! is_admin() ) {
			return $items;
		}

		// bail if not on view order screen
		if ( ! isset( $GLOBALS['current_screen'] ) || 'shop_order' != $GLOBALS['current_screen']->id ) {
			return $items;
		}

		// Check if any of the fees are checkout add-ons
		foreach ( $items as $key => $item ) {
			if ( 'fee' == $item['type'] && $item['wc_checkout_add_on_id'] ) {

				$add_on = wc_checkout_add_ons()->get_add_on( $item['wc_checkout_add_on_id'] );

				if ( 'file' == $add_on->type ) {

					$label = $add_on->normalize_value( $item['wc_checkout_add_on_value'], false );
					$this->file_labels[] = $label;
				} else {

					$label = maybe_unserialize( $item['wc_checkout_add_on_label'] );
				}

				if ( $label ) {
					$this->formatted_names[ $items[ $key ]['name'] ] = $items[ $key ]['name'] . $this->label_separator . ( is_array( $label ) ? implode( ', ', $label ) : $label );

					if ( 'file' != $add_on->type ) {
						$this->formatted_names[ $items[ $key ]['name'] ]  = esc_html( $this->formatted_names[ $items[ $key ]['name'] ] );
					}
				}
			}
		}

		return $items;
	}


	/**
	 * Add add-on meta to order row label for display purposes in
	 * order edit screen.
	 *
	 * @since 1.0
	 * @param string $safe_text
	 * @param string $text
	 * @return string Escaped or unescaped text
	 */
	public function append_add_on_fee_meta( $safe_text, $text ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_lt_2_2() && ! empty( $this->formatted_names ) ) {
			foreach ( $this->formatted_names as $name => $formatted_name ) {
				if ( $text == $name ) {

					$safe_text = $formatted_name;
					unset( $this->formatted_names[ $name ] );
				}
			}
		}

		return $safe_text;
	}


	/**
	 * Unescape file link HTML
	 *
	 * Because all order fee item meta gets HTML escaped, the link
	 * will not display correctly. We unescape the HTML here so that the links
	 * to uploaded files work
	 *
	 * @since 1.0
	 * @param string $safe_text
	 * @param string $text
	 * @return string Escaped or unescaped text
	 */
	public function unescape_file_link_html( $safe_text, $text ) {

		if ( ! empty( $this->file_labels ) ) {
			foreach ( $this->file_labels as $key => $label ) {
				if ( false === strpos( $text, $label ) ) {

					$safe_text = $text;
					unset( $this->file_labels[ $key ] );
				}
			}
		}

		return $safe_text;
	}


} // end \WC_Checkout_Add_Ons_Shop_Order_CPT class
