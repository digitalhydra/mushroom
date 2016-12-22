<?php
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;

/**
 * Class OptionGeoLocation
 *
 * Manage Geolocation search
 */
class OptionGeoLocation extends WpSolrExtensions {

	// Prefix for the geolocation distance field(s)
	const GEOLOCATION_DISTANCE_FIELD_PREFIX = 'wpsolr_distance_';

	// Template for the geolocation distance field(s) name
	const TEMPLATE_GEOLOCATION_DISTANCE_FIELD_NAME = '%s%s';

	// HTML template for the geolocation distance div showed on each results
	const TEMPLATE_RESULTS_GEO_DISTANCE = '<div class="%s">%s</div>';

	// Class for the geolocation distance div showed on each results
	const WPSOLR_RESULTS_GEO_DISTANCE_CLASS = 'wpsolr_results_geo_distance';

	// Template for the geolocation distance field(s)
	const TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD = '%s%s:%s';

	// Template for the geolocation distance sort field(s)
	const TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD = 'geodist(%s,%s,%s)'; // geodist between field and 'lat,long'

	// Translation string labels
	const TRANSLATION_NAME_USER_AGREEMENT_CHECKBOX_LABEL = 'user agreement checkbox label'; // do not change

	// jQuery selector of an input by name
	const JQUERY_SELECTOR_INPUT_BY_NAME = "input[name*='%s']";

	// HTML fragment for the user agreement checkbox
	const TEMPLATE_CHECKBOX_USER_AGREEMENT = <<<'TAG'
<div class="wpsolr_is_geo_checkbox"><input type="checkbox" name="%s" value="%s" %s >%s</div>
TAG;


	// Function to calculate distance
	const GEO_DISTANCE = 'geodist()';

	// Solr type for LatLong fields
	const _SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE = '_ll';

	// Format lat,long
	const FORMAT_LAT_LONG = '%s,%s';

