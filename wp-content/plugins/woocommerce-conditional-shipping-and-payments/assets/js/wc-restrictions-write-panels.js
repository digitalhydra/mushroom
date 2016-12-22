/* global wc_restrictions_admin_params */

jQuery( function($) {

	function wc_restrictions_getEnhancedSelectFormatString() {
		var formatString = {
			formatMatches: function( matches ) {
				if ( 1 === matches ) {
					return wc_restrictions_admin_params.i18n_matches_1;
				}

				return wc_restrictions_admin_params.i18n_matches_n.replace( '%qty%', matches );
			},
			formatNoMatches: function() {
				return wc_restrictions_admin_params.i18n_no_matches;
			},
			formatAjaxError: function( jqXHR, textStatus, errorThrown ) {
				return wc_restrictions_admin_params.i18n_ajax_error;
			},
			formatInputTooShort: function( input, min ) {
				var number = min - input.length;

				if ( 1 === number ) {
					return wc_restrictions_admin_params.i18n_input_too_short_1;
				}

				return wc_restrictions_admin_params.i18n_input_too_short_n.replace( '%qty%', number );
			},
			formatInputTooLong: function( input, max ) {
				var number = input.length - max;

				if ( 1 === number ) {
					return wc_restrictions_admin_params.i18n_input_too_long_1;
				}

				return wc_restrictions_admin_params.i18n_input_too_long_n.replace( '%qty%', number );
			},
			formatSelectionTooBig: function( limit ) {
				if ( 1 === limit ) {
					return wc_restrictions_admin_params.i18n_selection_too_long_1;
				}

				return wc_restrictions_admin_params.i18n_selection_too_long_n.replace( '%qty%', limit );
			},
			formatLoadMore: function( pageNumber ) {
				return wc_restrictions_admin_params.i18n_load_more;
			},
			formatSearching: function() {
				return wc_restrictions_admin_params.i18n_searching;
			}
		};

		return formatString;
	}

	$.fn.wc_restrictions_select2 = function() {

		$( this ).find( ':input.wc-enhanced-select' ).filter( ':not(.enhanced)' ).each( function() {
			var select2_args = $.extend({
				minimumResultsForSearch: 10,
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' )
			}, wc_restrictions_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );
	};

	$.fn.wc_restrictions_scripts = function() {

		if ( wc_restrictions_admin_params.is_wc_version_gte_2_3 == 'yes' ) {

			$( this ).wc_restrictions_select2();

		} else {

			$( this ).find( '.chosen_select' ).chosen();
		}

		$( this ).find( '.help_tip, .tips' ).tipTip( {
			'attribute' : 'data-tip',
			'fadeIn' : 50,
			'fadeOut' : 50,
			'delay' : 200
		} );
	};

	/* ------------------------------------*/
	/* Restrictions
	/* ------------------------------------*/

	if ( wc_restrictions_admin_params.post_id === '' ) {

		$( '#restrictions_data' ).closest( 'table.form-table' ).removeClass( 'form-table' ).addClass( 'restrictions-form-table' );

		// Meta-Boxes - Open/close
		$( '#restrictions_data' ).on( 'click', '.wc-metabox > h3', function( event ) {
			$( this ).next( '.wc-metabox-content' ).stop().slideToggle( 300 );
		} );

		$( '#restrictions_data' ).on( 'click', '.wc-metabox > h3', function() {
			$( this ).parent( '.wc-metabox' ).toggleClass( 'closed' ).toggleClass( 'open' );
		} );

		if ( wc_restrictions_admin_params.is_wc_version_gte_2_3 == 'yes' ) {
			$( '#restrictions_data' ).wc_restrictions_select2();
		} else {
			$( '#restrictions_data .chosen_select' ).chosen();
		}

		$( '#restrictions_data .wc-metabox' ).each( function() {

			var p = $( this );
			var c = p.find( '.wc-metabox-content' );

			if ( p.hasClass( 'closed' ) )
				c.hide();
		} );

	}

	// Restriction Remove
	$( '#restrictions_data' ).on( 'click', 'button.remove_row', function() {

		var $parent = $( this ).parent().parent();

		$parent.find('*').off();
		$parent.remove();
		restrictions_row_indexes();
	} );

	// Restriction Keyup
	$( '#restrictions_data' ).on( 'keyup', 'textarea.short_description', function() {
		$( this ).closest( '.woocommerce_restriction' ).find( 'h3 .restriction_title_inner' ).text( $( this ).val() );
	} );

	// Restriction Expand
	$( '#restrictions_data' ).on( 'click', '.expand_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .wc-metabox-content' ).show();
		return false;
	} );

	// Restriction Close
	$( '#restrictions_data' ).on( 'click', '.close_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .wc-metabox-content' ).hide();
		return false;
	} );

	// Country Restriction Show/Hide States
	$( '#restrictions_data' ).on( 'change', '.exclude_states select', function() {
		if ( $( this ).val() === 'specific' ) {
			$( this ).closest( '.exclude_states' ).parent().children( '.excluded_states' ).show();
		} else {
			$( this ).closest( '.exclude_states' ).parent().children( '.excluded_states' ).hide();
		}
		return false;
	} );

	// Select all/none
	$( '#restrictions_data' ).on( 'click', '.wccsp_select_all', function() {
		$( this ).closest( '.select-field' ).find( 'select option' ).attr( 'selected', 'selected' );
		$( this ).closest( '.select-field' ).find( 'select' ).trigger( 'change' );
		return false;
	} );

	$( '#restrictions_data' ).on( 'click', '.wccsp_select_none', function() {
		$( this ).closest( '.select-field' ).find( 'select option' ).removeAttr( 'selected' );
		$( this ).closest( '.select-field' ).find( 'select' ).trigger( 'change' );
		return false;
	} );

	// Restriction Add
	var checkout_restrictions_metabox_count = $( '.woocommerce_restrictions .woocommerce_restriction' ).length;

	$( '#restrictions_data' ).on( 'click', 'button.add_restriction', function () {

		// Check if restriction already exists and don't allow creating multiple rules if the restriction does not permit so

		var restriction_id = $( '#restrictions_data select.restriction_type' ).val();

		var applied_restrictions 	= $( '.woocommerce_restrictions' ).find( '.woocommerce_restriction_' + restriction_id );
		var restrictions 			= $( '.woocommerce_restrictions .woocommerce_restriction' );

		// If no option is selected, do nothing
		if ( restriction_id === '' ) {
			return false;
		}

		var block_params = {};

		if ( wc_restrictions_admin_params.is_wc_version_gte_2_3 == 'yes' ) {
			block_params = {
				message: 	null,
				overlayCSS: {
					background: '#fff',
					opacity: 	0.6
				}
			};
		} else {
			block_params = {
				message: 	null,
				overlayCSS: {
					background: '#fff url(' + wc_restrictions_admin_params.wc_plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 	0.6
				}
			};
		}

		$( '#restrictions_data' ).block( block_params );

		checkout_restrictions_metabox_count++;

		var data = {
			action: 		'woocommerce_add_checkout_restriction',
			post_id: 		wc_restrictions_admin_params.post_id,
			index: 			checkout_restrictions_metabox_count,
			restriction_id: restriction_id,
			applied_count: 	applied_restrictions.length,
			count: 			restrictions.length,
			security: 		wc_restrictions_admin_params.add_restriction_nonce
		};

		$.post( wc_restrictions_admin_params.wc_ajax_url, data, function ( response ) {

			if ( response.errors.length > 0 ) {

				window.alert( response.errors.join( '\n\n' ) );

			} else {

				$( '#restrictions_data .woocommerce_restrictions' ).append( response.markup );

				var added = $( '#restrictions_data .woocommerce_restrictions .woocommerce_restriction' ).last();

				added.wc_restrictions_scripts();

				added.data( 'conditions_count', 0 );
			}

			$( '#restrictions_data' ).unblock();
			$( '#restrictions_data' ).trigger( 'woocommerce_restriction_added', response );

		}, 'json' );

		return false;
	} );

	// Condition Add
	$( '.woocommerce_restrictions .woocommerce_restriction' ).each( function() {
		var conditions_count = $( this ).find( '.restriction_conditions .condition_row' ).length;

		$( this ).data( 'conditions_count', conditions_count );
	} );

	$( '#restrictions_data' ).on( 'click', 'button.add_condition', function () {

		var restriction          = $( this ).closest( '.woocommerce_restriction' );
		var conditions_markup    = restriction.find( '.restriction_conditions_append_data' ).data( 'conditions_markup' );
		var new_condition_markup = conditions_markup.new_condition_markup;

		var conditions_count     = parseInt( restriction.data( 'conditions_count' ) ) + 1;

		restriction.data( 'conditions_count', conditions_count );

		restriction.find( '.restriction_conditions tbody' ).append( new_condition_markup.replace( /%condition_index%/g, conditions_count ) );

		var added = restriction.find( '.restriction_conditions' ).last();

		added.wc_restrictions_scripts();

		return false;
	} );

	// Condition Remove
	$( '#restrictions_data' ).on( 'click', 'button.remove_conditions', function () {

		var remove = $( this ).closest( '.restriction_conditions' ).find( '.condition_row input.remove_condition:checked' );

		remove.closest( '.condition_row' ).remove();

		return false;
	} );

	// Condition Change
	$( '#restrictions_data' ).on( 'change', 'select.condition_type', function () {

		var condition_type    = $( this ).val();
		var condition         = $( this ).closest( '.condition_row' );
		var restriction       = $( this ).closest( '.woocommerce_restriction' );

		var conditions_count  = parseInt( restriction.data( 'conditions_count' ) ) + 1;

		restriction.data( 'conditions_count', conditions_count );

		var conditions_markup = restriction.find( '.restriction_conditions_append_data' ).data( 'conditions_markup' );
		var condition_content = conditions_markup[ condition_type ];

		condition.find( '.condition_content' ).html( condition_content.replace( /%condition_index%/g, conditions_count ) ).addClass( 'added' );

		var added = condition.find( '.added' );

		added.wc_restrictions_scripts();

		added.removeClass( 'added' );

		return false;
	} );

	// Init metaboxes
	init_wc_restrictions_metaboxes();

	function restrictions_row_indexes() {
		$( '.woocommerce_restrictions .woocommerce_restriction' ).each( function( index, el ){
			$( '.position', el ).val( parseInt( $(el).index( '.woocommerce_restrictions .woocommerce_restriction' ) ) );
		} );
	}


	function init_wc_restrictions_metaboxes() {

		// Initial order
		var woocommerce_checkout_restrictions = $( '.woocommerce_restrictions' ).find( '.woocommerce_restriction' ).get();

		woocommerce_checkout_restrictions.sort( function( a, b ) {
		   var compA = parseInt( $(a).attr( 'rel' ) );
		   var compB = parseInt( $(b).attr('rel') );
		   return ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
		} );

		$( woocommerce_checkout_restrictions ).each( function( idx, itm ) {
			$( '.woocommerce_restrictions' ).append(itm);
		} );

		// Component ordering
		$( '.woocommerce_restrictions' ).sortable( {
			items:'.woocommerce_restriction',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css( 'background-color','#f6f6f6' );
			},
			stop:function(event,ui){
				ui.item.removeAttr( 'style' );
				restrictions_row_indexes();
			}
		} );
	}

} );
