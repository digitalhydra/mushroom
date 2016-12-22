<?php
/**
 * Admin class
 *
 * @author Yithemes
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( !defined( 'YITH_WCAS' ) ) { exit; } // Exit if accessed directly

if( !class_exists( 'YITH_WCAS_Admin' ) ) {
    /**
     * Admin class. 
	 * The class manage all the admin behaviors.
     *
     * @since 1.0.0
     */
    class YITH_WCAS_Admin {
		/**
		 * Plugin options
		 * 
		 * @var array
		 * @access public
		 * @since 1.0.0
		 */
		public $options = array();

        /**
         * Plugin version
         *
         * @var string
         * @since 1.0.0
         */
        public $version;

        /**
         * @var $_panel Panel Object
         */
        protected $_panel;



        /**
         * @var string Ajax Search panel page
         */
        protected $_panel_page = 'yith_wcas_panel';

        /**
         * Various links
         *
         * @var string
         * @access public
         * @since 1.0.0
         */
       public $doc_url    = 'http://yithemes.com/docs-plugins/yith-woocommerce-ajax-search/';
    
    	/**
		 * Constructor
		 * 
		 * @access public
		 * @since 1.0.0
		 */
		public function __construct( $version ) {
            $this->version = $version;

			//Actions
            add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

            add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCAS_DIR . '/' . basename( YITH_WCAS_FILE ) ), array( $this, 'action_links') );
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
            // register plugin to licence/update system
            add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
            add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );

            //custom styles and javascripts
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

            // YITH WCAS Loaded
            do_action( 'yith_wcas_loaded' );
		}

        /**
         * Enqueue styles and scripts
         *
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function enqueue_styles_scripts() {
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script('yith_wcas_admin', YITH_WCAS_URL . 'assets/js/yith-wcas-admin' . $suffix .'.js', array('jquery'), '1.2.3', true);

        }



        /**
         * Add a panel under YITH Plugins tab
         *
         * @return   void
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use     /Yit_Plugin_Panel class
         * @see      plugin-fw/lib/yit-plugin-panel.php
         */
        public function register_panel() {

            if ( ! empty( $this->_panel ) ) {
                return;
            }

            $admin_tabs = array(
                'settings' => __( 'Settings', 'yith-woocommerce-ajax-search' ),
                'search'   => __( 'Search', 'yith-woocommerce-ajax-search' ),
                'output'   => __( 'Output', 'yith-woocommerce-ajax-search' )
            );

            $args = array(
                'create_menu_page' => true,
                'parent_slug'      => '',
                'page_title'       => __( 'Ajax Search', 'yith-woocommerce-ajax-search' ),
                'menu_title'       => __( 'Ajax Search', 'yith-woocommerce-ajax-search' ),
                'capability'       => 'manage_options',
                'parent'           => '',
                'parent_page'      => 'yit_plugin_panel',
                'page'             => $this->_panel_page,
                'admin-tabs'       => $admin_tabs,
                'options-path'     => YITH_WCAS_DIR . '/plugin-options'
            );

            /* === Fixed: not updated theme  === */
            if( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
                require_once( 'plugin-fw/lib/yit-plugin-panel-wc.php' );
            }

            $this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );

            add_action( 'woocommerce_admin_field_yith_wcas_upload', array( $this->_panel, 'yit_upload' ), 10, 1 );
            //add_action( 'woocommerce_update_option_yith_wcas_upload', array( $this->_panel, 'yit_upload_update' ), 10, 1 );
        }



        /**
         * Register plugins for activation tab
         *
         * @return void
         * @since    2.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_activation() {
            if( ! class_exists( 'YIT_Plugin_Licence' ) ) {
                require_once 'plugin-fw/licence/lib/yit-licence.php';
                require_once 'plugin-fw/licence/lib/yit-plugin-licence.php';
            }
            YIT_Plugin_Licence()->register( YITH_WCAS_INIT, YITH_WCAS_SECRET_KEY, YITH_WCAS_SLUG );
        }

        /**
         * Register plugins for update tab
         *
         * @return void
         * @since    2.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_updates() {
            if( ! class_exists( 'YIT_Upgrade' ) ) {
                require_once( 'plugin-fw/lib/yit-upgrade.php' );
            }
            YIT_Upgrade()->register( YITH_WCAS_SLUG, YITH_WCAS_INIT );
        }



        /**
         * Action Links
         *
         * add the action links to plugin admin page
         *
         * @param $links | links plugin array
         *
         * @return   mixed Array
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @return mixed
         * @use plugin_action_links_{$plugin_file_name}
         */

        public function action_links( $links ) {
            $links[] = '<a href="' . admin_url( "admin.php?page={$this->_panel_page}" ) . '">' . __( 'Settings', 'yith-woocommerce-ajax-search' ) . '</a>';
            return $links;
        }

        /**
         * plugin_row_meta
         *
         * add the action links to plugin admin page
         *
         * @param $plugin_meta
         * @param $plugin_file
         * @param $plugin_data
         * @param $status
         *
         * @return   Array
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use plugin_row_meta
         */
        public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

            if( defined( 'YITH_WCAS_INIT' ) && YITH_WCAS_INIT == $plugin_file ){
                $plugin_meta[] = '<a href="' . $this->doc_url . '" target="_blank">' . __( 'Plugin Documentation', 'yith-woocommerce-ajax-search' ) . '</a>';
            }
            return $plugin_meta;
        }


    }
}