	/**
	 * Constructor
	 * Subscribe to actions/filters
	 **/
	function __construct() {

		if ( WPSOLR_Global::getOption()->get_option_geolocation_is_show_user_agreement_ajax() ) {

			/*
			add_filter( WpSolrFilters::WPSOLR_FILTER_APPEND_FIELDS_TO_AJAX_SEARCH_FORM, array(
				$this,
				'wpsolr_filter_add_geo_user_agreement_checkbox_to_ajax_search_form',
			), 10, 1 );*/

			add_action( WpSolrFilters::WPSOLR_ACTION_URL_PARAMETERS, array(
				$this,
				'wpsolr_filter_url_parameters',
			), 10, 2 );

		}

		add_filter( WpSolrFilters::WPSOLR_FILTER_JAVASCRIPT_FRONT_LOCALIZED_PARAMETERS, array(
			$this,
			'wpsolr_filter_javascript_front_localized_parameters',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SORT, array(
			$this,
			'wpsolr_filter_add_sort',
		), 10, 3 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_FIELDS, array(
			$this,
			'wpsolr_filter_add_fields',
		), 10, 3 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SOLR_FIELD_TYPES, array(
			$this,
			'wpsolr_filter_solr_field_types',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_DEFAULT_SORT_FIELDS, array(
			$this,
			'wpsolr_filter_default_sort_fields',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SORT_FIELDS, array(
			$this,
			'wpsolr_filter_sort_fields',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_DEFAULT_SORT, array(
			$this,
			'wpsolr_filter_default_sort',
		), 10, 2 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SOLR_RESULTS_APPEND_CUSTOM_HTML, array(
			$this,
			'wpsolr_filter_solr_results_append_custom_html',
		), 10, 4 );

		add_action( WpSolrFilters::WPSOLR_ACTION_POSTS_RESULTS, array(
			$this,
			'wpsolr_action_posts_results',
		), 10, 2 );

		add_action( WpSolrFilters::WPSOLR_FILTER_INDEX_SANITIZE_FIELD, array(
			$this,
			'wpsolr_filter_index_sanitize_field',
		), 10, 5 );

		add_action( WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY, array(
			$this,
			'wpsolr_action_solarium_query',
		), 10, 1 );
	}

	/**
	 *
	 * Add a filter to remove empty coordinates from results.
	 *
	 * @param $parameters array
	 *
	 * @throws Exception
	 */
	public function wpsolr_action_solarium_query( $parameters ) {

		// @var WPSOLR_Query $wpsolr_query
		$wpsolr_query = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY ];

		if ( $this->get_is_geolocation( $wpsolr_query ) ) {

			if ( WPSOLR_Global::getOption()->get_option_geolocation_is_filter_results_with_empty_coordinates() ) {

				$solarium_query = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY ];

				foreach ( WPSOLR_Global::getOption()->get_option_index_custom_fields() as $custom_field_name ) {

					if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === WpSolrSchema::get_custom_field_solr_type( $custom_field_name ) ) {
						// Found a geolocation field: exclude results without a value in that field

						/**
						 * Exclude all documents without geolocation
						 * For that we use a trick: all fields are clone in field_SOLR_DYNAMIC_TYPE_STRING1
						 */
						$solarium_query->addFilterQuery(
							array(
								'key'   => sprintf( 'geo_exclude_empty_%s', $custom_field_name ),
								'query' => sprintf( '%s:*', WpSolrSchema::replace_field_name_extension_with( $custom_field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING1, true ) ),
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Sanitize a longitude,latitude location value
	 * Try to convert it to a double,double else throw an exception.
	 *
	 * @param $default_value Null
	 * @param WP_Post $post
	 * @param string $field_name
	 * @param mixed $value
	 * @param string $field_type
	 *
	 * @return float
	 */
	public static
	function wpsolr_filter_index_sanitize_field(
		$default_value, $post, $field_name, $value, $field_type
	) {

		if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === $field_type ) {

			if ( empty( $value ) ) {
				return $value;
			}

			$lat_long_array = explode( ',', $value );
			if ( 2 === count( $lat_long_array ) ) {
				// Comma separated string: ok

				$latitude  = WpSolrSchema::get_sanitized_float_value( $post, $field_name, $lat_long_array[0], $field_type );
				$latitude  = floatval( $latitude );
				$longitude = WpSolrSchema::get_sanitized_float_value( $post, $field_name, $lat_long_array[1], $field_type );
				$longitude = floatval( $longitude );
				if ( ( - 90 <= $latitude ) && ( $latitude <= 90 ) && ( - 180 <= $longitude ) && ( $longitude <= 180 ) ) {
					// Latitude is a float between -90° and +90°
					// Longitude is a float between -180° and +180°
					return $value;
				}
			}


			// wrong format. Send error.
			WpSolrSchema::throw_sanitized_error( $post, $field_name, $value, $field_type );
		}

		// Type is not a geolocation: continue.
		return null;
	}

	/**
	 * Add distance custom fields to the post results
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param Result $solarium_results
	 *
	 */
	public function wpsolr_action_posts_results( WPSOLR_Query $wpsolr_query, Result $solarium_results ) {

		if ( empty( $wpsolr_query->posts ) || empty( $solarium_results ) ) {
			// No results: nothing to do.
			return;
		}

		// Name of the field added to the post containing a list of distances
		$field_distance_name = WPSOLR_Regexp::remove_string_at_the_end( self::GEOLOCATION_DISTANCE_FIELD_PREFIX, '_' );

		foreach ( WPSOLR_Global::getOption()->get_option_index_custom_fields() as $custom_field_name ) {

			if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === WpSolrSchema::get_custom_field_solr_type( $custom_field_name ) ) {
				// Add geolocation fields to the fields

				$distance_field_name = $this->get_distance_field_name( $custom_field_name );

				foreach ( $solarium_results as $document ) {

					if ( $document->$distance_field_name ) {

						foreach ( $wpsolr_query->posts as $post ) {

							if ( $post->ID === (int) $document->PID ) {

								if ( empty( $post->$field_distance_name ) ) {
									$post->$field_distance_name = array();
								}

								array_push(
									$post->$field_distance_name,
									(object) array(
										'field_name'      => $custom_field_name,
										'distance'        => number_format( $document->$distance_field_name, 2, '.', ' ' ),
										// distance formatted
										'distance_number' => $document->$distance_field_name,
										// distance not formatted
									)
								);
							}
						}
					}
				}
			}
		}

		return;
	}

	/**
	 * Generate geolocation distance html to append to results
	 *
	 * @param $default_html
	 * @param $user_id
	 * @param Document $document
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return array *
	 */
	public function wpsolr_filter_solr_results_append_custom_html( $default_html, $user_id, Document $document, WPSOLR_Query $wpsolr_query ) {

		$result = '';

		$template_text = WPSOLR_Global::getOption()->get_option_geolocation_result_distance_label();

		if ( ! empty( $template_text ) && $this->get_is_geolocation( $wpsolr_query ) ) {

			foreach ( WPSOLR_Global::getOption()->get_option_index_custom_fields() as $custom_field_name ) {

				if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === WpSolrSchema::get_custom_field_solr_type( $custom_field_name ) ) {
					// Add geolocation fields to the fields

					$distance_field_name = $this->get_distance_field_name( $custom_field_name );

					if ( $document->$distance_field_name ) {

						$distance_field_name_translated = WPSOLR_Translate::translate_field_custom_field(
							WPSOLR_Option::TRANSLATION_DOMAIN_SORT_LABEL,
							$custom_field_name,
							WPSOLR_Global::getOption()->get_option_geolocation_user_aggreement_label()
						);


						$result .= sprintf( self::TEMPLATE_RESULTS_GEO_DISTANCE,
							self::WPSOLR_RESULTS_GEO_DISTANCE_CLASS,
							sprintf( $template_text, $distance_field_name_translated, number_format( $document->$distance_field_name, 2, '.', ' ' )
							)
						);
					}
				}
			}
		}

		// No default geolocation default sort, or not a geolocation search: use the general default sort.
		return $result;
	}

	/**
	 * Get geolocation default sort
	 *
	 * @param string $default_sort
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return array
	 */
	public function wpsolr_filter_default_sort( $default_sort, WPSOLR_Query $wpsolr_query ) {

		if ( $this->get_is_geolocation( $wpsolr_query ) ) {
			// It's a geolocation search, use default geolocation default sort
			$geolocation_default_sort = WPSOLR_Global::getOption()->get_option_geolocation_default_sort();

			if ( ! empty( $geolocation_default_sort ) ) {
				// Use default geolocation default sort
				return $geolocation_default_sort;
			}
		}

		// No default geolocation default sort, or not a geolocation search: use the general default sort.
		return $default_sort;
	}

	/**
	 * Get geolocation sort fields to show in a drop-down list
	 *
	 * @return array
	 */
	public static function get_sort_fields() {
		// Just add an additional sort item, to revert to the default non-geolocation choice.
		$results = array_merge( array(
			array(
				'code'  => '',
				'label' => 'Use non geolocation default sort',
			),
		), WpSolrSchema::get_sort_fields() );

		return $results;
	}


	/**
	 * Is the current visitor location valid ?
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return bool
	 *
	 */
	public function get_is_geolocation( WPSOLR_Query $wpsolr_query ) {

		$wpsolr_latitude  = $wpsolr_query->get_wpsolr_latitude();
		$wpsolr_longitude = $wpsolr_query->get_wpsolr_longitude();

		return ( ! empty( $wpsolr_latitude ) && ! empty( $wpsolr_longitude ) );
	}

	/**
	 * @param Query $solarium_query
	 * @param $latitude
	 * @param $longitude
	 */
	public function add_geolocation_center_point_coordinates( Query $solarium_query, $latitude, $longitude ) {

		$solarium_query->addParam( 'pt', $latitude . ',' . $longitude );
	}

	/**
	 * @param Query $solarium_query
	 * @param $field_name
	 */
	public function add_geolocation_field( Query $solarium_query, $field_name ) {

		$solarium_query->addParam( 'sfield', $field_name );
	}

	/**
	 * Add the geolocation sort selected to the Solr query
	 *
	 * @param \Solarium\QueryType\Select\Query\Query $solarium_query
	 * @param string $sort_field_name
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return mixed
	 */
	public function wpsolr_filter_add_sort( $solarium_query, $sort_field_name, WPSOLR_Query $wpsolr_query ) {

		// Get field name without _asc or _desc ('price_str_asc' => 'price_str')
		$sort_field_without_order = WpSolrSchema::get_field_without_sort_order_ending( $sort_field_name );

		if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === WpSolrSchema::get_custom_field_solr_type( $sort_field_without_order ) ) {

			$sort_field_name = WpSolrSchema::replace_field_name_extension( $sort_field_without_order );

			if ( $this->get_is_geolocation( $wpsolr_query ) ) {
				// Geo dist field with a geo center point (current visitor location)

				$sorts = $solarium_query->getSorts();
				if ( ! empty( $sorts ) && ! empty( $sorts[ $sort_field_name ] ) ) {

					// Use the sort by distance
					$solarium_query->addSort( $this->get_anonymous_geodistance_query_for_field( $sort_field_name, $wpsolr_query ), $sorts[ $sort_field_name ] );

					// Filter out results without coordinates
					/*
					 * does not work with some Solr versions
					 $solarium_query->addFilterQuery(
						array(
							'key'   => 'geo_exclude_empty',
							'query' => sprintf( '%s:[-90,-180 TO 90,180]', $sort_field_name ),
						)
					);*/

				}
			}

			// Remove the field from the sorts, as we use a function instead,
			// or we do not use the field as sort because geolocation is missing.
			$solarium_query->removeSort( $sort_field_name );
		}

		return $solarium_query;
	}

	/**
	 * Add geolocation fields to the solr query fields
	 *
	 * @param array $fields
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return string
	 * @internal param array $parameters
	 */
	public
	function wpsolr_filter_add_fields(
		$fields, WPSOLR_Query $wpsolr_query
	) {

		if ( $this->get_is_geolocation( $wpsolr_query ) ) {

			foreach ( WPSOLR_Global::getOption()->get_option_index_custom_fields() as $custom_field_name ) {

				if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === WpSolrSchema::get_custom_field_solr_type( $custom_field_name ) ) {
					// Add geolocation fields to the fields
					$fields[] = $this->get_named_geodistance_query_for_field( $custom_field_name, $wpsolr_query );
				}
			}
		}

		return $fields;
	}


	/**
	 * Generate a distance query for a field, and name the query
	 * 'field_name1' => wpsolr_distance_field_name1:geodist(field_name1_ll, center_point_lat, center_point_long)
	 *
	 * @param $field_name
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return string
	 */
	public function get_named_geodistance_query_for_field( $field_name, WPSOLR_Query $wpsolr_query ) {
		return sprintf( self::TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD,
			self::GEOLOCATION_DISTANCE_FIELD_PREFIX,
			WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
			$this->get_anonymous_geodistance_query_for_field( $field_name, $wpsolr_query )
		);
	}

	/**
	 * Generate a distance query for a field
	 * 'field_name1' => geodist(field_name1_ll, center_point_lat, center_point_long)
	 *
	 * @param $field_name
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return string
	 */
	public function get_anonymous_geodistance_query_for_field( $field_name, WPSOLR_Query $wpsolr_query ) {
		return sprintf( self::TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD,
			WpSolrSchema::replace_field_name_extension( $field_name ),
			$wpsolr_query->get_wpsolr_latitude(),
			$wpsolr_query->get_wpsolr_longitude()
		);
	}

	/**
	 * Generate a distance field name from a field
	 * 'field_name1' => wpsolr_distance_field_name1
	 *
	 * @param $field_name
	 *
	 * @return string
	 */
	public function get_distance_field_name( $field_name ) {
		return sprintf( self::TEMPLATE_GEOLOCATION_DISTANCE_FIELD_NAME, self::GEOLOCATION_DISTANCE_FIELD_PREFIX, WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) );
	}

	/**
	 * Add geolocation solr field types
	 *
	 * @param array $field_types
	 *
	 * @return array $field_types
	 */
	public
	function wpsolr_filter_solr_field_types(
		$field_types
	) {
		global $license_manager;

		$field_types[ self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE ] = array(
			'label'    => 'geolocation, (Latitude,Longitude), sortable',
			'sortable' => true,
			'disabled' => ( null !== $license_manager ) ? $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_GEOLOCATION, true ) : '',
		);

		return $field_types;
	}


	/**
	 * Remove geolocation solr field types from the default sort list
	 *
	 * @param array $default_sort_fields
	 *
	 * @return array $fields
	 */
	public
	function wpsolr_filter_default_sort_fields(
		$default_sort_fields
	) {

		$results = array();

		foreach ( $default_sort_fields as $sort_field ) {

			$sort_field_without_order = WpSolrSchema::get_field_without_sort_order_ending( $sort_field['code'] );

			if ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE !== WpSolrSchema::get_custom_field_solr_type( $sort_field_without_order ) ) {

				// Not a geolocation field: keep it.
				$results[] = $sort_field;
			}
		}

		return $results;
	}


