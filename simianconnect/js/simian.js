

//QT posters
if (typeof(QTP) != "undefined" && typeof(QTP.Poster) != "undefined") { 
QTP.Poster.prototype.clickText = "Click To Play";
}

//reel movie loading
var $j = jQuery.noConflict();

$j(document).ready(function() {

	$j('ul.reelList').on('click','a',function() {
	
		$reel_id = $j(this).attr('rel');
		$p = $j("#"+$reel_id);
		
		$m = $j("<div />").attr('id', $reel_id +"_mov");
		
		$p.find('div.reelVideo').empty().append($m);
		
		qtEmbed($reel_id +"_mov",$j(this).attr('href'),640,360,"true");
		
		$p.find('.mediaTitle').html($j(this).children('img').first().attr('title'));
		
		return false;
	});
	
	
});

function qtEmbed($dom_id,$src,$width,$height,$autostart){

	//$j(document).ready(function(){
	
	if (typeof(QT) != "undefined") { 
	
		var $new = QT.GenerateOBJECTText_XHTML($src, $width, $height, '','scale','tofit', 'autostart',$autostart);
		
		$($dom_id).replace($new);
		
	} else {
	
		console.log(QT);
	
	}
	
	//});

}

function htmlembed(url,width,height){
	
	html = "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\" height=\"256\" width=\"320\"><param name=\"src\" value=\""+url+"\"><param name=\"autoplay\" value=\"false\"><param name=\"type\" value=\"video/quicktime\" height=\""+height+"\" width=\""+width+"\"><embed src=\""+url+"\" height=\""+height+"\" width=\""+width+"\" autoplay=\"false\" type=\"video/quicktime\" pluginspage=\"http://www.apple.com/quicktime/download/\"></object>";

	return html;
}