<?php
/* product category widget */

class phoen_category_wpb_widget extends WP_Widget
{
	function __construct()
	{
		parent::__construct(
		// Base ID of your widget
		'phoen_category_wpb_widget', 

		// Widget name will appear in UI
		__('Woocommerce Category by Phoeniixx', 'wpb_widget_domain'), 

		// Widget description
		array( 'description' => __( 'Category Products For Woocommerce', 'wpb_widget_domain' ), 'panels_groups' => array('phoeniixx')) 
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) 
	{
		
		if($instance['title'])
		{
			$title =  $instance['title'];
		}
		else
		{
			$title = "Category Products";
		}
		if($instance['product_category'])
		{
			$product_category =  $instance['product_category'];
		}
		else
		{
			$product_category = "Category Products";
		}
		if($instance['product_limit'])
		{
			$product_limit =  $instance['product_limit'];
		}
		else
		{
			$product_limit = 10;
		}

		 ?>
		 
			<div class="woocommerce">
			
				<h2 class="recent-products-heading"><?php echo $title; ?></h2>  
				
					<ul id="<?php echo 'phoen_category'.$instance['panels_info']['id']; ?>" class="products owl-carousel phoen_category" style="display:block;">
					
					<?php
						if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
						{
								$args = array( 
								 
										 'post_type' => 'product',
										 'posts_per_page' => $product_limit,
										 'product_cat'=>$product_category,
										 'status' => 'publish',
										 'orderby' => 'desc'

								);
								 
									$loop = new WP_Query( $args );
									
									
								if ( $loop->have_posts() ) 
								{
									while ( $loop->have_posts() ) : $loop->the_post(); global $product; 

												woocommerce_get_template_part( 'content', 'product' );
										endwhile;
								}
								else
								{
									?>
									<div class="js-empty-section">
										
										<p>No products found</p>
											
									</div>
									<?php
									
								}
											
											?>
								<?php wp_reset_query();
						}
						else
						{
							?>
								<div class="js-empty-section">
										
										<p>Woocommerce plugin does not exist</p>
											
									</div>
							<?php
						}
					?>
					</ul>
				
			</div>
			
		<?php	
		//echo $args['after_widget'];
		//echo $instance['phoen_carousel'];
		
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

						jQuery(this).find('#phoen_category'+<?php echo $instance['panels_info']['id']; ?>).owlCarousel({
							
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
						
						jQuery(this).find('#phoen_category'+<?php echo $instance['panels_info']['id']; ?>).show();

					}); 
					
				});
				
			</script>
			
			<?php
		}
	}


			
	// Widget Backend 
	public function form( $instance ) 
	{
		
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else 
		{
			$title = __( 'Category Product', 'wpb_widget_domain' );
		}
		if ( isset( $instance[ 'product_category' ] ) ) {
			$product_category = $instance[ 'product_category' ];
		
		}
		else 
		{
			$product_category ='music';
		}
		if ( isset( $instance[ 'product_limit' ] ) ) 
		{
			$product_limit = $instance[ 'product_limit' ];
		}
		else 
		{
			$product_limit =12;
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
		$prod_cat_args = array(
		  'taxonomy'     => 'product_cat', 
		  'orderby'      => 'name',
		  'empty'        => 0
		);

		$woo_categories = get_categories( $prod_cat_args );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		
		<p class="default-form">
			<label for="<?php echo $this->get_field_id( 'product_category' ); ?>"><?php _e( 'Product Category:' ); ?></label> 
			<select class="widefat product_category" name="<?php echo $this->get_field_name( 'product_category' ); ?>" id="<?php echo $this->get_field_id( 'product_category' ); ?>">
			<?php
			foreach($woo_categories as $cat)
			{
				?>
					<option value="<?php echo $cat->slug; ?>" <?php if($product_category==$cat->slug){echo 'selected';}?>><?php echo $cat->name; ?></option>
				<?php		
			
			}
			?>
			</select>
		</p>
		<p>

			<label for="<?php echo $this->get_field_id( 'product_limit' ); ?>"><?php _e( 'Number of Product show:' ); ?></label> 
			<input type="number" name="<?php echo $this->get_field_name( 'product_limit' ); ?>" id="<?php echo $this->get_field_id( 'product_limit' ); ?>" value="<?php echo $product_limit;?>">
		</p>
		<p>

			<label for="<?php echo $this->get_field_id( 'phoen_carousel' ); ?>"><?php _e( 'Carousel Enable:' ); ?></label> 
			<input type="radio" name="<?php echo $this->get_field_name( 'phoen_carousel' ); ?>" id="<?php echo $this->get_field_id( 'phoen_carousel_yes' ); ?>" value="yes" <?php if($phoen_carousel=='yes'){ echo "checked";}?>>Yes
			<input type="radio" name="<?php echo $this->get_field_name( 'phoen_carousel' ); ?>" id="<?php echo $this->get_field_id( 'phoen_carousel_no' ); ?>" value="no" <?php if($phoen_carousel=='no'){ echo "checked";}?>>NO
		</p>
		<?php 
	}
		
	// Updating widget replacing old instances with new
	public function phoen_category_pro_update( $new_instance, $old_instance ) 
	{
		$instance = array();
		
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		
		$instance['product'] = ( ! empty( $new_instance['product'] ) ) ? strip_tags( $new_instance['product'] ) : '';
		
		$instance['product_category'] = ( ! empty( $new_instance['product_category'] ) ) ? strip_tags( $new_instance['product_category'] ) : '';
		
		$instance['product_limit'] = ( ! empty( $new_instance['product_limit'] ) ) ? strip_tags( $new_instance['product_limit'] ) : '';
		
		$instance['phoen_carousel'] = ( ! empty( $new_instance['phoen_carousel'] ) ) ? strip_tags( $new_instance['phoen_carousel'] ) : '';
		
		return $instance;
	}
} // Class wpb_widget ends here

// Register and load the widget
function phoen_category_pro_wpb_load_widget() {
	
	register_widget( 'phoen_category_wpb_widget' );
	
}
add_action( 'widgets_init', 'phoen_category_pro_wpb_load_widget' );
?>
