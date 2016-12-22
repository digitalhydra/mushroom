<?php

/**
 * API Class
 *
 * Handles the products
 * 
 * @class 	  WC_Pos_API
 * @package   WooCommerce POS
 */

class WC_Pos_API {

	public function __construct() {

		// try and increase server timeout
		$this->increase_timeout();

		// remove wc api authentication
	    // - relies on ->api and ->authentication being publicly accessible
	    if( isset( WC()->api ) && isset( WC()->api->authentication ) ){
	      remove_filter( 'woocommerce_api_check_authentication', array( WC()->api->authentication, 'authenticate' ), 0 );
	    }

	    // support for X-HTTP-Method-Override for WC < 2.4
	    if( version_compare( WC()->version, '2.4', '<' ) && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ){
	      $_GET['_method'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
	    }

		add_filter( 'woocommerce_api_check_authentication', array( $this, 'wc_api_authentication' ), 20, 1 );

		// and we're going to filter on the way out
		add_filter( 'woocommerce_api_product_response', array( $this, 'filter_product_response' ), 99, 3 );
		add_action( 'woocommerce_api_coupon_response', array($this, 'api_coupon_response'), 99, 4 );
		add_filter( 'woocommerce_api_order_response', array( $this, 'filter_order_response' ), 99, 4 );
		add_filter( 'woocommerce_api_query_args', array( $this, 'filter_api_query_args' ), 99, 2 );


		$params = array_merge( $_GET, $_POST );
		if(  isset( $params['role'] ) && $params['role'] == 'all' ){
			add_action( 'pre_get_users', array( $this, 'pre_get_users' ), 99, 1 );			
		}

	}

	/**
     * Bypass authenication for WC REST API
     * @return WP_User object
     */
    public function wc_api_authentication( $user ) {


        //if( $this->is_pos_referer() === true || is_pos() ){

            global $current_user;
            $user = $current_user;

            if( ! user_can( $user->ID, 'view_register' ) )
              $user = new WP_Error(
                'woocommerce_pos_authentication_error',
                __( 'User not authorized to access WooCommerce POS', 'wc_point_of_sale' ),
                array( 'status' => 401 )
              );
            
        //}

        return $user;

    }

	/**
	 * WC REST API can timeout on some servers
	 * This is an attempt t o increase the timeout limit
	 * TODO: is there a better way?
	 */
	public function increase_timeout() { 
		$timeout = 6000;
		if( !ini_get( 'safe_mode' ) )
			@set_time_limit( $timeout );

		@ini_set( 'memory_limit', WP_MAX_MEMORY_LIMIT );
		@ini_set( 'max_execution_time', (int)$timeout );
	}

	public function pre_get_users($query)
	{
		$query->query_vars['role'] = '';
		return $query;
	}


	/**
	 * Filter product response from WC REST API for easier handling by backbone.js
	 * - unset unnecessary data
	 * - flatten some nested arrays
	 * @param  array $product_data
	 * @return array modified data array $product_data
	 */
	public function filter_product_response( $product_data, $product, $fields ) {
		
		// flatten variable data
		$product_data['categories_ids'] = wp_get_post_terms( $product->id, 'product_cat', array( "fields" => "ids" ) );
		
		if( !empty($product_data['attributes'])) {

			foreach ($product_data['attributes'] as $attr_key => $attribute) {
				$slug =  str_replace( 'attribute_', '', sanitize_title( $attribute['slug'] ) );
				
				$is_taxonomy    = false;
				$taxonomy       = $this->get_attribute_taxonomy_by_slug( $attribute['slug'] );
				if($taxonomy){
					$is_taxonomy = true;
				}

				$product_data['attributes'][$attr_key]['slug'] = $slug;
				$product_data['attributes'][$attr_key]['is_taxonomy'] = $is_taxonomy;

				$options = array();
				foreach ($product_data['attributes'][$attr_key]['options'] as $opt) {
					
					if ( $is_taxonomy === true ) {
						// Don't use wc_clean as it destroys sanitized characters
						$a = get_term_by( 'name', $opt, 'pa_' . $slug );
						if( $a ){
							$value = $a->slug;
						}else{
							$value = sanitize_title( stripslashes( $opt ) );							
						}
					} else {
						$value = wc_clean( stripslashes( $opt ) );
					}

					$options[] = array('slug' => $value, 'name' => $opt);

				}
				$product_data['attributes'][$attr_key]['options'] = $options;

			}

		}
		$parent_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_data['id'] ), 'shop_thumbnail' );
		$product_data['thumbnail_src'] = $parent_image ? current($parent_image) : wc_placeholder_img_src();

