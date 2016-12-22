/**
 * yith-wcas-admin.js
 *
 * @author Yithemes
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2.3
 */

jQuery(document).ready(function($) {
    "use strict";

    var select          = $( document).find( '.yith-wcas-chosen' );

    select.each( function() {
        $(this).chosen({
            width: '350px',
            disable_search: true,
            multiple: true
        })
    });

    // show category list
    var show_category_list = $('#yith_wcas_show_category_list'),
        search_by_sku = $('#yith_wcas_search_by_sku'),
        row_list = $('#yith_wcas_show_category_list_all').closest('tr'),
        search_by_sku_next_row = $('#yith_wcas_search_by_sku_variations').closest('tr');

    var show_row =  function( checkbox_el, row_el){
        if ( checkbox_el.is(":checked")){
            row_el.show();
        }else{
            row_el.hide();
        }
    };

    show_row( show_category_list, row_list );
    show_row( search_by_sku, search_by_sku_next_row );
    
    show_category_list.on('click', function(){
        show_row( show_category_list, row_list );
    });

    search_by_sku.on('click', function(){
        show_row( search_by_sku, search_by_sku_next_row );
    });

});