jQuery(window).load(function() {
		
	
	
	//console.log( jQuery('#pgc-691-3-1').width() );

	var owl = jQuery("#owl-demo");

	owl.owlCarousel({

		items : 2, //10 items above 1000px browser width
		itemsDesktop : [1000,5], //5 items between 1000px and 901px
		itemsDesktopSmall : [900,3], // 3 items betweem 900px and 601px
		itemsTablet: [600,2], //2 items between 600 and 0;
		itemsMobile : false // itemsMobile disabled - inherit from itemsTablet option

	});

	// Custom Navigation Events
	jQuery(".next").click(function(){
		owl.trigger('owl.next');
	})
	jQuery(".prev").click(function(){
		owl.trigger('owl.prev');
	})
	jQuery(".play").click(function(){
		owl.trigger('owl.play',1000);
	})
	jQuery(".stop").click(function(){
		owl.trigger('owl.stop');
	}) 

});