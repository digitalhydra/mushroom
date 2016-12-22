<?php 

function widget_activator_settings(){
	
	?>
	
	<div id="profile-page" class="wrap">
		<?php 
			if(isset($_GET['tab'])){
				
				$tab = sanitize_text_field( $_GET['tab'] );
			}else{
				
				$tab ='';
			}
		
			?>
		<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a class="nav-tab <?php if($tab == 'general' || $tab == ''){ echo esc_html( "nav-tab-active" ); } ?>" href="?page=phoen-awesome-widget-manager&amp;tab=general">General</a>
				<a class="nav-tab <?php if($tab == 'premium'){ echo esc_html( "nav-tab-active" ); } ?>" href="?page=phoen-awesome-widget-manager&amp;tab=premium">Premium Widgets</a>	</h2>
		<?php
		$plugin_dir_url = plugin_dir_url(__FILE__);
		
		if($tab == 'general' || $tab == '')
		{
			if ( ! empty( $_POST ) && check_admin_referer('phoen_awesome_widgets_setting_my_action', 'phoen_awesome_widgets_setting_my_fields') ) {
		
				if($_POST['paw_save_all']){
					
					if(isset($_POST['phoen_awesome_widgets_enable'])){
							
						$is_enable = sanitize_text_field( $_POST['phoen_awesome_widgets_enable'] );
						
					}else{
						
						$is_enable = 0;
					}
					
					
					$settings_save = update_option('phoen_awesome_wid_enable',$is_enable);
					
					if($settings_save == 1){
					
					?>

						<div class="updated" id="message">

							<p><strong>Setting updated.</strong></p>

						</div>

					<?php
					}
					else
					{
						?>
							<div class="error below-h2" id="message"><p> Something Went Wrong Please Try Again With Valid Data.</p></div>
						<?php
					}
					
				}
			}
		?>
		<div class="meta-box-sortables" id="normal-sortables">
			<div class="postbox " id="pho_wcpc_box">
				
				<div class="inside">
					<div class="pho_check_pin">

						<div class="column two">
						
							<div class="pho-upgrade-btn">
									<a target="_blank" href="https://www.phoeniixx.com/product/awesome-widgets-siteorigin-page-builder/"><img src="<?php echo $plugin_dir_url; ?>assests/images/premium-btn.png" /></a>
									<a href="https://phoeniixx.com/demo/woocommerce/siteorigin-widgets/all-in-one-widget-1/" target="_blank"><img src="<?php echo $plugin_dir_url; ?>assests/images/demo-btn.png" /></a>
								</div>
						</div>
					</div>
				</div>
			</div>
		</div>	

		<form method="post" id="pcp_form">
      
		<?php wp_nonce_field( 'phoen_awesome_widgets_setting_my_action', 'phoen_awesome_widgets_setting_my_fields' ); 
		
		  $is_enable = get_option('phoen_awesome_wid_enable');?>
		  
			<h3>General Settings</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top" class="">
						<th class="titledesc" scope="row">Enable Plugin</th>
						<td class="forminp forminp-checkbox">
							<fieldset>
								<legend class="screen-reader-text"></legend>
								<label for="phoen_awesome_widgets_enable">
									<input type="checkbox"  value="1" <?php echo esc_attr($is_enable==1)?'checked':'' ;?> id="phoen_awesome_widgets_enable" name="phoen_awesome_widgets_enable"> 
								</label> 
							</fieldset>
						</td>
					</tr>
					
				</tbody>
			</table>
			<br />	
			<input type="submit" value="Save Changes" class="button-primary" name="paw_save_all"  style="float: left; margin-right: 10px;">
			
		</form>
		
		<style>
				.form-table th {
					width: 270px;
					padding: 25px;
				}
				.form-table td {
					
					padding: 20px 10px;
				}
				.form-table {
					background-color: #fff;
				}
				

				.px-multiply{ color:#ccc; vertical-align:bottom;}

				.long{ display:inline-block; vertical-align:middle; }

				.wid{ display:inline-block; vertical-align:middle;}

				.up{ display:block;}

				.grey{ color:#b0adad;}
				
				#pho_wcpc_box.postbox h3{ padding:0 0 0 10px;}
				
				.pho-upgrade-btn a:focus {outline: none; box-shadow: none; }
				.pho-upgrade-btn a {display: inline-block;}
		</style>

		
	</div>
	
	
	
	<?php
	
	}elseif($tab == 'premium' ){
		
		require_once('awesome_widget_pro.php');
		
	}
	

}

?>