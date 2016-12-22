<?php
use Solarium\QueryType\Select\Query\Query;

/**
 * Manage schema.xml definitions
 */
class WpSolrSchema {

	const EXTENSION_SEPARATOR = '_';

	// Solr dynamic types extensions
	const _SOLR_DYNAMIC_TYPE_STRING = '_str';
	const _SOLR_DYNAMIC_TYPE_STRING1 = '_srch';
	const _SOLR_DYNAMIC_TYPE_S = '_s';
	const _SOLR_DYNAMIC_TYPE_INTEGER = '_i';
	const _SOLR_DYNAMIC_TYPE_INTEGER_LONG = '_l';
	const _SOLR_DYNAMIC_TYPE_FLOAT = '_f';
	const _SOLR_DYNAMIC_TYPE_FLOAT_DOUBLE = '_d';
	const _SOLR_DYNAMIC_TYPE_DATE = '_dt';
	const _SOLR_DYNAMIC_TYPE_CUSTOM_FIELD = 'custom_field';

	// Conversion error message
	const ERROR_SANITIZED_MESSAGE = 'Value %s of field "%s" of post->ID=%s ("%s") is not of type "%s". Check out field\'s definition in WPSOLR data settings (tab 2.2) .';

	// @var \Solarium\Core\Query\Helper $helper
	private static $helper;

	// @property array List of Solr dynamic types extensions
	protected static $solr_dynamic_types;

	// Field queried by default. Necessary to get highlighting right.
	const _FIELD_NAME_DEFAULT_QUERY = 'text';

	/*
	 * Solr document field names
	 */
	const _FIELD_NAME_ID = 'id';
	const _FIELD_NAME_PID = 'PID';
	const _FIELD_NAME_TITLE = 'title';
	const _FIELD_NAME_STATUS_S = 'post_status_s'; // post status, sortable
	const _FIELD_NAME_CONTENT = 'content';
	const _FIELD_NAME_AUTHOR = 'author';
	const _FIELD_NAME_AUTHOR_S = 'author_s';
	const _FIELD_NAME_TYPE = 'type';
	const _FIELD_NAME_DATE = 'date';
	const _FIELD_NAME_MODIFIED = 'modified';
	const _FIELD_NAME_DISPLAY_DATE = 'displaydate';
	const _FIELD_NAME_DISPLAY_MODIFIED = 'displaymodified';
	const _FIELD_NAME_PERMALINK = 'permalink';
	const _FIELD_NAME_COMMENTS = 'comments';
	const _FIELD_NAME_NUMBER_OF_COMMENTS = 'numcomments';
	const _FIELD_NAME_CATEGORIES = 'categories';
	const _FIELD_NAME_CATEGORIES_STR = 'categories_str';
	const _FIELD_NAME_TAGS = 'tags';
	const _FIELD_NAME_CUSTOM_FIELDS = 'categories';
	const _FIELD_NAME_FLAT_HIERARCHY = 'flat_hierarchy_%s'; // field contains hierarchy as a string with separator
	const _FIELD_NAME_NON_FLAT_HIERARCHY = 'non_flat_hierarchy_%s'; // filed contains hierarchy as an array
	const _FIELD_NAME_BLOG_NAME_STR = 'blog_name_str';
	const _FIELD_NAME_POST_THUMBNAIL_HREF_STR = 'post_thumbnail_href_str';
	const _FIELD_NAME_POST_HREF_STR = 'post_href_str';

	// Separator of a flatten hierarchy
	const FACET_HIERARCHY_SEPARATOR = '->';

	/*
		 * Dynamic types
		 */
	// Solr dynamic type postfix for text
	const _DYNAMIC_TYPE_POSTFIX_TEXT = '_t';


