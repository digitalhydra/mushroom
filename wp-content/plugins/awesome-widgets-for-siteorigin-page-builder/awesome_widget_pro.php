<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$plugin_dir_url =  plugin_dir_url( __FILE__ );
?>
<style>
.premium-box {width: 100%;}
.premium-box-head {background: #eae8e7; width: 100%; height:500px; text-align: center;}
.pho-upgrade-btn {display: block; text-align: center;}
.pho-upgrade-btn a{display: inline-block;  margin-top: 75px;}
.pho-upgrade-btn a:focus {outline: none; box-shadow: none; }
.main-heading  {text-align: center; background: #fff; margin-bottom: -70px;}
.main-heading img {margin-top: -200px;}

.premium-box-container {margin: 0 auto;}
.premium-box-container .description {text-align: center; display: block; padding: 35px 0;}
.premium-box-container .description:nth-child(odd) {background: #fff;}
.premium-box-container .description:nth-child(even) {background: #eae8e7;}

.premium-box-container .pho-desc-head {width: 768px; margin: 0 auto; position: relative;}
.premium-box-container .pho-desc-head:after {background:url(<?php echo $plugin_dir_url; ?>assests/images/head-arrow.png) no-repeat;
 position: absolute; right: -30px; top: -6px; width: 69px; height: 98px; content: "";} 

.premium-box-container .pho-desc-head h2 {color: #02c277; font-weight: bolder; font-size: 28px; text-transform: capitalize;margin: 0; line-height:35px;}
.pho-plugin-content {margin: 0 auto; width: 768px; overflow: hidden;}
.pho-plugin-content p {line-height: 32px; font-size: 18px; color: #212121; }
.pho-plugin-content img {width: auto; max-width: 100%;}
.description .pho-plugin-content ol { margin: 0; padding-left: 25px; text-align: left;}
.description .pho-plugin-content ol li {font-size: 16px; line-height: 28px; color: #212121; padding-left: 5px;}
.description .pho-plugin-content .pho-img-bg { width: 750px; margin: 0 auto; border-radius: 5px 5px 0 0; 
padding: 70px 0 40px; height: auto;}
.premium-box-container .description:nth-child(odd) .pho-img-bg {background: #eaeaef url(<?php echo $plugin_dir_url; ?>assests/images/image-frame-odd.png) no-repeat 100% top;}
.premium-box-container .description:nth-child(even) .pho-img-bg {background: #eaeaef url(<?php echo $plugin_dir_url; ?>assests/images/image-frame-even.png) no-repeat 100% top;}

</style>

<div class="premium-box">

    <div class="premium-box-head">
        <div class="pho-upgrade-btn">
        <a href="https://www.phoeniixx.com/product/awesome-widgets-siteorigin-page-builder/" target="_blank"><img src="<?php echo $plugin_dir_url; ?>assests/images/premium-btn.png" /></a>
		<a href="https://phoeniixx.com/demo/woocommerce/siteorigin-widgets/all-in-one-widget-1/" target="_blank"><img src="<?php echo $plugin_dir_url; ?>assests/images/demo-btn.png" /></a>
        </div>
    </div>
    <div class="main-heading"><h1><img src="<?php echo $plugin_dir_url; ?>assests/images/premium-head.png" /></h1></div>

        <div class="premium-box-container">
		
				<div class="description">
                <div class="pho-desc-head"><h2>All in One Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>You can show Top Seller, Featured, New Arrival, Sale Off and Category based products in one tab.</p>
                        <div class="pho-img-bg">
								<img src="<?php echo $plugin_dir_url; ?>assests/images/all-in-one-wid.png" />
                        </div>
                    </div>
				</div> <!-- description end -->
				
				<div class="description">
                <div class="pho-desc-head"><h2>Category Based New Arrival Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Option to show category based widget, Enable/Disable View All Button, Advanced Tab Styling. Option to show Title and Description.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/new-arrival-wid.png" />
                        </div>
                    </div>
				</div> <!-- description end -->
			
            <div class="description">
                <div class="pho-desc-head"><h2>Category Based Top Seller Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Option to show category based widget, Enable/Disable View All Button, Advanced Tab Styling. Option to show Title and Description.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/top-seller-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
            
            <div class="description">
                <div class="pho-desc-head"><h2>Category Based Sale Off Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Option to show category based widget, Enable/Disable View All Button, Advanced Tab Styling. Option to show Title and Description.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/sale-off-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
            
            <div class="description">
                <div class="pho-desc-head"><h2>Category Based Featured Product Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Option to show category based widget, Enable/Disable View All Button, Advanced Tab Styling. Option to show Title and Description.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/featured-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Category Based Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Option to show category based widget, Enable/Disable View All Button, Advanced Tab Styling. Option to show Title and Description.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/category-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Flip Box Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Multiple Hover Effects options to create flip box where you can add Image, Title, Description and Button.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/flip-box-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Info Box Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>You can Show/Hide Title, Description and Button.Option to float Images and Content to Left or Right.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/info-box-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Special Hover Effects Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Multiple options to create Special Hover Effects.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/special-hover-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Interactive Banner Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Multiple options to create Banner with advance CSS options.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/interactive-banner-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Modal Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>Show any type of content in Modal Widget like Image, Gallery, Content, Table, Contact Form and many more also you can use shortcodes.</p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/modal-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
			
			<div class="description">
                <div class="pho-desc-head"><h2>Multi Color Headline Widget</h2></div>
                    <div class="pho-plugin-content">
                        <p>You can set headline of multi colors with multiple CSS options. </p>
                        <div class="pho-img-bg">
                        <img src="<?php echo $plugin_dir_url; ?>assests/images/headline-wid.png" />
                        </div>
                    </div>
            </div> <!-- description end -->
            
        </div> <!-- premium-box-container end -->
        
        <div class="pho-upgrade-btn">
        <a href="https://www.phoeniixx.com/product/awesome-widgets-siteorigin-page-builder/" target="_blank"><img src="<?php echo $plugin_dir_url; ?>assests/images/premium-btn.png" /></a>
		<a href="https://phoeniixx.com/demo/woocommerce/siteorigin-widgets/all-in-one-widget-1/" target="_blank"><img src="<?php echo $plugin_dir_url; ?>assests/images/demo-btn.png" /></a>
        </div>

</div> <!-- premium-box end -->