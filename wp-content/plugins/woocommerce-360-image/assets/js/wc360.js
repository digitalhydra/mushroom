window.onload = init;

var product1;
function init(){

var all_images = wc360_vars.images;

var image_array = jQuery.parseJSON( all_images );

product1 = jQuery('.threesixty').ThreeSixty({
totalFrames: image_array.length,
currentFrame: 1,
endFrame: image_array.length,
framerate: wc360_vars.speed,
imgList: '.threesixty_images',
progress: '.spinner',
filePrefix: '',
height: wc360_vars.height,
width: wc360_vars.width,
navigation: wc360_vars.navigation,
imgArray: image_array,
responsive: wc360_vars.responsive,
drag: wc360_vars.drag,
disableSpin: wc360_vars.spin,
plugins: ['ThreeSixtyFullscreen'],
});

}
