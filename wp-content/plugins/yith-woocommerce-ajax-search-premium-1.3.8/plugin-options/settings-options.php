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


return array(

	'settings' => array(

        'section_general_settings'         => array(
            'name' => __( 'General settings', 'yit' ),
            'type' => 'title',
            'id'   => 'yith_wcas_general'
        ),

        'search_input_label'               => array(
            'name'    => __( 'Search input label', 'yit' ),
            'type'    => 'text',
            'desc'    => __( 'Label for Search input field.', 'yit' ),
            'id'      => 'yith_wcas_search_input_label',
            'default' => __( 'Search for products', 'yit' ),
        ),

        'search_submit_label'              => array(
            'name'    => __( 'Search submit label', 'yit' ),
            'type'    => 'text',
            'desc'    => __( 'Label for Search input field.', 'yit' ),
            'id'      => 'yith_wcas_search_submit_label',
            'default' => __( 'Search', 'yit' )
        ),

        'trigger_min_chars'                => array(
            'name'              => __( 'Minimum number of characters', 'yit' ),
            'desc'              => __( 'Minimum number of characters required to trigger autosuggest.', 'yit' ),
            'id'                => 'yith_wcas_min_chars',
            'default'           => '3',
            'css'               => 'width:50px;',
            'type'              => 'number',
            'custom_attributes' => array(
                'min'  => 1,
                'max'  => 100,
                'step' => 1
            )
        ),

        'trigger_max_result_num'           => array(
            'name'              => __( 'Maximum number of results', 'yit' ),
            'desc'              => __( 'Maximum number of results showed in autosuggest box.', 'yit' ),
            'id'                => 'yith_wcas_posts_per_page',
            'default'           => '3',
            'css'               => 'width:50px;',
            'type'              => 'number',
            'custom_attributes' => array(
                'min'  => 1,
                'max'  => 15,
                'step' => 1
            )
        ),

        'section_ajax_search_settings_end' => array(
            'type' => 'sectionend',
            'id'   => 'yith_wcas_general_end'
        ),

        //from 1.2.3
        'section_additional_features'         => array(
            'name' => __( 'Additional Features', 'yit' ),
            'desc' => __( 'If these options are not showed, your theme may not support these features. Please, contact the developer of theme to implement them.', 'yit'),
            'type' => 'title',
            'id'   => 'yith_wcas_additional_features'
        ),

        //from 1.2.3
        'show_search_list' => array(
            'name'    => __( 'Show filter for search fields', 'yit' ),
            'desc'    => __( 'Show filter for search fields (it allows searching the Whole site or only among products)', 'yit' ),
            'id'      => 'yith_wcas_show_search_list',
            'default' => 'no',
            'type'    => 'checkbox'
        ),

        //from 1.2.3
        'show_category_list' => array(
            'name'    => __( 'Show the category list', 'yit' ),
            'desc'    => '',
            'id'      => 'yith_wcas_show_category_list',
            'default' => 'no',
            'type'    => 'checkbox'
        ),

        //from 1.2.3
        'show_category_list_all' => array(
            'title'           => __( 'Categories to show', 'yit' ),
            'desc'            => __( 'This option lets you decide to show all the categories or only the main ones', 'yit' ),
            'id'              => 'yith_wcas_show_category_list_all',
            'class'     => 'yith-wcas-chosen',
            'default'  => 'main',
            'type'     => 'select',
            'options'  => array(
                'main'  => __( 'Main Categories', 'yit' ),
                'all'  => __( 'All Categories', 'yit' ),
            ),
        ),


        'section_additional_features_end' => array(
            'type' => 'sectionend',
            'id'   => 'yith_wcas_additional_features_end'
        ),


	),
);