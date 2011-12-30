//testing
var $j = jQuery.noConflict();
$j(document).ready(function() {
	$j('.reelPlayer ul a').click(function(){
		console.log($j(this).attr('href'));
		$j(this).parent('ul').siblings('object').remove();
		$j(this).parent('ul').parent('div').prepend(htmlembed($j(this).attr('href'),640,360));
		$j(this).parent('ul').parent('div').siblings('.mediaTitle').html($j(this).children('img').first().attr('title'));
		return false;
	});
	
	
});

function htmlembed(url,width,height){
	
	html = "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\" height=\"256\" width=\"320\"><param name=\"src\" value=\""+url+"\"><param name=\"autoplay\" value=\"false\"><param name=\"type\" value=\"video/quicktime\" height=\""+height+"\" width=\""+width+"\"><embed src=\""+url+"\" height=\""+height+"\" width=\""+width+"\" autoplay=\"false\" type=\"video/quicktime\" pluginspage=\"http://www.apple.com/quicktime/download/\"></object>";

	return html;
}