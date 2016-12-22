<?php 
/* Best seller widget */ 
class phoen_best_seller_wpb_widget extends WP_Widget {
	
	function __construct() {
	
		parent::__construct(
			// Base ID of your widget
			'phoen_best_seller_wpb_widget', 

			// Widget name will appear in UI
			__('Woocommerce Best Seller by Phoeniixx', 'wpb_widget_domain'), 

			// Widget description
			array( 'description' => __( 'Best Seller Products For Woocommerce', 'wpb_widget_domain' ),  'panels_groups' => array('phoeniixx'))
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {
		
		if(isset($instance['product_limit']))
		{
			$product_limit = $instance['product_limit'];
		}
		else
		{
			$product_limit = 12;
		}
		if(isset($instance['title']))
		{
			$title = $instance['title'];
		}
		else
		{
			$title = "Best Seller";
		}
		//echo $args['before_widget'];
		?>

		<div class="recent-products woocommerce">
			<h2 class="recent-products-heading"><?php echo $title; ?></h2>  
				<ul id="<?php echo 'phoen_best_product'.$instance['panels_info']['id']; ?>"  class="products owl-carousel" style="display:block;">
				<?php
					if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
					{
						$args = array(
										'post_type' => 'product',
										'posts_per_page' => $product_limit,
										'meta_key' => 'total_sales',
										'orderby' => 'meta_value_num',
									);

						$loop = new WP_Query( $args );
						
						if ( $loop->have_posts() ) {
							
							while ( $loop->have_posts() ) : $loop->the_post();
							
							woocommerce_get_template_part( 'content', 'product' );
							
							endwhile;
						}else{
							
							echo __( 'No products found' );
						
						}

						wp_reset_query();
					}
					else
					{
					?>
						<div class="js-empty-section">
									
							<p>Woocommerce Plugin Doest Not Exist</p>
										
						</div>
					<?php
					}
				?>
				</ul>
			
		</div>
	<?php
	if($instance['phoen_carousel'] == 'yes' )
		{
			
			?>
			
			<script>
			
				jQuery(document).ready(function(){

					jQuery('.panel-grid').each(function(){
						
						var get_length = jQuery(this).find('.panel-grid-cell').length;

						if(get_length == 1)
						{
							
							var item_count = 5;
							
						}
						else if (get_length == 2)
						{
							
							var item_count = 3;
							
						}
						else if (get_length == 3)
						{
							
							var item_count = 2;
							
						}
						else if (get_length == 4)
						{
							
							var item_count = 1;
							
						}

						jQuery(this).find('#phoen_best_product'+<?php echo $instance['panels_info']['id']; ?>).owlCarousel({
							
							items:item_count,
							
							autoPlay: 3000, //Set AutoPlay to 3 seconds
							
							lazyLoad : true,
							
							navigation : true,
							
							responsiveClass:true,
							
							responsive:{

								0:{
									items:1,            
								},
								480:{
									items:2,            
								},
								600:{
									items:3,            
								},
								768:{
									items:5,           
								}
							},
							
						});
					
						jQuery(this).find('#phoen_best_product'+<?php echo $instance['panels_info']['id']; ?>).show();
							
					}); 
					
				});
				
			</script>
			
			<?php
		}
	//echo $args['after_widget'];
	}
		
	// Widget Backend 
	public function form( $instance ) {
		
		if ( isset( $instance[ 'title' ] ) ) {
		
			$title = $instance[ 'title' ];
		}
		else 
		{
			
			$title = __( 'Best Seller', 'wpb_widget_domain' );
		}
		if ( isset( $instance[ 'product_limit' ] ) ) {
		
			$product_limit = $instance[ 'product_limit' ];
		}
		else 
		{
			
			$product_limit = 12;
		}
		if ( isset( $instance[ 'phoen_carousel' ] ) )
		{
			$phoen_carousel = $instance[ 'phoen_carousel'];
		}
		else 
		{
		$phoen_carousel ='yes';
		}
		// Widget admin form
		?>
		<p>
			
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" placeholder="New Title" value="<?php echo esc_attr( $title ); ?>" />
		
		</p>
		<p>
			
			<label for="<?php echo $this->get_field_id( 'product_limit' ); ?>"><?php _e( 'No Of Products Show:' ); ?></label> 
			
			<input class="widefat" id="<?php echo $this->get_field_id( 'product_limit' ); ?>" name="<?php echo $this->get_field_name( 'product_limit' ); ?>" type="number" placeholder="New Title" value="<?php echo esc_attr( $product_limit ); ?>" />
		
		</p>
		<p>

			<label for="<?php echo $this->get_field_id( 'phoen_carousel' ); ?>"><?php _e( 'Carousel Enable:' ); ?></label> 
			<input type="radio" name="<?php echo $this->get_field_name( 'phoen_carousel' ); ?>" id="<?php echo $this->get_field_id( 'phoen_carousel_yes' ); ?>" value="yes" <?php if($phoen_carousel=='yes'){ echo "checked";}?>>Yes
			<input type="radio" name="<?php echo $this->get_field_name( 'phoen_carousel' ); ?>" id="<?php echo $this->get_field_id( 'phoen_carousel_no' ); ?>" value="no" <?php if($phoen_carousel=='no'){ echo "checked";}?>>NO
		</p>
		<?php 
	}
	
	// Updating widget replacing old instances with new
	public function phoen_best_seller_pro_update( $new_instance, $old_instance ) {
		
		$instance = array();
	
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		
		$instance['product_limit'] = ( ! empty( $new_instance['product_limit'] ) ) ? strip_tags( $new_instance['product_limit'] ) : '';
		$instance['phoen_carousel'] = ( ! empty( $new_instance['phoen_carousel'] ) ) ? strip_tags( $new_instance['phoen_carousel'] ) : '';
		
		return $instance;
	}
} // Class wpb_widget ends here

// Register and load the widget
function phoen_best_seller_pro_wpb_load_widget() {
	
	register_widget( 'phoen_best_seller_wpb_widget' );

}

add_action( 'widgets_init', 'phoen_best_seller_pro_wpb_load_widget' );
?>