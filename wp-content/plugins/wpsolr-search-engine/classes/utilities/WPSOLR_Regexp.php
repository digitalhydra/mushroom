<?php


/**
 * Common Regexp expressions used in WPSOLR.
 *
 * Class WPSOLR_Regexp
 */
class WPSOLR_Regexp {

	/**
	 * Extract values from a range query parameter
	 * '[5 TO 30]' => ['5', '30']
	 *
	 * @param $text
	 *
	 * @return string
	 */
	static function extract_filter_range_values( $text ) {

		// Replace separator literals by a single special character. Much easier, because negate a literal is difficult with regexp.
		$text = str_replace( array( ' TO ', '[', ']' ), ' | ', $text );

		// Negate all special caracters to get the 'field:value' array
		preg_match_all( '/[^|\s]+/', $text, $matches );

		// Trim results
		$results_with_some_empty_key = ! empty( $matches[0] ) ? array_map( 'trim', $matches[0] ) : array();

		// Remove empty array rows (it happens), prevent duplicates.
		$results = array();
		foreach ( $results_with_some_empty_key as $result ) {
			if ( ! empty( $result ) ) {
				array_push( $results, $result );
			}
		}

		return $results;
	}

	/**
	 * Extract last occurence of a separator
	 * 'field1' => ''
	 * 'field1_asc' => 'asc'
	 * 'field1_notme_asc' => 'asc'
	 *
	 * @param $text
	 * @param $text_to_find
	 *
	 * @return string
	 */
	static function extract_last_separator( $text, $separator ) {

		preg_match( sprintf( '/[_]+[^_]*$/', $separator ), $text, $matches );

		return ! empty( $matches ) ? substr( $matches[0], strlen( $separator ) ) : $text;
	}

	/**
	 * Remove $text_to_remove at the end of $text
	 *
	 * @param $text
	 * @param $text_to_remove
	 *
	 * @return string
	 */
	static function remove_string_at_the_end( $text, $text_to_remove ) {

		return preg_replace( sprintf( '/%s$/', $text_to_remove ), '', $text );
	}

	/**
	 * Remove $text_to_remove at the beginning of $text
	 *
	 * @param $text
	 * @param $text_to_remove
	 *
	 * @return string
	 */
	static function remove_string_at_the_begining( $text, $text_to_remove ) {

		return preg_replace( sprintf( '/^%s/', $text_to_remove ), '', $text );
	}

}