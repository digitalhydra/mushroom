<div class="md-modal md-dynamicmodal" id="modal-lost-connection">
    <div class="md-content">
        <div>
            <div class="sa-icon sa-warning pulseWarning" style="display: block;">
		      <span class="dashicons dashicons-admin-plugins pulseWarningInsColor"></span>
		    </div>
		    <p><?php _e( "Lost Connection to Server", 'wc_point_of_sale'); ?></p>
		    <h2>
		    	<?php _e( "Reconnecting", 'wc_point_of_sale'); ?>
		    	<span class="dot-one">.</span>
		    	<span class="dot-two">.</span>
		    	<span class="dot-three">.</span>
	    	</h2>
        </div>
    </div>
</div>

<div class="md-modal md-dynamicmodal" id="modal-reconnected-successfuly">
    <div class="md-content">
        <div>
            <div class="sa-icon sa-success" style="display: block;">
		      <span class="dashicons dashicons-admin-plugins"></span>
		    </div>
		    <h2><?php _e( "Connected", 'wc_point_of_sale'); ?></h2>
	        <button class="button button-primary wp-button-large md-close" type="button" >
	            <?php _e('Continue', 'wc_point_of_sale'); ?>
	        </button>
        </div>
    </div>
</div>