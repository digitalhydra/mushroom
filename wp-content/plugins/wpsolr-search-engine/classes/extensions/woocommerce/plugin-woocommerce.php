<?php
use Solarium\QueryType\Select\Query\Query;

/**
 * Class PluginWooCommerce
 *
 * Manage WooCommerce plugin
 */
class PluginWooCommerce extends WpSolrExtensions {

	// Polylang options
	const _OPTIONS_NAME = 'wdm_solr_extension_woocommerce_data';

	// Product types
	const PRODUCT_TYPE_VARIABLE = 'variable';

	// Product category field
	const FIELD_PRODUCT_CAT_STR = 'product_cat_str';

	// Post type of orders
	const CONST_POST_TYPE_SHOP_ORDER = 'shop_order';

	// Order fields
	const FIELD_POST_DATE_DT = 'post_date_dt';
	const FIELD_ORDER_TOTAL_F = '_order_total_f';

	// WooCommerce url parameter 'orderby'
	const WOOCOMERCE_URL_PARAMETER_SORT_BY = 'orderby';

	// Url product category pattern.
	// Ex: /product-category/pcategory1 => pcategory1
	// Ex: /product-category/pcategory1/ => pcategory1
	// Ex: /anything/ => /anything/
	const URL_PATTERN_PRODUCT_CATEGORY = '/\/product-category\/([^\/]+)[$|\/|?]*.*/';

	/**
	 * Remove the top level of the category facet hierarchy on category pages.
	 * top_cat =>
	 * top_cat->current_cat =>
	 * top_cat->current_cat->sub_cat => sub_cat
	 */
	const REGEX_SUB_CATEGORIES = '/.*%s->(.*)/';

	/**
	 * Helper instance.
	 *
	 * @var \Solarium\Core\Query\Helper Helper
	 */
	protected $helper;

	/*
	 * @var bool $is_replace_category_search
	 */
	protected $is_replace_category_search;

	/*
	 * @var string $product_category_name
	 */
	protected $product_category_name;

	/*
	 * @var string $product_category_id
	 */
	protected $product_category_id;


