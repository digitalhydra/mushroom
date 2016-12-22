<div class="md-modal md-dynamicmodal md-menu" id="modal-order_discount">
    <div class="media-frame-menu">
        <div class="media-menu">
            <a href="#discount_tab" class="active discount_modal"><?php _e('Discount', 'woocommerce'); ?></a>
            <a href="#coupon_tab" class="coupon_modal"><?php _e('Coupon', 'woocommerce'); ?></a>
        </div>                
    </div>
    <div class="md-content">        
        <div id="discount_tab" class="discount_section popup_section" style="display: block;">
            <h1><?php _e('Discount', 'wc_point_of_sale'); ?><span class="md-close"></span></h1>
            <div class="media-frame-wrap">

                    <input type="hidden" id="order_discount_prev" value="<?php echo ($order->get_total_discount() > 0 ) ? $order->get_total_discount() : ''; ?>">

                    <div id="inline_order_discount"></div>

                    <input type="hidden" id="order_discount_symbol" value="currency_symbol">

            </div>
            <div class="wrap-button">
                <button class="button wp-button-large md-close" type="button"><?php _e('Back', 'wc_point_of_sale'); ?></button>
                <button class="button button-primary wp-button-large alignright" type="button" id="save_order_discount"><?php _e('Add Discount', 'wc_point_of_sale'); ?></button>
            </div>
        </div>
        <div id="coupon_tab" class="discount_section popup_section">
            <h1><?php _e('Coupon', 'wc_point_of_sale'); ?><span class="md-close"></span></h1>
            <div class="media-frame-wrap">
                <input id="coupon_code" class="input-text" type="text" placeholder="<?php _e('Coupon code', 'wc_point_of_sale'); ?>" value="" name="coupon_code">
                <button class="button" type="button" name="apply_coupon" id="apply_coupon_btn"><?php _e('Apply Coupon', 'wc_point_of_sale'); ?></button>

                <div class="messages"></div>
            </div>
            <div class="wrap-button">
                <button class="button wp-button-large md-close" type="button"><?php _e('Back', 'wc_point_of_sale'); ?></button>
            </div>

        </div>
    </div>
</div>