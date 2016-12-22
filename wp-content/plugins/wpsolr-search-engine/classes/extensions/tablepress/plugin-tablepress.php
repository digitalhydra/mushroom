<?php

/**
 * Class PluginTablePress
 *
 * Manage TablePress
 * @link https://wordpress.org/plugins/tablepress/
 */
class PluginTablePress extends WpSolrExtensions {


	/**
	 * Constructor
	 * Subscribe to actions
	 **/
	function __construct() {

		if ( is_admin() && WPSOLR_Global::getOption()->get_tablepress_is_index_shortcodes() ) {
			// This extension must only execute in admin, and only when authorized.

			add_filter( 'tablepress_table_output', array(
				$this,
				'tablepress_table_output',
			), 10, 3 );

			// Ensure do_shortcode executes also in admin when called by wpsolr indexing.
			add_action( 'tablepress_run', function () {
				TablePress::$model_options                 = TablePress::load_model( 'options' );
				TablePress::$model_table                   = TablePress::load_model( 'table' );
				$GLOBALS['tablepress_frontend_controller'] = TablePress::load_controller( 'frontend' );

			} );
		}

	}


	/**
	 * Replace the html generated code by a flatten string, to prevent stripped html problems
	 * (like columns content being glued in a single content without whitespaces).
	 *
	 * @param string $output The generated HTML for the table.
	 * @param array $table The current table.
	 * @param array $render_options The render options for the table.
	 */
	public function tablepress_table_output( $output, $table, $render_options ) {

		if ( empty( $render_options['id'] ) ) {
			// Comes from the admin TableSpace edit render preview: do nothing.
			return $output;
		}

		// Comes from do_shortcode() during wpsolr indexing: flatten data rather than use html output.
		$result = $this->implode_recursive( WPSolrIndexSolrClient::CONTENT_SEPARATOR, $table['data'] );

		return $result;
	}


	/**
	 * Flatten recursive arrays.
	 *
	 * @param $separator
	 * @param $arrayvar
	 *
	 * @return string
	 */
	public function implode_recursive( $separator, $arrayvar ) {

		$result = '';

		foreach ( $arrayvar as $av ) {

			if ( ! empty( $av ) ) {

				if ( is_array( $av ) ) {

					$result .= $this->implode_recursive( $separator, $av ); // Recursive Use of the Array

				} else {

					$result .= $separator . $av;
				}

			}

		}

		return trim( $result );
	}

}