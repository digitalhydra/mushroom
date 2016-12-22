<?php
/*
Plugin Name: WooCommerce PDF Invoices
Plugin URI: https://www.woothemes.com/products/pdf-invoices/
Description: Attach a PDF Invoice to the completed order email and allow invoices to be downloaded from customer's My Account page.
Version: 3.0.1
Author: Add On Enterprises
Author URI: http://www.addonenterprises.com
*/

/*  Copyright 2011  Add On Enterprises LLC  (email : support@addonenterprises.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

    /**
     * Required functions
     */
    if ( ! function_exists( 'woothemes_queue_update' ) )
        require_once( 'woo-includes/woo-functions.php' );

    /**
     * Plugin updates
     */
    woothemes_queue_update( plugin_basename( __FILE__ ), '7495e3f13cc0fa3ee07304691d12555c', '228318' );

    /**
     * Don't do anything else unless WC is active
     */
    if ( is_woocommerce_active() ) :

        /**
         * Defines
         */
        define( 'PDFVERSION' , '1.3.0' );
		define( 'PDFLANGUAGE', 'woocommerce-pdf-invoice' );
		define( 'PDFSETTINGS' , admin_url( 'admin.php?page=woocommerce_pdf' ) );
		define( 'PDFSUPPORTURL' , 'http://support.woothemes.com/' );
		define( 'PDFDOCSURL' , 'http://docs.woothemes.com/document/woocommerce-pdf-invoice-setup-and-customization/');


        /**
         * Localization
         */
        $locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-pdf-invoice' );
        load_textdomain( 'woocommerce-pdf-invoice', WP_LANG_DIR . "/woocommerce-pdf-invoice/woocommerce-pdf-invoice-$locale.mo" );
        load_plugin_textdomain( 'woocommerce-pdf-invoice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        /**
         * Admin Settings
         */
		if ( is_admin() ) :
        	include( 'classes/class-pdf-settings-class.php' );
		endif;

		/**
		 * Sending PDFs and such
		 */
		include( 'classes/class-pdf-send-pdf-class.php' );

		/**
		 * Various PDF functions
		 * - Order meta box
		 * - My Account download PDF Invoice link
		 */
		include( 'classes/class-pdf-functions-class.php' );

        /**
         * WPML Compatibility
         */
        include 'classes/class-wpml-integration.php';

        /**
         * Load Admin Class
         * Used for plugin links, seems to break if added to an include file
         * so it's got it's own class for now.
         */
        class WC_pdf_admin {

            public function __construct() {

                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this,'plugin_links' ) );

            }

            /**
             * Plugin page links
             */
            function plugin_links( $links ) {

                $plugin_links = array(
                    '<a href="' . PDFSUPPORTURL . '">' . __( 'Support', 'woocommerce-pdf-invoice' ) . '</a>',
                    '<a href="' . PDFDOCSURL . '">' . __( 'Docs', 'woocommerce-pdf-invoice' ) . '</a>',
                );

                return array_merge( $plugin_links, $links );
            }

            function activate() {

                $woocommerce_pdf_invoice_version = get_option( 'woocommerce_pdf_invoice_version' );
                if ( !isset($woocommerce_pdf_invoice_version) || $woocommerce_pdf_invoice_version != PDFVERSION ) {
                    $this->install();
                }

            }

            function update() {

                $woocommerce_pdf_invoice_version = get_option( 'woocommerce_pdf_invoice_version' );
                if ( !isset($woocommerce_pdf_invoice_version) || $woocommerce_pdf_invoice_version != PDFVERSION ) {
                    $this->install();
                }

            }

            function install() {

                $this->do_install_woocommerce_pdf_invoice();
                update_option( 'woocommerce_pdf_invoice_version', PDFVERSION );

            }

            /**
             * Installation functions
             */
            function do_install_woocommerce_pdf_invoice() {

                $upload_dir =  wp_upload_dir();

                if ( wp_mkdir_p( $upload_dir['basedir'] . '/woocommerce_pdf_invoice' ) && ! file_exists( $upload_dir['basedir'] . '/woocommerce_pdf_invoice/index.html' ) ) {
                    
                    if ( $file_handle = @fopen( $upload_dir['basedir'] . '/woocommerce_pdf_invoice/index.html', 'w' ) ) {
                        fwrite( $file_handle, '' );
                        fclose( $file_handle );
                    }

                }

            }

        }

        if ( is_admin() ) :
            $GLOBALS['WC_pdf_admin'] = new WC_pdf_admin();
        endif;

        // Empty the PDF Folder
        if ( ! wp_next_scheduled( 'empty_pdf_task' ) ) {
            wp_schedule_event( time(), 'hourly', 'empty_pdf_task' );
        }

        add_action( 'empty_pdf_task', 'empty_pdf_folder' );

        function empty_pdf_folder() {

            $upload_dir =  wp_upload_dir();
            if ( file_exists( $upload_dir['basedir'] . '/woocommerce_pdf_invoice/index.html' ) ) {
                $pdftemp = $upload_dir['basedir'] . '/woocommerce_pdf_invoice';

                $files = glob( $pdftemp . '/*.pdf' ); // get all file names
                foreach($files as $file) {

                    if( is_file($file) ) {
                        unlink( $file ); // delete file
                    }

                }

            }

        }

    endif; // End is_woocommerce_active