	/**
	 * Remove geolocation solr field types from the default sort list
	 *
	 * @param array $default_sort_fields
	 *
	 * @return array $fields
	 */
	public
	function wpsolr_filter_sort_fields(
		$default_sort_fields
	) {

		return $default_sort_fields;

		/*
		$results = array();

		foreach ( $default_sort_fields as $sort_field_name ) {

			$sort_field_without_order = WpSolrSchema::get_field_without_sort_order_ending( $sort_field_name );

			if ( empty( $this->fields_to_add_to_query ) && ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE !== WpSolrSchema::get_custom_field_solr_type( $sort_field_without_order ) ) ) {

				// Not a geolocation field: keep it.
				$results[] = $sort_field_name;
			} elseif ( ! empty( $this->fields_to_add_to_query ) && ( self::_SOLR_DYNAMIC_TYPE_LATITUDE_LONGITUDE === WpSolrSchema::get_custom_field_solr_type( $sort_field_without_order ) ) ) {

				// Geolocation field: keep it.
				$results[] = $sort_field_name;
			}
		}

		return $results;
		*/
	}

	/**
	 * Add geolocation parameters to the javascript front-end
	 *
	 * @param array $parameters
	 *
	 * @return string
	 */
	public
	function wpsolr_filter_javascript_front_localized_parameters(
		$parameters
	) {

		if ( WPSOLR_Global::getOption()->get_option_geolocation_is_show_user_agreement_ajax() ) {
			$parameters['WPSOLR_FILTER_ADD_GEO_USER_AGREEMENT_CHECKBOX_TO_AJAX_SEARCH_FORM'] = $this->wpsolr_filter_add_geo_user_agreement_checkbox_to_ajax_search_form();
		}
		$parameters['WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR']     = $this->wpsolr_filter_geolocation_search_box_jquery_selector();
		$parameters['WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR'] = $this->wpsolr_filter_geolocation_user_agreement_jquery_selector();
		$parameters['SEARCH_PARAMETER_LATITUDE']                                = WPSOLR_Query_Parameters::SEARCH_PARAMETER_LATITUDE;
		$parameters['SEARCH_PARAMETER_LONGITUDE']                               = WPSOLR_Query_Parameters::SEARCH_PARAMETER_LONGITUDE;
		$parameters['PARAMETER_VALUE_YES']                                      = WPSOLR_Query_Parameters::PARAMETER_VALUE_YES;
		$parameters['PARAMETER_VALUE_NO']                                       = WPSOLR_Query_Parameters::PARAMETER_VALUE_NO;

		return $parameters;
	}

