<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


return apply_filters( 'yith_wcas_search_options', array(
    'search' => array(

        'search_option_section'         => array(
            'name'              => __( 'Search settings', 'yit' ),
            'type'              => 'title',
            'id'                => 'yith_wcas_search_options'
        ),

        //from 1.2.7
        'default_research' => array(
            'name'    => __( 'Choose element types to search', 'yit' ),
            'desc'    => __( 'Choose if you want to extend search also to posts and pages', 'yit'),
            'id'      => 'yith_wcas_default_research',
            'class'   => 'yith-wcas-chosen',
            'default' => 'product',
            'type'    => 'select',
            'options' => array(
                'any'     => __( 'All', 'yit' ),
                'product' => __( 'Products', 'yit' ),
            ),
        ),

        'search_in_excerpt' => array(
            'name'    => __( 'Search in excerpt', 'yit' ),
            'desc'    => __( 'Extend search in the excerpt of the product','yit' ),
            'id'      => 'yith_wcas_search_in_excerpt',
            'default' => 'yes',
            'type'    => 'checkbox'
        ),


        'search_in_content' => array(
            'name'    => __( 'Search in content', 'yit' ),
            'desc'    => __( 'Extend search in the content of the product','yit' ),
            'id'      => 'yith_wcas_search_in_content',
            'default' => 'yes',
            'type'    => 'checkbox'
        ),

        'search_in_product_categories' => array(
            'name'    => __( 'Search in product categories', 'yit' ),
            'desc'    => __( 'Extend search in product categories','yit' ),
            'id'      => 'yith_wcas_search_in_product_categories',
            'default' => 'yes',
            'type'    => 'checkbox'
        ),

        'search_in_product_tags' => array(
            'name'    => __( 'Search in product tags', 'yit' ),
            'desc'    => __( 'Extend search in product tags','yit' ),
            'id'      => 'yith_wcas_search_in_product_tags',
            'default' => 'yes',
            'type'    => 'checkbox'
        ),


        'search_type_more_words' => array(
            'name'    => __( 'Multiple Word Search', 'yit' ),
            'desc'    => '',
            'id'      => 'yith_wcas_search_type_more_words',
            'default' => 'or',
            'type'    => 'select',
            'options' => array(
                'and'  => __( 'Show items containing all words typed', 'yit' ),
                'or' => __( 'Show items containing al least one of the words typed', 'yit' ),
            ),
        ),


        'search_option_section_end' => array(
            'type' => 'sectionend',
            'id'   => 'yith_wcas_search_options_end'
        ),

        //from 1.3.2
        'search_by_cf_option_section'         => array(
            'name'              => __( 'Search by Custom Field', 'yit' ),
            'desc'    => __( 'Extend search functionality so that search in a custom field. Write custom field\'s name comma separated. Attention: this feature may slow down the search process on some servers.','yit' ),
            'type'              => 'title',
            'id'                => 'yith_wcas_search_by_custom_field'
        ),

        'cf_name'               => array(
            'name'    => __( 'Custom field name', 'yit' ),
            'type'    => 'text',
            'desc'    => '',
            'id'      => 'yith_wcas_cf_name',
            'default' => '',
        ),

        //from 1.3.2
        'search_by_cf_option_section_end' => array(
            'type' => 'sectionend',
            'id'   => 'yith_wcas_search_by_custom_field_end'
        ),

        //from 1.2.3
        'search_by_sku_option_section'         => array(
            'name'              => __( 'Search by Sku Settings', 'yit' ),
            'desc'    => __( 'Extend search functionality so that search includes also sku. Attention: this feature may slow down the search process on some servers.','yit' ),
            'type'              => 'title',
            'id'                => 'yith_wcas_search_by_sku_options'
        ),


        'search_by_sku' => array(
            'name'    => __( 'Search by sku', 'yit' ),
            'desc'    => __( 'Extend search functionality so that search includes also sku','yit' ),
            'id'      => 'yith_wcas_search_by_sku',
            'default' => 'no',
            'type'    => 'checkbox'
        ),

        //from 1.2.3
        'search_by_sku_variations' => array(
            'name'    => __( 'Search by sku variable products', 'yit' ),
            'desc'    => __( 'Extend sku search including variable products.','yit' ),
            'id'      => 'yith_wcas_search_by_sku_variations',
            'default' => 'no',
            'type'    => 'checkbox'
        ),


        //from 1.2.3
        'search_by_sku_option_section_end' => array(
            'type' => 'sectionend',
            'id'   => 'yith_wcas_search_by_sku_options_end'
        ),



    )
) );