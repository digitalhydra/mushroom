<?php

/**
 * Class PluginAcf
 *
 * Manage Advanced Custom Fields (ACF) plugin
 * @link https://wordpress.org/plugins/advanced-custom-fields/
 */
class PluginAcf extends WpSolrExtensions {

	// Prefix of ACF fields
	const FIELD_PREFIX = '_';

	// Polylang options
	const _OPTIONS_NAME = 'wdm_solr_extension_acf_data';

	// acf fields indexed by name.
	private $_fields;

	// Options
	private $_options;

	// ACF types
	const ACF_TYPE_REPEATER = 'repeater';
	const ACF_TYPE_FILE = 'file';
	const ACF_TYPE_FILE_ID = 'id';
	const ACF_TYPE_FILE_OBJECT = 'array';
	const ACF_TYPE_FILE_URL = 'url';
	const ACF_TYPE_FLEXIBLE_CONTENT = 'flexible_content';
	const ACF_TYPE_GOOGLE_MAP = 'google_map';

	/**
	 * Factory
	 *
	 * @return PluginAcf
	 */
	static function create() {

		return new self();
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	/**
	 * PluginAcf constructor.
	 */
	function __construct() {

		$this->_options = self::get_option_data( self::EXTENSION_ACF );

		add_filter( WpSolrFilters::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, array(
			$this,
			'get_index_custom_fields',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME, array(
			$this,
			'get_field_label',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_POST_CUSTOM_FIELDS, array(
			$this,
			'filter_custom_fields',
		), 10, 2 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_GET_POST_ATTACHMENTS, array(
			$this,
			'filter_get_post_attachments',
		), 10, 2 );


		if ( is_admin() ) {
			add_action( 'acf/init', array(
				$this,
				'acf_google_map_init_pro',
			), 10 );
		}

	}

	/**
	 * Retrieve all field keys of all ACF fields.
	 *
	 * @return array
	 */
	function get_acf_fields() {
		global $wpdb;

		// Uue cached fields if exist
		if ( isset( $this->_fields ) ) {
			return $this->_fields;
		}

		$fields = array();

		// Else create the cached fields
		$results = $wpdb->get_results( "SELECT distinct meta_key, meta_value
                                        FROM $wpdb->postmeta
                                        WHERE meta_key like '_%'
                                        AND   meta_value like 'field_%'" );

		$nb_results = count( $results );
		for ( $loop = 0; $loop < $nb_results; $loop ++ ) {
			$fields[ $results[ $loop ]->meta_key ] = $results[ $loop ]->meta_value;

		}

		// Save the cache
		$this->_fields = $fields;

		return $this->_fields;
	}


	/**
	 * Update custom fields list to be indexed
	 * Replace _groupRepeater_0_repeatedFieldName by repeatedFieldName
	 *
	 * @param string[] $custom_fields
	 *
	 * @return string[]
	 */
	function get_index_custom_fields( $custom_fields ) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = array();
		}

		$fields = $this->get_acf_fields();

		$results = array();
		foreach ( $custom_fields as $custom_field_name ) {

			$do_not_include = false;

			if ( isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) || isset( $fields[ $custom_field_name ] ) ) {

				$field_key = isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) ? $fields[ self::FIELD_PREFIX . $custom_field_name ] : $fields[ $custom_field_name ];
				$field     = get_field_object( $field_key, false, false, false );

				if ( $field ) {

					switch ( $field['type'] ) {

						case self::ACF_TYPE_REPEATER:
						case self::ACF_TYPE_FLEXIBLE_CONTENT:
							// This field is a container: do not use it as custom field.
							$do_not_include = true;
							break;

						case self::ACF_TYPE_FILE:
							// This field is a file: do not use it as custom field. Used in attachments.
							$do_not_include = true;
							break;

						default:

							/**
							 * Get the canonical form of a repeated field name, eventually.
							 * Examples:
							 * _xxxxx_0_field => field
							 * __xxxxx_0__field => field
							 * xxxxx_0_field => field
							 * _xxxxx_10_field => field
							 * _xxxxx_yy_field => _xxxxx_yy_field
							 * xxxxx_yy_field => xxxxx_yy_field
							 * field => field
							 */
							$repeated_field_name = preg_replace( '/(.*)_(\d*)_(.*)/', '$3', $custom_field_name );

							if ( ! in_array( $repeated_field_name, $results, true ) ) {
								// Add the non repeated field name, or the repeated field canonical name.
								array_push( $results, $repeated_field_name );
							}

							$do_not_include = true;
							break;
					}
				}
			}

			if ( ! $do_not_include && ! in_array( $custom_field_name, $results, true ) ) {
				// Add the non repeated field name, or the non ACF field name.
				array_push( $results, $custom_field_name );
			}

		}

		return $results;
	}

	/**
	 * Get the ACF field label from the custom field name.
	 *
	 * @param $custom_field_name
	 *
	 * @return mixed
	 */
	public
	function get_field_label(
		$custom_field_name
	) {

		$result = $custom_field_name;

		if ( ! isset( $this->_options['display_acf_label_on_facet'] ) ) {
			// No need to replace custom field name by acf field label
			return $result;
		}

		// Retrieve field among ACF fields
		$fields = $this->get_acf_fields();
		if ( isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) ) {
			$field_key = $fields[ self::FIELD_PREFIX . $custom_field_name ];
			$field     = get_field_object( $field_key );
			$result    = isset( $field['label'] ) ? $field['label'] : $custom_field_name;
		}

