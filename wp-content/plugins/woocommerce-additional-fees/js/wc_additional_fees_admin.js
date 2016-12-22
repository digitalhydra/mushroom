
jQuery(function() {
	
	var last_settings_tab = jQuery('input[name="wc_add_fees_settings_last_tab_active"]');
		
	jQuery('#wc_add_fees_product_container a').on('click', function(){
			var container = jQuery(this).closest('#wc_add_fees_product_container');
			var href = jQuery(this).attr('href');
			container.find('a').removeClass('current');
			jQuery(this).addClass('current');
			container.find('.section').hide();
			container.find(href).show();
			return false;
	});
	
	jQuery('#wc_add_fees_settings_container a').on('click', function(){
			var container = jQuery(this).closest('#wc_add_fees_settings_container');
			var href = jQuery(this).attr('href');
			container.find('a').removeClass('current');
			jQuery(this).addClass('current');
			container.find('.section').hide();
			container.find(href).show();
			last_settings_tab.val(href);
			return false;
	});
	
		//	activate last settings tab
	var last_settings_tab_link = last_settings_tab.val();
	var sett_container = last_settings_tab.closest('#wc_add_fees_settings_container');
	var tab = sett_container.find(last_settings_tab_link).first();
	
	if( tab.length > 0 )
	{
		jQuery('#wc_add_fees_settings_container a[href="'+ last_settings_tab_link + '"]').trigger('click');
	}
	else
	{
		jQuery('#wc_add_fees_settings_container a:eq(1)').trigger('click');
	}
	
	jQuery('#wc_add_fees_product_container a:eq(1)').trigger('click');
	
});