	/**
	 * Set the geolocation user agreement parameter
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $url_parameters
	 *
	 */
	public
	function wpsolr_filter_url_parameters(
		WPSOLR_Query $wpsolr_query, $url_parameters
	) {

		$wpsolr_query->set_wpsolr_is_geo(
			isset( $url_parameters[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_GEO_USER_AGREEMENT ] )
				? $url_parameters[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_GEO_USER_AGREEMENT ]
				: ( WPSOLR_Global::getOption()->get_option_geolocation_is_show_user_agreement_ajax_is_default_yes() ? WPSOLR_Query_Parameters::PARAMETER_VALUE_YES : WPSOLR_Query_Parameters::PARAMETER_VALUE_NO )
		);
	}

	/**
	 * Get the the user agreement checkbox HTML
	 *
	 * @return string
	 */
	public
	function wpsolr_filter_add_geo_user_agreement_checkbox_to_ajax_search_form() {

		$user_agreement_checkbox_label = WPSOLR_Global::getOption()->get_option_geolocation_user_aggreement_label();
		if ( ! empty( $user_agreement_checkbox_label ) ) {
			// Give plugins a chance to change the sort label (WPML, POLYLANG).
			$user_agreement_checkbox_label = apply_filters( WpSolrFilters::WPSOLR_FILTER_TRANSLATION_STRING, $user_agreement_checkbox_label,
				array(
					'domain' => WPSOLR_Option::TRANSLATION_DOMAIN_SORT_LABEL,
					'name'   => self::TRANSLATION_NAME_USER_AGREEMENT_CHECKBOX_LABEL,
					'text'   => $user_agreement_checkbox_label,
				)
			);

		} else {
			$user_agreement_checkbox_label = OptionLocalization::get_term( OptionLocalization::get_options(), 'geolocation_ask_user' );
		}

		$result = sprintf(
			self::TEMPLATE_CHECKBOX_USER_AGREEMENT,
			WPSOLR_Query_Parameters::SEARCH_PARAMETER_GEO_USER_AGREEMENT,
			WPSOLR_Global::getQuery()->get_wpsolr_is_geo(),
			checked( WPSOLR_Global::getQuery()->get_wpsolr_is_geo(), WPSOLR_Query_Parameters::PARAMETER_VALUE_YES, false ),
			$user_agreement_checkbox_label
		);

		return $result;
	}

	/**
	 * Geolocation jquery selector of search box(es)
	 * @return string
	 */
	public
	function wpsolr_filter_geolocation_search_box_jquery_selector() {
		return $this->concat_selectors( '.' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT, WPSOLR_Global::getOption()->get_option_geolocation_jquery_selector() );
	}

	/**
	 * Geolocation jquery selector of user agreement's checkbox
	 * @return string
	 */
	public
	function wpsolr_filter_geolocation_user_agreement_jquery_selector() {
		return $this->concat_selectors(
			sprintf( self::JQUERY_SELECTOR_INPUT_BY_NAME, WPSOLR_Query_Parameters::SEARCH_PARAMETER_GEO_USER_AGREEMENT )
			, WPSOLR_Global::getOption()->get_option_geolocation_selector_user_aggreement()
		);
	}

	/**
	 * Concat the default Ajax selector with the selector option.
	 *
	 * @param $default_selector
	 * @param $selector
	 *
	 * @return string
	 */
	public
	function concat_selectors(
		$default_selector, $selector
	) {
		return empty( $selector ) ? $default_selector : $default_selector . ',' . $selector;
	}
}