	function __construct() {


		add_filter( WpSolrFilters::WPSOLR_FILTER_POST_CUSTOM_FIELDS, array(
			$this,
			'filter_custom_fields',
		), 10, 2 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, array(
			$this,
			'filter_post_statuses_to_index',
		), 10, 2 );

		add_action( WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY, array(
			$this,
			'wpsolr_action_solarium_query',
		), 10, 1 );

		add_action( WpSolrFilters::WPSOLR_FILTER_IS_REPLACE_BY_WPSOLR_QUERY, array(
			$this,
			'wpsolr_filter_is_replace_by_wpsolr_query',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE, array(
			$this,
			'add_fields_to_document_for_update',
		), 10, 4 );

		add_action( WpSolrFilters::WPSOLR_ACTION_URL_PARAMETERS, array(
			$this,
			'wpsolr_filter_url_parameters',
		), 10, 2 );

		// Customize the WooCOmmerce sort list-box
		add_filter( 'woocommerce_default_catalog_orderby_options', array(
			$this,
			'custom_woocommerce_catalog_orderby',
		), 10 );
		add_filter( 'woocommerce_catalog_orderby', array(
			$this,
			'custom_woocommerce_catalog_orderby',
		), 10 );

		add_action( WpSolrFilters::WPSOLR_FILTER_FACETS_TO_DISPLAY, array(
			$this,
			'wpsolr_filter_facets_to_display',
		), 10, 1 );

		add_action( WpSolrFilters::WPSOLR_FILTER_FACETS_CONTENT_TO_DISPLAY, array(
			$this,
			'wpsolr_filter_facets_content_to_display',
		), 10, 1 );
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	/**
	 * Factory
	 *
	 * @return PluginWooCommerce
	 */
	static function create() {

		return new self();
	}

	/**
	 * Return all woo commerce attributes names (slugs)
	 * @return array
	 */
	static function get_attribute_taxonomy_names() {

		$results = array();

		foreach ( self::get_attribute_taxonomies() as $woo_attribute ) {

			// Add woo attribute terms to custom fields
			array_push( $results, $woo_attribute->attribute_name );
		}

		return $results;
	}

	/**
	 * Return all woo commerce attributes
	 * @return array
	 */
	static function get_attribute_taxonomies() {

		// Standard woo function
		return wc_get_attribute_taxonomies();
	}


	public function get_is_category_search() {

		if ( isset( $this->is_replace_category_search ) ) {
			// Use cached value.
			return $this->is_replace_category_search;
		}

		$this->is_replace_category_search = ( WPSOLR_Global::getOption()->get_option_plugin_woocommerce_is_replace_product_category_search() && $this->is_product_category_url() );

		return $this->is_replace_category_search;
	}

	/**
	 * Extract product category from url.
	 * Must be done because is_product_category() does not work at this early stage.
	 *
	 * @return bool
	 */
	public function is_product_category_url() {

		$product_category_slug = preg_replace( self::URL_PATTERN_PRODUCT_CATEGORY, '$1', $_SERVER['REQUEST_URI'] );

		if ( $product_category_slug === $_SERVER['REQUEST_URI'] ) {
			return false;
		}

		$product_category = get_term_by( 'slug', $product_category_slug, 'product_cat' );
		if ( $product_category ) {
			$this->product_category_name = $product_category->name;
			$this->product_category_id   = $product_category->term_id;

			return true;
		}

		return false;
	}

	/**
	 *
	 * Replace WP query by a WPSOLR query when the current WP Query is an order type query.
	 *
	 * @param bool $is_replace_by_wpsolr_query
	 *
	 * @return bool
	 */
	public function wpsolr_filter_is_replace_by_wpsolr_query( $is_replace_by_wpsolr_query ) {
		global $wp_query;

		// A category page
		if ( $this->get_is_category_search() && ! is_admin() && is_main_query()
		     && WPSOLR_Global::getOption()->get_search_is_replace_default_wp_search()
		     && WPSOLR_Global::getOption()->get_search_is_use_current_theme_search_template()
		) {
			return true;
		}

		if ( is_admin() && WPSOLR_Global::getOption()->get_option_plugin_woocommerce_is_replace_admin_orders_search() ) {

			// ) && ! empty( $_REQUEST['s']
			if ( ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-admin/edit.php' ) ) && ! empty( $_REQUEST['post_type'] ) && ( self::CONST_POST_TYPE_SHOP_ORDER === $_REQUEST['post_type'] ) ) {
				// This is an order query, in the admin.
				return true;
			}
		}

		return $is_replace_by_wpsolr_query;
	}

	/**
	 *
	 * Add a filter on order post type.
	 *
	 * @param array $parameters
	 *
	 */
	public function wpsolr_action_solarium_query( $parameters ) {

		// @var WPSOLR_Query $wpsolr_query
		$wpsolr_query   = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY ];
		$solarium_query = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY ];
		// @var WPSolrSearchSolrClient $solarium_client
		$solarium_client = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT ];

		// post_type url parameter
		if ( ! empty( $wpsolr_query->query['post_type'] ) ) {

			// @var Document $solarium_query
			$solarium_query->addFilterQuery(
				array(
					'key'   => sprintf( 'woocommerce type:%s', $wpsolr_query->query['post_type'] ),
					'query' => sprintf( 'type:%s', $wpsolr_query->query['post_type'] ),
				)
			);
		}