	// Definition translated fields when multi-languages plugins are activated
	public static $multi_language_fields = array(
		array(
			'field_name'      => self::_FIELD_NAME_TITLE,
			'field_extension' => self::_DYNAMIC_TYPE_POSTFIX_TEXT,
		),
		array(
			'field_name'      => self::_FIELD_NAME_CONTENT,
			'field_extension' => self::_DYNAMIC_TYPE_POSTFIX_TEXT,
		),
	);

	/**
	 * Get all extensions
	 *
	 * @return array
	 */
	public static function get_solr_dynamic_entensions() {

		if ( empty( self::$solr_dynamic_types ) ) {
			// cache

			self::$solr_dynamic_types = apply_filters( WpSolrFilters::WPSOLR_FILTER_SOLR_FIELD_TYPES, array(
				self::_SOLR_DYNAMIC_TYPE_STRING  => array(
					'label'      => 'Text, not sortable, multivalued',
					'sortable'   => false,
					'multivalue' => true,
					'disabled'   => '',
					'help_id'    => WPSOLR_Help::HELP_SCHEMA_TYPE_DATE,
				),
				self::_SOLR_DYNAMIC_TYPE_S       => array(
					'label'      => 'Text, sortable',
					'sortable'   => true,
					'multivalue' => false,
					'disabled'   => '',
					'help_id'    => WPSOLR_Help::HELP_SCHEMA_TYPE_DATE,
				),
				self::_SOLR_DYNAMIC_TYPE_INTEGER => array(
					'label'      => 'Integer number, sortable',
					'sortable'   => true,
					'multivalue' => false,
					'disabled'   => '',
					'help_id'    => WPSOLR_Help::HELP_SCHEMA_TYPE_DATE,
				),
				self::_SOLR_DYNAMIC_TYPE_FLOAT   => array(
					'label'      => 'Floating point number, sortable',
					'sortable'   => true,
					'multivalue' => false,
					'disabled'   => '',
					'help_id'    => WPSOLR_Help::HELP_SCHEMA_TYPE_DATE,
				),
				self::_SOLR_DYNAMIC_TYPE_DATE    => array(
					'label'      => 'Date, sortable',
					'sortable'   => true,
					'multivalue' => false,
					'disabled'   => '',
					'help_id'    => WPSOLR_Help::HELP_SCHEMA_TYPE_DATE,
				),
				/*
				self::_SOLR_DYNAMIC_TYPE_INTEGER_LONG => array('label' => 'Big integer, sortable','sortable' => false,),
				self::_SOLR_DYNAMIC_TYPE_FLOAT        => array(
					'label'    => 'Floating point number, sortable',
					'sortable' => false,
				),
				self::_SOLR_DYNAMIC_TYPE_FLOAT_DOUBLE => array(
					'label'    => 'Double float',
					'sortable' => true,
				),
				self::_SOLR_DYNAMIC_TYPE_CUSTOM_FIELD => array(
					'label'    => 'Field defined in schema.xml',
					'sortable' => true,
				),
				*/
			) );
		}

		return self::$solr_dynamic_types;
	}

	/**
	 * Get extension id used by default
	 *
	 * @return array
	 */
	public static function get_solr_dynamic_entension_id_by_default() {
		return self::_SOLR_DYNAMIC_TYPE_STRING;
	}


	/**
	 * Get extension label
	 *
	 * @param $solr_dynamic_type
	 *
	 * @return string
	 */
	public static function get_solr_dynamic_entension_label( $solr_dynamic_type ) {

		return ( ! empty( $solr_dynamic_type ) && ! empty( $solr_dynamic_type['label'] ) ? $solr_dynamic_type['label'] : '' );
	}

	/**
	 * Is extension id sortable ?
	 *
	 * @param $solr_dynamic_type
	 *
	 * @return string
	 */
	public static function get_solr_dynamic_entension_is_sortable( $solr_dynamic_type ) {

		return ( ! empty( $solr_dynamic_type ) && ! empty( $solr_dynamic_type['sortable'] ) ? $solr_dynamic_type['sortable'] : false );
	}

