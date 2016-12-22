<?php
/**
 * Functions related to core back-compatibility.
 *
 * @class  WC_CSP_Core_Compatibility
 * @since  1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CSP_Core_Compatibility {

	public static function init() {
		if ( is_admin() ) {
			add_filter( 'woocommerce_enable_deprecated_additional_flat_rates', __CLASS__ . '::enable_deprecated_addon_flat_rates' );
		}
	}

	/**
	 * Clears cached shipping rates.
	 *
	 * @return void
	 */
	public static function clear_cached_shipping_rates() {
		global $wpdb;

		// WC 2.2 - WC 2.4: Rates cached as transients.
		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('\_transient\_wc\_ship\_%') OR `option_name` LIKE ('\_transient\_timeout\_wc\_ship\_%')" );

		// WC 2.5: Rates cached in session.
		if ( self::is_wc_version_gte_2_5() ) {
			// Increments the shipping transient version to invalidate session entries.
			WC_Cache_Helper::get_transient_version( 'shipping', true );
		}
	}

	/**
	 * Enable deprecated Add-on flat rate options panel
	 *
	 * @param  boolean $enable
	 * @return boolean
	 */
	public static function enable_deprecated_addon_flat_rates( $enable ) {
		return true;
	}

	/**
	 * Get the WC Product instance for a given product ID or post
	 *
	 * get_product() is soft-deprecated in WC 2.2
	 *
	 * @since 1.0.4
	 * @param bool|int|string|\WP_Post $the_product
	 * @param array $args
	 * @return WC_Product
	 */
	public static function wc_get_product( $the_product = false, $args = array() ) {

		if ( self::is_wc_version_gte_2_2() ) {

			return wc_get_product( $the_product, $args );

		} else {

			return get_product( $the_product, $args );
		}
	}

	/**
	 * Helper method to get the version of the currently installed WooCommerce
	 *
	 * @since 1.0.4
	 * @return string woocommerce version number or null
	 */
	private static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.2 or greater
	 *
	 * @since 1.1.11
	 * @return boolean true if the installed version of WooCommerce is 2.2 or greater
	 */
	public static function is_wc_version_gte_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.2 or greater
	 *
	 * @since 1.0.4
	 * @return boolean true if the installed version of WooCommerce is 2.2 or greater
	 */
	public static function is_wc_version_gte_2_3() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.3', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.2 or greater
	 *
	 * @since 1.0.4
	 * @return boolean true if the installed version of WooCommerce is 2.2 or greater
	 */
	public static function is_wc_version_gte_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is less than 2.2
	 *
	 * @since 1.0.4
	 * @return boolean true if the installed version of WooCommerce is less than 2.2
	 */
	public static function is_wc_version_lt_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '<' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version
	 *
	 * @since 1.0.4
	 * @param string $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
	}
}

WC_CSP_Core_Compatibility::init();
