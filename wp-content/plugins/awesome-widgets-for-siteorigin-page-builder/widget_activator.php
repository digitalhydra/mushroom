<?php 
/*
Plugin Name: Awesome Widgets for SiteOrigin Page Builder
Plugin URI: http://www.phoeniixx.com
Description: Awesome Widgets for SiteOrigin Page Builder is a great plugin and it help you add Woocommerce Products Featured Collection, New Arrival, Sale Off, Best Seller and Category on any page of your site.
Author: phoeniixx
Version: 1.4
Text Domain:Custom widget activator
Domain Path: /languages
Author URI: http://www.phoeniixx.com
*/

if ( ! defined( 'ABSPATH' ) )
{
	exit;   
}
	// Exit if accessed directly
/**
 * Check if WooCommerce is active
 **/
 
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{
	add_action('wp_head','phoen_frontend_assests');
 
	function phoen_frontend_assests(){
		
	wp_enqueue_style( 'phoen-owl-css', plugin_dir_url(__FILE__).'/assests/css/phoe_owl_carousel.css' );

	wp_enqueue_script( 'phoen-owl-js', plugin_dir_url( __FILE__ ).'/assests/js/owl.carousel.js', array( 'jquery' ));
		
	wp_enqueue_script( 'phoen-fron-end-js', plugin_dir_url( __FILE__ ).'/assests/js/phoe_fron_end.js', array( 'jquery' ));

	}	
	

	add_action('admin_menu', 'phoen_awesome_widget_menu_tab');

	function phoen_awesome_widget_menu_tab(){
		
		$page_title="Awesome Widgets";
		$menu_title="Settings";
		$capability="manage_options";
		$menu_slug="phoen-awesome-widget-manager";
		$plugin_dir_url =  plugin_dir_url(__FILE__);
		add_menu_page( 'awesome-widget', __( 'Awesome Widgets', 'phe' ), 'nosuchcapability', 'awesome-widget', NULL, $plugin_dir_url.'/assests/images/logo-wp.png', 59);
		add_submenu_page( 'awesome-widget', $page_title, $menu_title, $capability, $menu_slug ,'widget_activator_settings');
		add_submenu_page( 'awesome-widget', $page_title,'Awesome widgets Pro', $capability, 'phoen-awesome-widget-manager&tab=premium' ,'widget_activator_settings');
	}	
	
	register_activation_hook(__FILE__, 'phoen_awesome_by_default_settings');
	
	function phoen_awesome_by_default_settings(){
		
		$is_enable = get_option('data_compare_product');
		
		if((!isset($is_enable) || $is_enable == '')){
			
			update_option('phoen_awesome_wid_enable',1);
			
		}
	}
	
	
	
	require_once('widget_activator_settings.php');
	
	//require_once('widget_activator_pro.php');
	
	$is_enable = get_option('phoen_awesome_wid_enable');
	
	if($is_enable == 1){
	
		include_once(plugin_dir_path(__FILE__).'widgets/top_seller_widget/top_seller_widget.php');
		
		include_once(plugin_dir_path(__FILE__).'widgets/featured_widget/featured_widget.php');
		
		include_once(plugin_dir_path(__FILE__).'widgets/new_arrival_widget/new_arrival_widget.php');
		
		include_once(plugin_dir_path(__FILE__).'widgets/sale_off_widget/sale_off_widget.php');
		
		include_once(plugin_dir_path(__FILE__).'widgets/category_widget/category_widget.php');
		
	}
	function mytheme_add_widget_tabs($tabs) {
		$tabs[] = array(
			'title' => __('Phoeniixx Widgets', 'phoeniixx'),
			'filter' => array(
				'groups' => array('phoeniixx')
			)
		);

		return $tabs;
	}
	add_filter('siteorigin_panels_widget_dialog_tabs', 'mytheme_add_widget_tabs', 20);
	
}
else
{ 
	?>
		<div class="error notice is-dismissible " id="message"><p>Please <strong>Activate</strong> WooCommerce Plugin First, to use it.</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
	<?php 
}  
?>