		if( isset($product_data['variations']) && !empty($product_data['variations'])){
			foreach ($product_data['variations'] as $key => $variation) {
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $variation['id'] ), 'shop_thumbnail' );
				$product_data['variations'][$key]['thumbnail_src'] = $image ? current($image) : $product_data['thumbnail_src'];
			}
		}


		/*foreach($removeKeys as $key) {
			unset($product_data[$key]);
		}*/
		return $product_data;
	}

	/**
	 * Get attribute taxonomy by slug.
	 */
	private function get_attribute_taxonomy_by_slug( $slug ) {
		$taxonomy = null;
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		foreach ( $attribute_taxonomies as $key => $tax ) {
			if ( $slug == $tax->attribute_name ) {
				$taxonomy = 'pa_' . $tax->attribute_name;

				break;
			}
		}

		return $taxonomy;
	}


	public function filter_order_response($order_data, $the_order, $fields, $api)
	{
		$post = $the_order->post;

		$order_data['order_status'] = sprintf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ) );

		$formatted_address = '';
		if ( $f_address = $the_order->get_formatted_shipping_address() ) {
			$formatted_address = '<a target="_blank" href="' . esc_url( $the_order->get_shipping_address_map_url() ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $f_address ) ) .'</a>';
		} else {
			$formatted_address = '<span>&ndash;</span>';
		}

		if ( $the_order->get_shipping_method() ) {
			$formatted_address .= '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
		}

		$order_data['formatted_shipping_address'] = $formatted_address;

		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
		} else {
			$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );
			$h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
		}

		$order_data['order_date'] = '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $post ) ) . '</abbr>';

		if ( $the_order->customer_message ) {
			$order_data['customer_message'] =  '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $the_order->customer_message ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
		} else {
			$order_data['customer_message'] =  '<span class="na">&ndash;</span>';
		}

		if ( $post->comment_count ) {

			// check the status of the post
			$status = ( 'trash' !== $post->post_status ) ? '' : 'post-trashed';

			$latest_notes = get_comments( array(
				'post_id'   => $post->ID,
				'number'    => 1,
				'status'    => $status
			) );

			$latest_note = current( $latest_notes );

			if( $latest_note ){

				if ( $post->comment_count == 1 ) {
					$order_data['order_notes'] = '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				} elseif ( isset( $latest_note->comment_content ) ) {
					$order_data['order_notes'] = '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content . '<br/><small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), 'woocommerce' ), $post->comment_count - 1 ) . '</small>' ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				} else {
					$order_data['order_notes'] = '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $post->comment_count, 'woocommerce' ), $post->comment_count ) ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				}

			}else{
				$order_data['order_notes'] = '<span class="na">&ndash;</span>';	
			}

		} else {
			$order_data['order_notes'] = '<span class="na">&ndash;</span>';
		}

		$order_data['order_total'] = $the_order->get_formatted_order_total();

		if ( $the_order->payment_method_title ) {
			$order_data['order_total'] .= '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->payment_method_title ) . '</small>';
		}
		if( sizeof($order_data['line_items']) > 0 ){
			foreach ($order_data['line_items'] as $key => $item) {
				$parents = get_post_ancestors( $item['product_id'] );
		        if($parents && !empty($parents) ){
		        	$order_data['line_items'][$key]['variation_id'] = $item['product_id'];
		        	$order_data['line_items'][$key]['product_id']   = $parents[0];
		        }
			}
		}
		
		$order_data['print_url'] = wp_nonce_url( admin_url( 'admin.php?print_pos_receipt=true&order_id=' . $the_order->id ), 'print_pos_receipt' );
		
		return $order_data;	
	}


	public function filter_api_query_args( $args, $request_args )
	{
		if ( ! empty( $request_args['meta_key'] ) ) {
			$args['meta_key'] = $request_args['meta_key'];
			unset( $request_args['meta_key'] );
		}
		if ( ! empty( $request_args['meta_value'] ) ) {
			$args['meta_value'] = $request_args['meta_value'];
			unset( $request_args['meta_value'] );
		}
		if ( ! empty( $request_args['meta_compare'] ) ) {
			$args['meta_compare'] = $request_args['meta_compare'];
			unset( $request_args['meta_compare'] );
		}

		if( !empty($args['s'])){
			global $wpdb;
			$search_fields = array_map( 'wc_clean', apply_filters( 'woocommerce_shop_order_search_fields', array(
				'_order_key',
				'_billing_company',
				'_billing_address_1',
				'_billing_address_2',
				'_billing_city',
				'_billing_postcode',
				'_billing_country',
				'_billing_state',
				'_billing_email',
				'_billing_phone',
				'_shipping_address_1',
				'_shipping_address_2',
				'_shipping_city',
				'_shipping_postcode',
				'_shipping_country',
				'_shipping_state'
			) ) );

			$search_order_id = str_replace( 'Order #', '', $args['s'] );
			if ( ! is_numeric( $search_order_id ) ) {
				$search_order_id = 0;
			}

			// Search orders
			$post_ids = array_unique( array_merge(
				$wpdb->get_col(
					$wpdb->prepare( "
						SELECT DISTINCT p1.post_id
						FROM {$wpdb->postmeta} p1
						INNER JOIN {$wpdb->postmeta} p2 ON p1.post_id = p2.post_id
						WHERE
							( p1.meta_key = '_billing_first_name' AND p2.meta_key = '_billing_last_name' AND CONCAT(p1.meta_value, ' ', p2.meta_value) LIKE '%%%s%%' )
						OR
							( p1.meta_key = '_shipping_first_name' AND p2.meta_key = '_shipping_last_name' AND CONCAT(p1.meta_value, ' ', p2.meta_value) LIKE '%%%s%%' )
						OR
							( p1.meta_key IN ('" . implode( "','", $search_fields ) . "') AND p1.meta_value LIKE '%%%s%%' )
						",
						esc_attr( $args['s'] ), esc_attr( $args['s'] ), esc_attr( $args['s'] )
					)
				),
				$wpdb->get_col(
					$wpdb->prepare( "
						SELECT order_id
						FROM {$wpdb->prefix}woocommerce_order_items as order_items
						WHERE order_item_name LIKE '%%%s%%'
						",
						esc_attr( $args['s'] )
					)
				),
				array( $search_order_id )
			) );

			// Remove s - we don't want to search order name
			unset($args['s']);

			// so we know we're doing this
			$args['shop_order_search'] = true;

			// Search by found posts
			if( !empty($args['post__in'])){
				$args['post__in'] = array_merge($args['post__in'], $post_ids);
			}else{
				$args['post__in'] = $post_ids;
			}
		}
		return $args;	
	}

	public function api_coupon_response($coupon_data, $coupon, $fields, $server)
    {
        if(!empty($coupon_data) && is_array($coupon_data)){
            $used_by = get_post_meta( $coupon_data['id'], '_used_by' );
            if($used_by)
                $coupon_data['used_by'] = (array) $used_by;
            else
                $coupon_data['used_by'] = null;

            if(!$coupon->expiry_date)
                $coupon_data['expiry_date'] = false;
            
            $coupon_data['maximum_amount'] = $coupon->maximum_amount;
            
        }
        return $coupon_data;
    }
	

}