		if ( is_admin() && WPSOLR_Global::getOption()->get_option_plugin_woocommerce_is_replace_admin_orders_search() ) {
			if ( ! empty( $wpsolr_query->query['post_type'] ) && ( self::CONST_POST_TYPE_SHOP_ORDER === $wpsolr_query->query['post_type'] ) ) {

				// sort by
				$wpsolr_order_by_mapping_fields = array(
					'ID'          => 'PID',
					'date'        => self::FIELD_POST_DATE_DT,
					'order_total' => self::FIELD_ORDER_TOTAL_F,
				);
				$original_order_by              = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'post_date';
				$orderby                        = ! empty( $wpsolr_order_by_mapping_fields[ $original_order_by ] ) ? $wpsolr_order_by_mapping_fields[ $original_order_by ] : self::FIELD_POST_DATE_DT;
				$order                          = ( empty( $_GET['order'] ) || ( 'desc' === $_GET['order'] ) ) ? Query::SORT_DESC : Query::SORT_ASC;
				$solarium_query->addSort( $orderby, $order );

				// Filter by order status
				$order_status = ! empty( $_GET['post_status'] ) ? $_GET['post_status'] : '';
				if ( ! empty( $order_status ) ) {
					$solarium_query->addFilterQuery(
						array(
							'key'   => 'post_status',
							'query' => sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_STATUS_S, $order_status ),
						)
					);
				}
			}
		} elseif ( is_search() && WPSOLR_Global::getOption()->get_option_plugin_woocommerce_is_replace_admin_orders_search() ) {
			// search page on front-end, filter out orders from results.

			// @var Document $solarium_query
			$solarium_query->addFilterQuery(
				array(
					'key'   => sprintf( '-type:%s', self::CONST_POST_TYPE_SHOP_ORDER ),
					'query' => sprintf( '-type:%s', self::CONST_POST_TYPE_SHOP_ORDER ),
				)
			);
		}

		// Add category filter on category pages
		if ( $this->get_is_category_search() ) {

			$filter_query_field_name = $solarium_client->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, self::FIELD_PRODUCT_CAT_STR );

			// @var Document $solarium_query
			$solarium_query->addFilterQuery(
				array(
					'key'   => sprintf( 'woocommerce %s:"%s"', $filter_query_field_name, $this->product_category_name ),
					'query' => sprintf( '%s:"%s"', $filter_query_field_name, $this->product_category_name ),
				)
			);

		}

	}

	/**
	 * Return post status valid for orders
	 *
	 * @param string[] $post_statuses
	 * @param WP_Post $post
	 *
	 * @return string[]
	 */
	public function filter_post_statuses_to_index( array $post_statuses ) {

		// Add order statuses to indexable statuses
		$results = array_merge( $post_statuses, array_keys( wc_get_order_statuses() ) );

		// Default statuses.
		return $results;
	}

	/**
	 * Add woo attributes to a custom field with the same name
	 *
	 * @param $custom_fields
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function filter_custom_fields( $custom_fields, $post_id ) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = array();
		}

		// Get the product correponding to this post
		$product = wc_get_product( $post_id );

		if ( false === $product ) {
			// Not a product
			return $custom_fields;
		}

		switch ( $product->get_type() ) {

			case self::PRODUCT_TYPE_VARIABLE:

				$product_variable = new WC_Product_Variable( $product );
				foreach ( $product_variable->get_available_variations() as $variation_array ) {

					foreach ( $variation_array['attributes'] as $attribute_name => $attribute_value ) {

						if ( ! isset( $custom_fields[ $attribute_name ] ) ) {
							$custom_fields[ $attribute_name ] = array();
						}

						if ( ! in_array( $attribute_value, $custom_fields[ $attribute_name ], true ) ) {

							array_push( $custom_fields[ $attribute_name ], $attribute_value );
						}
					}
				}


				break;

			default:

				foreach ( $product->get_attributes() as $attribute ) {

					//$terms = wc_get_product_terms( $product->id, $attribute['name'], array( 'fields' => 'names' ) );

					// Remove the eventual 'pa_' prefix from the global attribute name
					$attribute_name = $attribute['name'];
					if ( substr( $attribute_name, 0, 3 ) === 'pa_' ) {
						$attribute_name = substr( $attribute_name, 3, strlen( $attribute_name ) );
					}

					$custom_fields[ $attribute_name ] = explode( ',', $product->get_attribute( $attribute['name'] ) );
				}

				break;
		}


		return $custom_fields;
	}

	/**
	 * Add fields to a Solarium document
	 *
	 * @param $solarium_document_for_update
	 * @param $solr_indexing_options
	 * @param $post
	 * @param $attachment_body
	 *
	 * @return object Solarium document updated with fields
	 */
	function add_fields_to_document_for_update( $solarium_document_for_update, $solr_indexing_options, $post, $attachment_body ) {

		if ( self::CONST_POST_TYPE_SHOP_ORDER === $post->post_type ) {
			if ( ! $this->helper ) {
				$this->helper = new \Solarium\Core\Query\Helper();
			}

			// add order post_date for sorting
			if ( ! empty( $post->post_date ) ) {
				$field_name                                = self::FIELD_POST_DATE_DT;
				$solarium_document_for_update->$field_name = $this->helper->formatDate( $post->post_date );
			}
		}

		return $solarium_document_for_update;
	}

	/**
	 * Replace WooCommerce sort list with WPSOLR sort list
	 *
	 * @param array $sortby
	 *
	 * @return array
	 */
	function custom_woocommerce_catalog_orderby( $sortby ) {

		if ( ! WPSOLR_Global::getOption()->get_option_plugin_woocommerce_is_replace_sort_items() ) {
			// Use standard WooCommerce sort items.
			return $sortby;
		}

		$results = array();

		// Retrieve WPSOLR sort fields, with their translations.
		$sorts = WPSOLR_Data_Sort::get_data(
			WPSOLR_Global::getOption()->get_sortby_items_as_array(),
			WPSOLR_Global::getOption()->get_sortby_items_labels(),
			WPSOLR_Global::getQuery()->get_wpsolr_sort(),
			OptionLocalization::get_options()
		);

		if ( ! empty( $sorts ) && ! empty( $sorts['items'] ) ) {
			foreach ( $sorts['items'] as $sort_item ) {
				$results[ $sort_item['id'] ] = $sort_item['name'];
			}
		}

		return $results;
	}

	/**
	 * Map WooCommerce url orderby parameters with  WPSOLR's
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $url_parameters
	 *
	 */
	public
	function wpsolr_filter_url_parameters(
		WPSOLR_Query $wpsolr_query, $url_parameters
	) {

		if ( WPSOLR_Global::getOption()->get_option_plugin_woocommerce_is_replace_sort_items() ) {
			// Get WooCommerce order by value from url, or use the default one set in settings->products->display.

			$order_by_value = isset( $url_parameters[ self::WOOCOMERCE_URL_PARAMETER_SORT_BY ] )
				? wc_clean( $url_parameters[ self::WOOCOMERCE_URL_PARAMETER_SORT_BY ] )
				: apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

			if ( ! empty( $order_by_value ) ) {
				$wpsolr_query->set_wpsolr_sort( $order_by_value );
			}
		}
	}


	/**
	 * Remove product category of facets to display if we are on a category page.
	 *
	 * @param array $facets_to_display ['type', 'categories', 'product_cat_str']
	 *
	 * @return array
	 */
	public function wpsolr_filter_facets_to_display( array $facets_to_display ) {

		if ( $this->get_is_category_search() ) {
			$index = array_search( self::FIELD_PRODUCT_CAT_STR, $facets_to_display, true );
			if ( false !== $index ) {
				//unset( $facets_to_display[ $index ] );
			}
		}

		return $facets_to_display;
	}

	/**
	 * Remove the top level of the category facet hierarchy on category pages.
	 * top_cat =>
	 * top_cat->current_cat =>
	 * top_cat->current_cat->sub_cat => sub_cat
	 *
	 * @param array $facets_content
	 *
	 * @return array
	 */
	public function wpsolr_filter_facets_content_to_display( array $facets_content ) {

		if ( empty( $facets_content ) ) {
			return array();
		}

		if ( $this->get_is_category_search() && ! empty( $facets_content[ self::FIELD_PRODUCT_CAT_STR ] ) ) {

			foreach ( $facets_content as $facet_name => &$facet_items ) {

				if ( self::FIELD_PRODUCT_CAT_STR === $facet_name ) {

					foreach ( $facet_items as $index => &$facet_item ) {
						$value_without_top_level_hierarchy = preg_replace( sprintf( self::REGEX_SUB_CATEGORIES, preg_quote( $this->product_category_name, '/' ) ), '$1', $facet_item['value'] );

						if ( $facet_item['value'] !== $value_without_top_level_hierarchy ) {

							$facet_item['value'] = $value_without_top_level_hierarchy;
						} else {

							unset( $facet_items[ $index ] );
						}
					}
				}
			}
		}

		return $facets_content;
	}
}