		return $result;
	}


	/**
	 * Decode acf values before indexing.
	 * Get all field values, recursively in containers if necessary, which are not containers, and not files.
	 * Files are treated in attachments code.
	 *
	 * @param $custom_fields
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public
	function filter_custom_fields(
		$custom_fields, $post_id
	) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = array();
		}

		// Get post ACF field objects
		$fields_set = array();
		$this->get_fields_all_levels(
			$fields_set,
			get_field_objects( $post_id ),
			array(), // We want All files
			array(
				self::ACF_TYPE_FILE, // But we don't want files. They are dealt with attachments.
			)
		);


		if ( $fields_set ) {

			$is_first = array();

			foreach ( $fields_set as $field_name => $fields ) {

				foreach ( $fields as $field ) {

					if ( ! empty( $field['value'] ) ) {

						switch ( $field['type'] ) {
							case self::ACF_TYPE_GOOGLE_MAP:
								/*
								array (
									'address' => 'some adress',
									'lat' => '48.631077',
									'lng' => '-10.1482240000000274',
								)*/
								// Convert to a lat,long format
								if ( ! empty( $field['value']['lat'] ) && ! empty( $field['value']['lng'] ) ) {
									$custom_fields[ $field['name'] ] = sprintf( OptionGeoLocation::FORMAT_LAT_LONG, $field['value']['lat'], $field['value']['lng'] );
								}

								break;

							default:
								// Same treatments for all other types.


								if ( ! isset( $is_first[ $field['name'] ] ) ) {
									unset( $custom_fields[ $field['name'] ] );
								}

								foreach ( is_array( $field['value'] ) ? $field['value'] : array( $field['value'] ) as $field_value ) {
									$custom_fields[ $field['name'] ][] = $field_value;
								}

								$is_first[ $field['name'] ] = false;

								break;
						}
					}
				}
			}
		}

		return $custom_fields;
	}

	/**
	 * Retrieve attachments in the fields of type file of the post
	 *
	 * @param array $attachments
	 * @param string $post
	 *
	 */
	public
	function filter_get_post_attachments(
		$attachments, $post_id
	) {

		if ( ! WPSOLR_Metabox::get_metabox_is_do_index_acf_field_files( $post_id ) ) {
			// Do nothing
			return $attachments;
		}

		// Get post ACF field objects
		$fields_set = array();
		$this->get_fields_all_levels(
			$fields_set,
			get_field_objects( $post_id ),
			array(
				self::ACF_TYPE_FILE,
			),
			array()
		);

		if ( $fields_set ) {

			foreach ( $fields_set as $field_name => $fields ) {

				foreach ( $fields as $field ) {

					// Retrieve the post_id of the file
					if ( ! empty( $field['value'] ) && ( self::ACF_TYPE_FILE === $field['type'] ) ) {


						switch ( $field['return_format'] ) {
							case self::ACF_TYPE_FILE_ID:
								array_push( $attachments, array( 'post_id' => $field['value'] ) );
								break;

							case self::ACF_TYPE_FILE_OBJECT:
								array_push( $attachments, array( 'post_id' => $field['value']['id'] ) );
								break;

							case self::ACF_TYPE_FILE_URL:
								array_push( $attachments, array( 'url' => $field['value'] ) );
								break;

							default:
								// Do nothing
								break;
						}
					}
				}
			}
		}

		return $attachments;
	}


	/**
	 * Get subfields of fields recursively
	 *
	 * @return mixed
	 */
	public
	function get_fields_all_levels(
		&$all_fields, $fields, $field_types, $excluded_field_types
	) {

		if ( empty( $fields ) ) {
			// Nothing to do.
			return;
		}

		foreach ( $fields as $field_name => $field ) {

			if ( ! empty( $field['value'] ) ) {

				switch ( $field['type'] ) {

					case self::ACF_TYPE_FLEXIBLE_CONTENT:

						// Extract sub_fields of each layout, then proceed on sub_fields
						$field['sub_fields'] = array();
						foreach ( $field['layouts'] as $layout ) {
							foreach ( $layout['sub_fields'] as $sub_field ) {
								$field['sub_fields'][] = $sub_field;
							}
						}

					// No break here!!!
					//break;

					case self::ACF_TYPE_REPEATER:
						foreach ( $field['sub_fields'] as $sub_field ) {

							// Copy sub_field value(s)
							foreach ( $field['value'] as $value ) {

								if ( ! empty( $value[ $sub_field['name'] ] ) ) {
									$sub_field['value'] = $value[ $sub_field['name'] ];

									$this->get_fields_all_levels( $all_fields, array( $sub_field['name'] => $sub_field ), $field_types, $excluded_field_types );
								}
							}
						}
						break;


					default:
						// This is a non-recursive type, with value(s). Add it to results.
						if (
							( empty( $field_types ) || in_array( $field['type'], $field_types, true ) ) // Field type is in included types
							&& ( empty( $excluded_field_types ) || ! in_array( $field['type'], $excluded_field_types, true ) ) // And field type is not in excluded types
						) {
							$all_fields[ $field['name'] ][] = $field;
						}
						break;
				}
			}
		}
	}

	/**
	 * Initialize ACF google map api for ACF PRO, if not already set by ACF before.
	 *
	 */
	function acf_google_map_init_pro() {

		$acf_api_key = acf_get_setting( 'google_api_key' );
		if ( empty( $acf_api_key ) ) {

			$wpsolr_api_key = WPSOLR_Global::getOption()->get_plugin_acf_google_map_api_key();

			if ( ! empty( $wpsolr_api_key ) ) {
				acf_update_setting( 'google_api_key', $wpsolr_api_key );
			}
		}
	}

}