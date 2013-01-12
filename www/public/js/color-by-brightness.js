/**
 * load images on showing an message entry
 * http://24ways.org/2010/calculating-color-contrast/
 */
(function($){
$.fn.colorByBrightness = function() {
    var color = $(this).css("background-color");
	if(color==null)
		return;
	color = color.match(/\d+/g);
	var r = parseInt(color[0]);
	var g = parseInt(color[1]);
	var b = parseInt(color[2]);
	var yiq = ((r*299)+(g*587)+(b*114))/1000;
	return (yiq >= 128) ? 'black' : 'white';
}
})(jQuery);