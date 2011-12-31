//QT posters
if (typeof(QTP) != "undefined") { 
QTP.Poster.prototype.clickText = "Click To Play";
}

//reel movie loading
var $j = jQuery.noConflict();

$j(document).ready(function() {

	$j('.reelPlayer ul a').click(function(){
	
		//console.log($j(this).attr('href'));
		
		var $p = $j(this).parent('ul');
		$p.siblings('object').remove();
		
		$p.parent('div').prepend(qtEmbed($j(this).attr('href'),640,360));
		
		$p.parent('div').siblings('.mediaTitle').html($j(this).children('img').first().attr('title'));
		
		return false;
	});
	
	
});

function qtEmbed($dom_id,$src,$width,$height){

	//$j(document).ready(function(){
	
	if (typeof(QT) != "undefined") { 
	
		var $new = QT.GenerateOBJECTText_XHTML($src, $width, $height, '','scale','tofit');
		
		//console.log($($dom_id));
		
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