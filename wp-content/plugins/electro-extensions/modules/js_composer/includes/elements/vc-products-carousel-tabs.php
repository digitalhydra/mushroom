<?php
if ( ! function_exists( 'electro_vc_products_carousel_tabs' ) ) :

function electro_vc_products_carousel_tabs( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'tab_title_1'		=> '',
		'tab_content_1'		=> '',
		'tab_title_2'		=> '',
		'tab_content_2'		=> '',
		'tab_title_3'		=> '',
		'tab_content_3'		=> '',
		'product_items'		=> 3,
		'product_columns'	=> 3,
		'nav_align'			=> 'center'
	), $atts ) );

	$args = array(
		'tabs' 		=> array(
			array(
				'title'			=> $tab_title_1,
				'shortcode_tag'	=> $tab_content_1
			),
			array(
				'title'			=> $tab_title_2,
				'shortcode_tag'	=> $tab_content_2
			),
			array(
				'title'			=> $tab_title_3,
				'shortcode_tag'	=> $tab_content_3
			)
		),
		'limit'		=> $product_items,
		'columns'	=> $product_columns,
		'nav-align'	=> $nav_align
	);

	$html = '';
	if( function_exists( 'electro_products_carousel_tabs' ) ) {
		ob_start();
		electro_products_carousel_tabs( $args );
		$html = ob_get_clean();
	}

	return $html;
}

add_shortcode( 'electro_products_carousel_tabs' , 'electro_vc_products_carousel_tabs' );

endif;