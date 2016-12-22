<?php
/**
 * Functions
 *
 * @author Yithemes
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( !defined( 'YITH_WCAS' ) ) { exit; } // Exit if accessed directly

if ( !function_exists( 'yit_get_shop_categories' ) ) {
    function yith_wcas_get_shop_categories( $show_all = true ) {
        global $wpdb;

        if ( $show_all ){
            $terms = $wpdb->get_results( 'SELECT name, slug FROM ' . $wpdb->prefix . 'terms, ' . $wpdb->prefix . 'term_taxonomy WHERE ' . $wpdb->prefix . 'terms.term_id = ' . $wpdb->prefix . 'term_taxonomy.term_id AND taxonomy = "product_cat" ORDER BY name ASC;' );
        }else{
            $args = apply_filters( 'yith_wcas_form_cat_args', array(
                'order'	        => 'ASC',
                'parent'        => 0,
                'hide_empty'	=> 1,
                'hierarchical'	=> 0,
            ) );

            $terms = get_terms( 'product_cat', $args );
        }

        return $terms;
    }
}
