<?php
/**
 * Template for the modals
 */
?>

<script type="text/template" id="tmpl-missing-attributes">
	<table>
		<tbody>
			{{#each attr}}
			<tr>
				<td>{{name}}</td>
				<td>
					<select data-label="{{name}}" data-taxonomy="{{slug}}" class="attribute_{{slug}}" >
						<option value=""><?php _e('Choose an option', 'wc_point_of_sale'); ?></option>
						{{missingAttributesOptions}}
					</select>
				</td>
			</tr>
  			{{/each}}
		</tbody>
	</table>
</script>

<script type="text/template" id="tmpl-add-custom-item-meta">
    {{#each this}}
	<tr>
        <td class="meta_label"><input type="text" class="meta_label_value" value="{{meta_key}}"></td>
        <td class="meta_attribute"><input type="text"  class="meta_attribute_value" value="{{meta_v}}"></td>
        <td class="remove_meta"><span href="#" data-tip="<?php _e('Remove', 'wc_point_of_sale'); ?>" class="remove_custom_product_meta tips"></span></td>
    </tr>
    {{/each}}
</script>

<script type="text/template" id="tmpl-form-add-customer">
    <input type="hidden" id="customer_details_id" value="{{id}}">
    <div class="col-1">
        <div class="woocommerce-billing-fields">
            <h3><?php _e( 'Billing Details', 'woocommerce' ); ?></h3>
        	<?php 
            $checkout = new WC_Checkout();

        	foreach ( $checkout->checkout_fields['billing'] as $key => $field ) :
        		$value = str_replace('billing_', 'billing_address.', $key);
        		woocommerce_form_field( $key, $field, '{{'.$value.'}}' );
    		endforeach; 
    		?>
        </div>        
    </div>
    <div class="col-2">
        <div class="woocommerce-shipping-fields">
            <?php
            	$ship_to_different_address = $checkout->get_value( 'ship_to_different_address' );
            ?>
            <h3 id="ship-to-different-address">
                <?php _e( 'Shipping Details', 'woocommerce' ); ?>
            </h3>
            <div class="shipping_address">
                    <p class="billing-same-as-shipping">
                        <a id="billing-same-as-shipping" class="tips billing-same-as-shipping" data-tip="<?php _e( 'Copy from billing', 'woocommerce' ); ?>"></a>
                    </p>
                    <?php 
                    	foreach ( $checkout->checkout_fields['shipping'] as $key => $field ) :
                    		$value = str_replace('shipping_', 'shipping_address.', $key);
                    		woocommerce_form_field( $key, $field, '{{'.$value.'}}' );
                		endforeach; 
            		?>
                </div>
        </div>
    </div>
    <div class="col-3">        
    	<h3 id="create_new_account">
            <input class="input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true) ?> type="checkbox" value="1" />
            <label for="createaccount" class="checkbox"><?php _e( 'Create an account?', 'woocommerce' ); ?></label>
        </h3>
        <div class="woocommerce-additional-fields">

            <?php if ( ! WC()->cart->needs_shipping() || WC()->cart->ship_to_billing_address_only() ) : ?>

                <h3><?php _e( 'Additional Information', 'woocommerce' ); ?></h3>

            <?php endif; ?>

            <?php foreach ( $checkout->checkout_fields['order'] as $key => $field ) : if( $key == 'order_comments') continue; ?>

                <?php woocommerce_form_field( $key, $field, '{{'.$key.'}}' ); ?>

            <?php endforeach; ?>

        </div>
    </div>                    
    <div class="clear"></div>
</script>
<script type="text/template" id="tmpl-custom-shipping-shippingaddress">
    <?php
    $checkout = new WC_Checkout();
    foreach ( $checkout->checkout_fields['shipping'] as $key => $field ) : 
        $value = str_replace('shipping_', 'shipping_address.', $key);
        woocommerce_form_field( 'custom_'.$key, $field, '{{'.$value.'}}' ); 
    endforeach; ?>
</script>

<script type="text/template" id="tmpl-custom-shipping-method-title-price">
    <tr>
        <td class="shipping_title"><input type="text" id="custom_shipping_title" value="{{title}}"></td>
        <td class="shipping_price"><input type="text" id="custom_shipping_price" value="{{price}}"></td>
    </tr>
</script>

<script type="text/template" id="tmpl-retrieve-sales-orders-list">
            <table class="wp-list-table widefat fixed striped posts retrieve_sales_nav">
                <tbody>
                {{#each this}}
                <tr class="iedit author-self level-0 post-{{id}} type-shop_order status-wc-pending post-password-required hentry">
                    <td class="order_status column-order_status">{{{order_status}}}</td>
                    <td class="order_title column-order_title has-row-actions column-primary">
                        {{displayOrderTitle}}
                    </td>
                    <td class="order_items column-order_items">
                        <a class="show_order_items" href="#">
                            {{getCountItems}}
                        </a>
                        <table cellspacing="0" class="order_items" style="display: none;">
                            <tbody>
                                {{order_items_list}}                                
                            </tbody>
                        </table>
                    </td>
                    <td class="shipping_address column-shipping_address">
                        {{{formatted_shipping_address}}}
                    </td>
                    <td class="customer_message column-customer_message">{{{customer_message}}}</td>
                    <td class="order_notes column-order_notes">{{{order_notes}}}</td>
                    <td class="order_date column-order_date">{{{order_date}}}</td>
                    <td class="order_total column-order_total">{{{order_total}}}</td>
                    <td class="crm_actions column-crm_actions"><p><a href="{{this.id}}" class="button load_order_data"><?php _e('Load Order', 'wc_point_of_sale'); ?></a></p></td>
                </tr>
                {{/each}}
                </tbody>
            </table>    
</script>

<script type="text/template" id="tmpl-retrieve-sales-orders-not-found">
    <table class="wp-list-table widefat fixed striped posts retrieve_sales_nav">
        <tbody>
            <tr class="no-items"><td colspan="9" class="colspanchange"><?php _e('No Orders found', 'wc_point_of_sale'); ?></td></tr>
        </tbody>
    </table>
</script>

<script type="text/template" id="tmpl-retrieve-sales-orders-pager">
    <div class="tablenav">
        <div class="tablenav-pages">
            <span class="displaying-num">{{items}}</span>
        {{#if count}}
            <span class="pagination-links">
                {{#if urls.a}}
                <a href="#" class="first-page" onclick="{{{urls.a}}}">
                    <span class="screen-reader-text">First page</span>
                    <span aria-hidden="true">«</span>
                </a>
                {{else}}
                    <span aria-hidden="true" class="tablenav-pages-navspan">«</span>
                {{/if}}
                {{#if urls.b}}
                <a href="#" class="prev-page" onclick="{{{urls.b}}}">
                    <span class="screen-reader-text">Previous page</span>
                    <span aria-hidden="true">‹</span>
                </a>
                {{else}}
                    <span aria-hidden="true" class="tablenav-pages-navspan">‹</span>
                {{/if}}
            
                <span class="paging-input"><label class="screen-reader-text" for="current-page-selector">Current Page</label>
                    <input type="text" aria-describedby="table-paging" size="1" value="{{currentpage}}" id="current-page-selector" class="current-page" data-count="{{count}}" data-reg_id="{{reg_id}}" >
                    of <span class="total-pages">{{countpages}}</span>
                </span>

                {{#if urls.c}}
                <a href="#" class="next-page" onclick="{{{urls.c}}}">
                    <span class="screen-reader-text">Next page</span>
                    <span aria-hidden="true">›</span>
                </a>
                {{else}}
                    <span aria-hidden="true" class="tablenav-pages-navspan">›</span>
                {{/if}}
                {{#if urls.d}}
                <a href="#" class="last-page" onclick="{{{urls.d}}}">
                    <span class="screen-reader-text">Last page</span>
                    <span aria-hidden="true">»</span>
                </a>
                {{else}}
                    <span aria-hidden="true" class="tablenav-pages-navspan">»</span>
                {{/if}}
            </span>
        {{/if}}
        </div>
    </div>
</script>

<script type="text/template" id="tmpl-confirm-box-content">
    {{#if title}}
    <h3>{{{title}}}</h3>
    {{/if}}
    {{#if content}}
    <div>
        {{{content}}}
    </div>
    {{/if}}
    <div class="wrap-button">
        <button class="button wp-button-large" type="button" id="cancel-button">
            <?php _e('Cancel', 'wc_point_of_sale'); ?>
        </button>
        <button class="button button-primary wp-button-large" type="button" id="confirm-button">
            <?php _e('Ok', 'wc_point_of_sale'); ?>
        </button>
    </div>
</script>
<script type="text/template" id="tmpl-confirm-void-register">
    <div class="sa-icon sa-warning pulseWarning" style="display: block;">
      <span class="sa-body pulseWarningIns"></span>
      <span class="sa-dot pulseWarningIns"></span>
    </div>
    <h2><?php _e( "Are you sure you want to clear all fields and start from scratch?", 'wc_point_of_sale'); ?></h2>
</script>
<script type="text/template" id="tmpl-prompt-email-receipt">
        <div class="sa-icon sa-success" style="display: block;">
          <span class="dashicons dashicons-email-alt"></span>
        </div>
        <h2><?php _e( "Do you want to email the receipt?", 'wc_point_of_sale'); ?></h2>
        <p><?php _e( "Enter customer email", 'wc_point_of_sale'); ?></p>        
</script>