	/**
	 * Get an extension definition by it's id
	 *
	 * @param string $solr_dynamic_type_id
	 *
	 * @return array
	 */
	public static function get_solr_dynamic_entension( $solr_dynamic_type_id ) {

		$extensions = self::get_solr_dynamic_entensions();

		return ( ! empty( $extensions[ $solr_dynamic_type_id ] ) ? $extensions[ $solr_dynamic_type_id ] : array() );
	}

	/**
	 * Is an extension id sortable ?
	 *
	 * @param $solr_dynamic_type_id
	 *
	 * @return string
	 */
	public static function get_solr_dynamic_entension_id_is_sortable( $solr_dynamic_type_id ) {

		$extension = self::get_solr_dynamic_entension( $solr_dynamic_type_id );

		return self::get_solr_dynamic_entension_is_sortable( $extension );
	}

	/**
	 * Gey an extension id label
	 *
	 * @param $solr_dynamic_type_id
	 *
	 * @return string
	 */
	public static function get_solr_dynamic_entension_id_label( $solr_dynamic_type_id ) {

		$extension = self::get_solr_dynamic_entension( $solr_dynamic_type_id );

		return self::get_solr_dynamic_entension_label( $extension );
	}

	/**
	 * Get a custom field solr type
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return string Field Type
	 */
	public static function get_custom_field_solr_type(
		$field_name
	) {

		$custom_fields = WPSOLR_Global::getOption()->get_option_index_custom_field_properties();

		if ( ! empty( $custom_fields[ $field_name ] )
		     && ! empty( $custom_fields[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
		) {
			return $custom_fields[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ];
		}

		// Default if type not found
		return self::_SOLR_DYNAMIC_TYPE_STRING;
	}

	/**
	 * Get field without ending '_asc' or '_desc' ('price_str_asc' => 'price_str', 'price_str_desc' => 'price_str')
	 *
	 * @param string $field_name_with_order Field name (like 'price_str_asc')
	 *
	 * @return string
	 */
	public static function get_field_without_sort_order_ending(
		$field_name_with_order
	) {

		$result = $field_name_with_order;
		$result = WPSOLR_Regexp::remove_string_at_the_end( $result, '_' . \Solarium\QueryType\Select\Query\Query::SORT_ASC );
		$result = WPSOLR_Regexp::remove_string_at_the_end( $result, '_' . \Solarium\QueryType\Select\Query\Query::SORT_DESC );

		return $result;
	}

	/**
	 * Get custom field properties
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return array
	 */
	public static function get_custom_field_properties( $field_name ) {

		// Get the properties of custom fields
		$custom_fields_properties = WPSOLR_Global::getOption()->get_option_index_custom_field_properties();

		$result = ( ! empty( $custom_fields_properties[ $field_name ] ) ? $custom_fields_properties[ $field_name ] : array() );

		return $result;
	}

	/**
	 * Get custom field type
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return string
	 */
	public static function get_custom_field_dynamic_type( $field_name ) {

		// Get the properties of this field
		$custom_field_properties = self::get_custom_field_properties( $field_name );

		$result = ( ! empty( $custom_field_properties[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
			? $custom_field_properties[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ]
			: '' );

		return $result;
	}

	/**
	 * For compatibility reasons with previous versions (13.5), all custom fields are ending with _str.
	 * In field name, replace _str by a dynamic type
	 * ('price_str', '_f') => 'price_f'
	 *
	 * @param string $field_name Field name, like 'price_str', or 'title'
	 *
	 * @return string
	 */
	public static function replace_field_name_extension( $field_name ) {

		$solr_dynamic_type_id = self::get_custom_field_dynamic_type( $field_name );

		$result = ! empty( $solr_dynamic_type_id )
			? str_replace( self::_SOLR_DYNAMIC_TYPE_STRING, $solr_dynamic_type_id, $field_name )
			: $field_name;

		return $result;
	}

	/**
	 * In field name, replace dynamic type by an extension type
	 * 'price_f, '_str' => 'price_str'
	 * 'title', '_str' => 'title'
	 * 'field1_str', '_str' => 'field1_str'
	 *
	 * @param string $field_name Field name, like 'price_str', or 'title'
	 * @param string $field_type_extension Solt type extension, lile '_str'
	 *
	 * @return string
	 */
	public static function replace_field_name_extension_with( $field_name, $field_type_extension, $is_forced = false ) {

		$extension = self::EXTENSION_SEPARATOR . WPSOLR_Regexp::extract_last_separator( $field_name, self::EXTENSION_SEPARATOR );

		if ( ! $is_forced ) {
			if ( ( self::EXTENSION_SEPARATOR === $extension ) || ( self::_SOLR_DYNAMIC_TYPE_STRING === $extension ) ) {
				// No extension, nothing to do: title, content ... remain the same
				// color_str ... remain the same
				return $field_name;
			}

			if ( ! array_key_exists( $extension, self::get_solr_dynamic_entensions() ) ) {
				// Extension is unknown, do nothing
				// price_def
				return $field_name;
			}
		}

		return WPSOLR_Regexp::remove_string_at_the_end( $field_name, $extension ) . $field_type_extension;
	}


	/**
	 * Get all sort fields ready to be put in a drop-down list
	 *
	 * @return array
	 */
	public static function get_sort_fields() {

		$result = WPSolrSearchSolrClient::get_sort_options();

		$custom_fields_sortable = array();
		foreach ( WPSOLR_Global::getOption()->get_option_index_custom_field_properties() as $custom_field_name => $custom_field_property ) {

			// Only sortable extension types can be sorted
			if ( self::get_solr_dynamic_entension_id_is_sortable( $custom_field_property[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] ) ) {

				// Add asc and desc for each sortable field
				foreach (
					array(
						array( Query::SORT_ASC, 'ascending' ),
						array( Query::SORT_DESC, 'descending' ),
					) as $sort_order
				) {
					$custom_fields_sortable[] = array(
						'code'  => sprintf( '%s_%s', $custom_field_name, $sort_order[0] ),
						'label' => sprintf( '%s %s', str_replace( self::_SOLR_DYNAMIC_TYPE_STRING, '', $custom_field_name ), $sort_order[1] ),
					);
				}
			}
		}

		$result = array_merge( $result, $custom_fields_sortable );

		return $result;
	}

	/**
	 * Get field without ending self::_SOLR_DYNAMIC_TYPE_STRING  ('price_str' => 'price', 'title' => 'title')
	 *
	 * @param string $field_name_with_str_ending Field name (like 'price_str')
	 *
	 * @return string
	 */
	public static function get_field_without_str_ending( $field_name_with_str_ending ) {

		$result = WPSOLR_Regexp::remove_string_at_the_end( $field_name_with_str_ending, self::_SOLR_DYNAMIC_TYPE_STRING );

		return $result;
	}

	/**
	 * @param \WP_Post $post
	 * @param string $field_name
	 * @param $value
	 * @param string $field_type
	 *
	 * @throws Exception
	 */
	public static
	function throw_sanitized_error(
		$post, $field_name, $value, $field_type
	) {

		throw new \Exception(
			sprintf(
				self::ERROR_SANITIZED_MESSAGE,
				$value,
				self::get_field_without_str_ending( $field_name ),
				empty( $post ) ? 'unknown' : $post->ID,
				empty( $post ) ? 'unknown' : $post->post_title,
				self::get_solr_dynamic_entension_id_label( $field_type )
			)
		);

	}

	/**
	 * Sanitize a float value
	 * Try to convert it to a float, else throw an exception.
	 *
	 * @param WP_Post $post
	 * @param string $field_name
	 * @param string $value
	 * @param string $field_type
	 *
	 * @return float
	 */
	public static
	function get_sanitized_float_value(
		$post, $field_name, $value, $field_type
	) {

		if ( empty( $value ) ) {
			return $value;
		}

		if ( ! is_numeric( $value ) ) {
			self::throw_sanitized_error( $post, $field_name, $value, $field_type );
		}

		if ( ! is_int( 0 + $value ) && ! is_float( 0 + $value ) ) {
			self::throw_sanitized_error( $post, $field_name, $value, $field_type );
		}

		return floatval( $value );
	}

	/**
	 * Sanitize a date value
	 * Try to convert it to a date, else throw an exception.
	 *
	 * @param WP_Post $post
	 * @param string $field_name
	 * @param string $value
	 * @param string $field_type
	 *
	 * @return float
	 */
	public static
	function get_sanitized_date_value(
		$post, $field_name, $value, $field_type
	) {

		if ( empty( $value ) ) {
			return $value;
		}

		// Try to format date to Solr date format
		if ( ! self::$helper ) {
			self::$helper = new \Solarium\Core\Query\Helper();
		}
		$result = self::$helper->formatDate( $value );

		if ( false === $result ) {
			self::throw_sanitized_error( $post, $field_name, $value, $field_type );
		}

		return $result;

	}

	/**
	 * Sanitize an integer value
	 * Try to convert it to an integer, else throw an exception.
	 *
	 * @param WP_Post $post
	 * @param string $field_name
	 * @param string $value
	 * @param string $field_type
	 *
	 * @return int
	 */
	public static
	function get_sanitized_integer_value(
		$post, $field_name, $value, $field_type
	) {

		if ( empty( $value ) ) {
			return $value;
		}

		if ( ! is_numeric( $value ) ) {
			self::throw_sanitized_error( $post, $field_name, $value, $field_type );
		}

		if ( ! is_int( 0 + $value ) ) {
			self::throw_sanitized_error( $post, $field_name, $value, $field_type );
		}

		return intval( $value );
	}

	/**
	 * Get custom field error conversion action
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return string
	 */
	public static function get_custom_field_error_conversion_action( $field_name ) {

		// Get the properties of this field
		$custom_field_properties = self::get_custom_field_properties( $field_name );

		$result = ( ! empty( $custom_field_properties[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ] )
			? $custom_field_properties[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ]
			: WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD );

		return $result;
	}

	/**
	 * Sanitize any value, based on it's Solr extension type
	 *
	 * @param WP_Post $post
	 * @param string $field_name
	 * @param string $value
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function get_sanitized_value( $post, $field_name, $value ) {

		$field_type = WpSolrSchema::get_custom_field_dynamic_type( $field_name );

		try {

			// Let a chance to sanitize the field
			$result = apply_filters( WpSolrFilters::WPSOLR_FILTER_INDEX_SANITIZE_FIELD, null,
				$post, $field_name, $value, $field_type
			);

			if ( null === $result ) {
				// Field not sanitized yet: do it now.

				switch ( $field_type ) {

					case WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE:
						$result = WpSolrSchema::get_sanitized_date_value( $post, $field_name, $value, $field_type );
						break;

					case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT:
						$result = WpSolrSchema::get_sanitized_float_value( $post, $field_name, $value, $field_type );
						break;

					case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER:
						$result = WpSolrSchema::get_sanitized_integer_value( $post, $field_name, $value, $field_type );
						break;

					default:
						$result = is_array( $value )
							? array_map( function ( $val ) {
								strip_tags( $val );
							}, $value )
							: strip_tags( $value );
						break;
				}
			}
		} catch ( Exception $e ) {

			$result                        = '';
			$field_error_conversion_action = WpSolrSchema::get_custom_field_error_conversion_action( $field_name );

			if ( WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_THROW_ERROR === $field_error_conversion_action ) {
				// Throw error if this field is configured to do that.
				throw $e;
			}
		}

		return $result;

	}
}
