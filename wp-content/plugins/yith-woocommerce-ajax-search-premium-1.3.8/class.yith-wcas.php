<?php
/**
 * Main class
 *
 * @author Yithemes
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( !defined( 'YITH_WCAS' ) ) {
    exit;
} // Exit if accessed directly

if ( !class_exists( 'YITH_WCAS' ) ) {
    /**
     * WooCommerce Ajax Search
     *
     * @since 1.0.0
     */
    class YITH_WCAS {
        /**
         * Plugin version
         *
         * @var string
         * @since 1.0.0
         */
        public $version = YITH_WCAS_VERSION;

        /**
         * Plugin object
         *
         * @var string
         * @since 1.0.0
         */
        public $obj = null;

        private $search_string = '';
        private $search_order  = '';
        private $post_type     = 'any';

        private $search_options = array();

        /**
         * Constructor
         *
         * @return mixed|YITH_WCAS_Admin|YITH_WCAS_Frontend
         * @since 1.0.0
         */
        public function __construct() {

            // Load Plugin Framework
            add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );

            // actions
            add_action( 'init', array( $this, 'init' ) );
            add_action( 'widgets_init', array( $this, 'registerWidgets' ) );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
            add_action( 'wp', array( $this, 'remove_pre_get_posts' ) );

            add_action( 'wp_ajax_yith_ajax_search_products', array( $this, 'ajax_search_products' ) );
            add_action( 'wp_ajax_nopriv_yith_ajax_search_products', array( $this, 'ajax_search_products' ) );

            // YITH WooCommerce Brands Compatibility
            add_filter( 'yith_wcas_search_options', array( $this, 'add_brands_search_option' ) );
            add_filter( 'yith_wcas_search_params', array( $this, 'add_brands_search_params' ) );

            //register shortcode
            add_shortcode( 'yith_woocommerce_ajax_search', array( $this, 'add_woo_ajax_search_shortcode' ) );

            if ( is_admin() ) {
                $this->obj = new YITH_WCAS_Admin( $this->version );

            }else {
                $this->obj = new YITH_WCAS_Frontend( $this->version );
            }

            return $this->obj;
        }


        /**
         * Init method:
         *  - default options
         *
         * @access public
         * @since  1.0.0
         */
        public function init() {
            //fill the options
            global $woocommerce;

            $ordering_args = $woocommerce->query->get_catalog_ordering_args( 'title', 'asc' );
            $search_by_cf  = get_option( 'yith_wcas_cf_name' );
            if( $search_by_cf != ''){
                $search_by_cf  = array_map('trim',explode(",",$search_by_cf));
            }

            $this->search_options = apply_filters( 'yith_wcas_search_params', array(
                'search_by_excerpt'        => apply_filters( 'yith_wcas_search_in_excerpt', get_option( 'yith_wcas_search_in_excerpt' ) ),
                'search_by_content'        => apply_filters( 'yith_wcas_search_in_content', get_option( 'yith_wcas_search_in_content' ) ),
                'search_by_cat'            => apply_filters( 'yith_wcas_search_in_product_categories', get_option( 'yith_wcas_search_in_product_categories' ) ),
                'search_by_tag'            => apply_filters( 'yith_wcas_search_in_product_tags', get_option( 'yith_wcas_search_in_product_tags' ) ),
                'search_by_sku'            => apply_filters( 'yith_wcas_search_by_sku', get_option( 'yith_wcas_search_by_sku' ) ),
                'search_by_sku_variations' => apply_filters( 'yith_wcas_search_by_sku_variations', get_option( 'yith_wcas_search_by_sku_variations' ) ),
                'search_by_cf'             => ( is_array($search_by_cf)) ? implode("','",$search_by_cf) : '',
                'posts_per_page'           => apply_filters( 'yith_wcas_search_posts_per_page', get_option( 'yith_wcas_posts_per_page' ) ),
                'orderby'                  => apply_filters( 'yith_wcas_search_orderby', $ordering_args['orderby'] ),
                'order'                    => apply_filters( 'yith_wcas_search_orderby', $ordering_args['order'] ),
            ) );
        }

        /**
         * Load Plugin Framework
         *
         * @since  1.0
         * @access public
         * @return void
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function plugin_fw_loader() {
            if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
                global $plugin_fw_data;
                if( ! empty( $plugin_fw_data ) ){
                    $plugin_fw_file = array_shift( $plugin_fw_data );
                    require_once( $plugin_fw_file );
                }
            }
        }

        /**
         * Load template for [yith_woocommerce_ajax_search] shortcode
         *
         * @access public
         *
         * @param $args array
         *
         * @return void
         * @since  1.0.0
         */
        public function add_woo_ajax_search_shortcode( $args = array() ) {
            $args            = shortcode_atts( array(), $args );
            ob_start();
            $wc_get_template = function_exists( 'wc_get_template' ) ? 'wc_get_template' : 'woocommerce_get_template';
            $wc_get_template( 'yith-woocommerce-ajax-search.php', $args, '', YITH_WCAS_DIR . 'templates/' );
            return ob_get_clean();
        }

        /**
         * Load and register widgets
         *
         * @access public
         * @since  1.0.0
         */
        public function registerWidgets() {
            register_widget( 'YITH_WCAS_Ajax_Search_Widget' );
        }


        function extend_search_join( $join ) {
            global $wpdb;

            if ( $this->post_type == 'product' ) {
                $join .= " INNER JOIN {$wpdb->postmeta} as ywmk ON ( {$wpdb->posts}.ID = ywmk.post_id ) ";
            }
	        // YITH WooCommerce Brands Compatibility
	        $search_by_brand = isset( $this->search_options['search_by_brand'] ) && $this->search_options['search_by_brand'] == 'yes';

            if ( $this->search_options['search_by_cat'] == 'yes' || $this->search_options['search_by_tag'] == 'yes' || $search_by_brand ) {
                $join .= " LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id LEFT JOIN {$wpdb->terms} tm ON tm.term_id = tt.term_id";
            }

            if( $this->search_options['search_by_cf'] != ''){
                $join .= " LEFT JOIN {$wpdb->postmeta} as cf1 ON ( {$wpdb->posts}.ID = cf1.post_id AND cf1.meta_key IN ('{$this->search_options['search_by_cf']}' ) ) ";
            }

            return $join;
        }

        function extend_search_where( $where = '', $post_like = true ) {
            global $wpdb;

            $terms = array();

            if($this->post_type == 'product' ){
                $where .= " AND  ( ywmk.meta_key = '_visibility' AND CAST( ywmk.meta_value AS CHAR) IN ('search','visible') ) ";
            }

            if ( $this->search_options['search_by_cat'] == 'yes' ) {
                if($this->post_type == 'product' ){
                    $terms[] = 'product_cat';
                }else{
                    $terms[] = 'category';
                    $terms[] = 'product_cat';
                }
            }

            if ( $this->search_options['search_by_tag'] == 'yes' ) {
                if($this->post_type == 'product' ){
                    $terms[] = 'product_tag';
                }else{
                    $terms[] = 'product_tag';
                    $terms[] = 'post_tag';
                }
            }

	        // YITH WooCommerce Brands Compatibility
	        if ( isset( $this->search_options['search_by_brand'] ) && $this->search_options['search_by_brand'] == 'yes' && $this->post_type == 'product' ) {
				if( ! in_array( YITH_WCBR::$brands_taxonomy, $terms ) ){
					$terms[] = YITH_WCBR::$brands_taxonomy;
				}
	        }

            $terms = apply_filters('yith_wcas_search_taxonomy_terms', $terms);

            if (  ( $post_like ) || !empty( $terms ) || $this->search_options['search_by_sku'] == 'yes'  ) {
                $where .= " AND (";
                $addor = false;
                if ( $post_like ) {

                    $where .= " ({$wpdb->posts}.post_title REGEXP '" . $this->search_string . "') ";

                    if ( $this->search_options['search_by_excerpt'] == 'yes' ) {
                        $where .= " OR ({$wpdb->posts}.post_excerpt REGEXP '" . $this->search_string . "') ";
                    }

                    if ( $this->search_options['search_by_content'] == 'yes' ) {
                        $where .= " OR ({$wpdb->posts}.post_content REGEXP '" . $this->search_string . "')  ";
                    }

                    if( $this->search_options['search_by_cf'] != ''){
                        $where .= " OR ( cf1.meta_value REGEXP '{$this->search_string}' )  ";
                    }

                    $addor = true;

                }

                if ( !empty( $terms ) ) {
                    $where .= ( $addor ) ? ' OR ' : '';
                    $where .= " ((tm.name REGEXP '" . $this->search_string . "' OR tm.slug REGEXP '" . $this->search_string . "') AND tt.taxonomy IN ('" . implode( "','", $terms ) . "')) ";
                }

                $where .= " ) ";
            }


            return $where;
        }

        /**
         * Perform jax search products
         */
        public function ajax_search_products() {

            $this->search_string = apply_filters( 'yith_wcas_ajax_search_products_search_query', esc_attr( trim($_REQUEST['query']) ) );

            //get the order by filter
            $search_strings     = $this->parse_search_string( $this->search_string );
            $this->search_order = $this->parse_search_order( $this->search_string, $search_strings );

            $this->search_string = preg_replace('/\s+/', ' ', $this->search_string);
            //search both or singular
            if( get_option('yith_wcas_search_type_more_words') == 'and' ){
                $this->search_string = str_replace(' ','?(.*)',$this->search_string);
            }else{
                $this->search_string = str_replace(' ','|',  $this->search_string );
            }


            $this->post_type = apply_filters( 'yith_wcas_ajax_search_products_post_type', esc_attr( ( isset( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : 'product' ) );

            $suggestions = array();

            $args = array(
                'post_type'           => $this->post_type,
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'orderby'             => $this->search_options['orderby'],
                'order'               => $this->search_options['order'],
                'posts_per_page'      => $this->search_options['posts_per_page']+1,
                'suppress_filters'    => FALSE
            );

            if( $this->post_type == 'product' ) {
                $args['meta_query'] = array(
                    array(
                        'key'     => '_visibility',
                        'value'   => array( 'search', 'visible' ),
                        'compare' => 'IN'
                    ),
                );


                /* perform the research if there's a request with a specific category */
                if ( isset( $_REQUEST['product_cat'] ) ) {
                    $args['tax_query'] = array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'slug',
                            'terms'    => $_REQUEST['product_cat']
                        ) );
                }
            }

            add_filter( 'posts_where', array( $this, 'extend_search_where' ) );
            add_filter( 'posts_join', array( $this, 'extend_search_join' ) );
            add_filter( 'posts_groupby', array( $this, 'search_post_groupby' ) );
            add_filter( 'posts_orderby', array( $this, 'search_post_orderby' ) );

            $results = apply_filters('ywrac_results', get_posts( $args ), $_REQUEST['query'] ) ;

            if ( count( $results ) < $this->search_options['posts_per_page']){
                $product_in = $this->extend_to_sku(true);
                $product_by_sku = array();
                if ( !empty( $product_in ) ) {
                    $args['post__in'] = $product_in;
                    remove_filter( 'posts_where', array( $this, 'extend_search_where' ) );
                    remove_filter( 'posts_join', array( $this, 'extend_search_join' ) );
                    $product_by_sku   = get_posts( $args );
                }

                $results = array_merge( $results, $product_by_sku );
            }


            if ( !empty( $results ) ) {

                $max_number = get_option('yith_wcas_posts_per_page');
                $have_results = ( ( count( $results ) - $max_number ) > 0 ) ? true : false;
                $i = 0;
                foreach ( $results as $post ) {
                    if( $i++ == $max_number ) break;

                    if( $post->post_type == 'product' ){
                        $product = wc_get_product( $post );

                        if( $product ->is_visible() ){
                            $suggest = apply_filters( 'yith_wcas_suggestion', array(
                                'id'    => $product->id,
                                'value' => strip_tags($product->get_title()),
                                'url'   => $product->get_permalink(),
                            ), $product );


                            if ( get_option( 'yith_wcas_show_thumbnail' ) === 'left' || get_option( 'yith_wcas_show_thumbnail' ) === 'right' ) {
                                $thumb = $product->get_image( 'shop_thumbnail', array( 'class' => esc_attr( 'align-' . get_option( 'yith_wcas_show_thumbnail' ) ) ) );
                                $suggest['img'] = sprintf( '<div class="yith_wcas_result_image %s">%s</div>', esc_attr( 'align-' . get_option( 'yith_wcas_show_thumbnail' ) ), $thumb );
                            }


                            if ( ( $product->is_on_sale() && get_option( 'yith_wcas_show_sale_badge' ) != 'no')  || ( $product->is_featured() && get_option( 'yith_wcas_show_featured_badge' ) != 'no') ){
                                $suggest['div_badge_open'] = '<div class="badges">';
                                if ( $product->is_on_sale() && get_option( 'yith_wcas_show_sale_badge' ) != 'no' ) {
                                    $suggest['on_sale'] = '<span class="yith_wcas_result_on_sale">'.__('sale', 'yith-woocommerce-ajax-search').'</span>';
                                }

                                if ( ! $product->is_in_stock() && get_option( 'yith_wcas_show_outofstock_badge' ) != 'no' ) {
                                    $suggest['outofstock'] = '<span class="yith_wcas_result_outofstock">'.__('Out of stock', 'yith-woocommerce-ajax-search').'</span>';
                                }

                                if ( $product->is_featured() && get_option( 'yith_wcas_show_featured_badge' ) != 'no' && ! ( get_option( 'yith_wcas_hide_feature_if_on_sale' ) == 'yes' && $product->is_on_sale()) ) {

                                    $suggest['featured'] = '<span class="yith_wcas_result_featured">'.__('featured', 'yith-woocommerce-ajax-search').'</span>';
                                }
                                $suggest['div_badge_close'] = '</div>';
                            }

                            if ( get_option( 'yith_wcas_show_excerpt' ) != 'no' ) {
                                $excerpt = ( $product->post->post_excerpt != '' ) ? $product->post->post_excerpt : $product->post->post_content;
                                $num_of_words = ( get_option('yith_wcas_show_excerpt_num_words') ) ? get_option('yith_wcas_show_excerpt_num_words') : 10;
                                $excerpt = strip_tags(strip_shortcodes(preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $excerpt)));
                                $suggest['excerpt'] = sprintf( '<p class="yith_wcas_result_excerpt">%s</p>', wp_trim_words( $excerpt, $num_of_words ) );
                            }

                            if ( get_option( 'yith_wcas_show_price' ) != 'no' ) {
                                $suggest['price'] = $product->get_price_html();
                            }

                            $suggestions[] = $suggest;
                        }

                    }else{
                        $suggest = apply_filters( 'yith_wcas_suggestion', array(
                            'id'    => $post->ID,
                            'value' => strip_tags($post->post_title),
                            'url'   => get_permalink($post->ID),
                        ), $post );


                        if ( has_post_thumbnail( $post->ID ) && ( get_option( 'yith_wcas_show_thumbnail' ) === 'left' || get_option( 'yith_wcas_show_thumbnail' ) === 'right' ) ) {
                            $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'thumbnail' );
                            if( $thumb ){
                                $suggest['img'] = sprintf( '<div class="yith_wcas_result_image %s"><img src="%s" alt="%s"></div>', esc_attr( 'align-' . get_option( 'yith_wcas_show_thumbnail' ) ), $thumb['0'], $post->post_title );
                            }
                        }


                        if ( get_option( 'yith_wcas_show_excerpt' ) != 'no' ) {
                            $excerpt = ( $post->post_excerpt != '' ) ? $post->post_excerpt : $post->post_content;
                            $num_of_words = ( get_option('yith_wcas_show_excerpt_num_words') ) ? get_option('yith_wcas_show_excerpt_num_words') : 10;
                            $excerpt = strip_tags(strip_shortcodes(preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $excerpt)));
                            $suggest['excerpt'] = sprintf( '<p class="yith_wcas_result_excerpt">%s</p>', wp_trim_words( $excerpt, $num_of_words ) );
                        }

                        $suggestions[] = $suggest;
                    }

                }
            }
            else {
                $have_results = false;
                $suggestions[] = array(
                    'id'    => - 1,
                    'value' => get_option( 'yith_wcas_search_show_no_results_text' ) ? get_option( 'yith_wcas_search_show_no_results_text' ) : __( 'No results', 'yith-woocommerce-ajax-search' ),
                    'url'   => '',
                );

            }
            wp_reset_postdata();


            $suggestions = array(
                'results' => $have_results,
                'suggestions' => $suggestions,
            );

            echo json_encode( $suggestions );
            die();
        }


        public function pre_get_posts( $q ) {

            global $wp_the_query;

            //if ( ! is_admin() && is_search() && !empty( $wp_the_query->query_vars['s'] ) && !( defined( 'DOING_AJAX' ) && DOING_AJAX )) {
             if ( ! is_admin() &&  !empty( $wp_the_query->query_vars['s'] ) && !( defined( 'DOING_AJAX' ) && DOING_AJAX )) {

                $pt = isset( $wp_the_query->query_vars['post_type'] )  ? $wp_the_query->query_vars['post_type'] : 'any' ;
                $this->post_type = apply_filters( 'yith_wcas_ajax_search_products_post_type', esc_attr( $pt ) );

                $qv = apply_filters( 'yith_wcas_ajax_search_products_search_query', esc_attr( trim($wp_the_query->query_vars['s']) ) );

                //get the order by filter
                $search_strings = $this->parse_search_string($qv);
                $this->search_order = $this->parse_search_order($qv,$search_strings);

                $this->search_string = preg_replace('/\s+/', ' ', $qv);
                if( get_option('yith_wcas_search_type_more_words') == 'and' ){
                    $this->search_string = str_replace(' ','?(.*)',$this->search_string);
                }else{
                    $this->search_string = str_replace(' ','|',  $this->search_string );
                }


                set_query_var ( 's', $this->search_string );
                add_filter( 'posts_join',    array( $this, 'search_post_join' ) );
                add_filter( 'posts_where',   array( $this, 'search_post_where' ) );
                if( !isset($_GET['orderby'])){
                    add_filter( 'posts_orderby', array( $this, 'search_post_orderby' ) );
                }

                add_filter( 'posts_groupby', array( $this, 'search_post_groupby' ) );

                set_query_var ( 's', $qv );

            }
        }

        public function search_post_orderby( $orderby ){
            return $this->search_order;
        }

        public function search_post_join( $join ) {
            global $wpdb;

            $join = $this->extend_search_join( $join );
            return $join;
        }

        public function search_post_where( $where ) {
            if ( $where != '' ) {
                $where_array = array_filter( array_map( 'trim', explode( 'AND', $where ) ) );

                $ands = $where_array;
                foreach ( $ands as $key => $value ) {
                    if ( strpos( $value, 'post_content' ) !== false ) {
                        unset( $where_array[$key] );
                    }
                }
                $where = ' AND ' . implode( ' AND ', $where_array );
            }

            global $wpdb;

            $where = $this->extend_search_where( $where, true );

            /* search by sku */
            $product_by_sku = $this->extend_to_sku(true);


            if( ! empty($product_by_sku) ){
                $where .= ' OR '.$wpdb->posts.'.ID IN (' . implode( ',', $product_by_sku ) . ') ';
            }


            return $where;
        }

        public function search_post_groupby( $groupby ) {
            global $wpdb;
            $groupby = "{$wpdb->posts}.ID";
            return $groupby;
        }

        /**
         * Return a list of product id if the option search by sku is active
         *
         * @param $only_visible bool
         *
         * @return array
         *
         * @since  1.3.0
         * @author Emanuela Castorina
         */
        public function extend_to_sku( $only_visible = false ){
            global $wpdb;

            $product_in = array();

            if ( $this->search_options['search_by_sku'] == 'yes' ) {
                if( $only_visible ){
                    $product_in = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pm1.post_id FROM {$wpdb->postmeta} as pm1
                                          join {$wpdb->postmeta} pm2
                                          on pm1.post_id = pm2.post_id
                                          and pm2.meta_key='_sku'
                                          and pm2.meta_value REGEXP '%s'
                                          join {$wpdb->postmeta} visibility
                                          on pm1.post_id = visibility.post_id
                                          and visibility.meta_key = '_visibility'
                                          and visibility.meta_value <> 'hidden'", $this->search_string ) );
                }else{
                    $product_in = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pm1.post_id FROM {$wpdb->postmeta} as pm1
                                         where pm1.meta_key='_sku' and pm1.meta_value REGEXP '%s'", $this->search_string ) );
                }

                if( $this->search_options['search_by_sku_variations'] == 'yes' ) {
                    if( $only_visible ){
                        $sku_to_id = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p1.post_parent FROM {$wpdb->posts} as p1
                                          join {$wpdb->postmeta} pm2
                                          on p1.ID = pm2.post_id
                                          and pm2.meta_key='_sku'
                                          and pm2.meta_value REGEXP '%s'
                                          join {$wpdb->postmeta} visibility
                                          on p1.post_parent = visibility.post_id
                                          and visibility.meta_key = '_visibility'
                                          and visibility.meta_value <> 'hidden'", $this->search_string ) );
                    }else{
                        $sku_to_id = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pm1.post_id FROM {$wpdb->postmeta} as pm1
                                         where pm1.meta_key='_sku' and pm1.meta_value REGEXP '%s'", $this->search_string ) );
                    }

                    if ( ! empty ( $sku_to_id ) ) {
                        $product_in = array_merge( $sku_to_id, $product_in );
                    }
                }
            }

            return $product_in;
        }

        /**
         * Parse the search string
         *
         * @param $s string
         *
         * @return array
         *
         * @since  1.3.0
         * @author Emanuela Castorina
         */
        protected function parse_search_string( $s ) {
            // added slashes screw with quote grouping when done early, so done later
            $s = stripslashes( $s );
            $s = str_replace( array( "\r", "\n" ), '', $s );

            if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $s, $matches ) ) {
                $search_terms = $this->parse_search_terms( $matches[0] );
                // if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
                if ( empty( $search_terms ) || count( $search_terms ) > 9 ) {
                    $search_terms = array( $s );
                }
            }
            else {
                $search_terms = array( $s );
            }

            return $search_terms;
        }

        /**
         * Parse the search terms
         *
         * @param $terms array
         *
         * @return array
         *
         * @since  1.3.0
         * @author Emanuela Castorina
         */
        protected function parse_search_terms( $terms ) {
            global $wp_query;

            $strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
            $checked    = array();

            $stopwords = $wp_query->get_search_stopwords();
            foreach ( $terms as $term ) {
                // keep before/after spaces when term is for exact match
                if ( preg_match( '/^".+"$/', $term ) ) {
                    $term = trim( $term, "\"'" );
                }
                else {
                    $term = trim( $term, "\"' " );
                }

                // Avoid single A-Z.
                if ( !$term || ( 1 === strlen( $term ) && preg_match( '/^[a-z]$/i', $term ) ) ) {
                    continue;
                }

                if ( !empty( $stopwords ) && in_array( call_user_func( $strtolower, $term ), $stopwords, true ) ) {
                    continue;
                }

                $checked[] = $term;
            }

            return $checked;
        }

        /**
         * Parse the search order
         *
         * @param $s            string
         * @param $search_terms array
         *
         * @return string
         *
         * @since  1.3.0
         * @author Emanuela Castorina
         */
        protected function parse_search_order( $s, $search_terms ) {
            global $wpdb;

            $search_orderby_title = array();
            foreach ( $search_terms as $term ) {
                $like                   = '%' . $wpdb->esc_like( $term ) . '%';
                $search_orderby_title[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $like );
            }

            if ( count( $search_terms ) > 1 ) {

                $num_terms = count( $search_orderby_title );
                $like      = '%' . $wpdb->esc_like( $s ) . '%';

                $search_orderby = '(CASE ';
                // sentence match in 'post_title'
                $search_orderby .= $wpdb->prepare( "WHEN $wpdb->posts.post_title LIKE %s THEN 1 ", $like );

                // sanity limit, sort as sentence when more than 6 terms
                // (few searches are longer than 6 terms and most titles are not)
                if ( $num_terms < 7 ) {
                    // all words in title
                    $search_orderby .= 'WHEN ' . implode( ' AND ', $search_orderby_title ) . ' THEN 2 ';
                    // any word in title, not needed when $num_terms == 1
                    if ( $num_terms > 1 ) {
                        $search_orderby .= 'WHEN ' . implode( ' OR ', $search_orderby_title ) . ' THEN 3 ';
                    }
                }

                // sentence match in 'post_content'
                $search_orderby .= $wpdb->prepare( "WHEN $wpdb->posts.post_content LIKE %s THEN 4 ", $like );
                $search_orderby .= 'ELSE 5 END)';
            }
            else {
                // single word or sentence search
                $search_orderby = reset( $search_orderby_title ) . ' DESC';
            }

            return $search_orderby;
        }

        /**
         * Remove Filter after the query
         *
         * @return void
         *
         * @since 1.3.6
         * @author Emanuela Castorina
         */

        public function remove_pre_get_posts() {
            global $wp_the_query;
            if ( ! is_admin() &&  !empty( $wp_the_query->query_vars['s'] ) && !( defined( 'DOING_AJAX' ) && DOING_AJAX )) {
                remove_filter( 'posts_join', array( $this, 'search_post_join' ) );
                remove_filter( 'posts_where', array( $this, 'search_post_where' ) );
                add_filter( 'posts_orderby', array( $this, 'search_post_orderby' ) );
                add_filter( 'posts_groupby', array( $this, 'search_post_groupby' ) );
                remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
            }

        }


	    /* === YITH WooCommerce Brands Compatibility === */

	    /**
	     * Filters search options, to add brands to search
	     *
	     * @param $search_options mixed Original array of option
	     * @return mixed Filtered array of options
	     *
	     * @since 1.3.0
	     * @author Antonio La Rocca <antonio.larocca@yithemes.com>
	     */
	    public function add_brands_search_option( $search_options ){
		    if( defined( 'YITH_WCBR' ) ) {
			    $options_chunk_1 = array_splice( $search_options['search'], 0, 6 );
			    $options_chunk_2 = $search_options['search'];

			    $brand_option = array(
				    'search_in_product_brands' => array(
					    'name'    => __( 'Search in product brands', 'yith-woocommerce-ajax-search' ),
					    'desc'    => __( 'Extend search in product brands' ),
					    'id'      => 'yith_wcas_search_in_product_brands',
					    'default' => 'yes',
					    'type'    => 'checkbox'
				    )
			    );

			    $search_options['search'] = array_merge( $options_chunk_1, $brand_option, $options_chunk_2 );
		    }

		    return $search_options;
	    }

	    /**
	     * Filters search params, to add brands to search
	     *
	     * @param $search_params mixed Original array of params
	     * @return mixed Filtered array of params
	     *
	     * @since 1.3.0
	     * @author Antonio La Rocca <antonio.larocca@yithemes.com>
	     */
	    public function add_brands_search_params( $search_params ){
		    if( defined( 'YITH_WCBR' ) ) {
			    $search_params['search_by_brand'] = apply_filters( 'yith_wcas_search_in_product_brands', get_option( 'yith_wcas_search_in_product_brands' ) );
		    }

		    return $search_params;
	    }


    }
}