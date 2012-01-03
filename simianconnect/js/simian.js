//QT posters
if (typeof(QTP) != "undefined" && typeof(QTP.Poster) != "undefined") { 
QTP.Poster.prototype.clickText = "Click To Play";

QTP.Poster.prototype.attributes = {
		controller: 'false',
		autoplay: 'true', 
		bgcolor: 'black', 
		scale: 'tofit',
		postdomevents: 'true'
	};
}

//reel movie loading
var $j = jQuery.noConflict();

$j(document).ready(function() {

	$j('ul.reelList').on('click','a',function() {
	
		$reel_id = $j(this).attr('rel');
		$p = $j("#"+$reel_id);
		
		$m = $j("<div />").attr('id', $reel_id +"_mov");
		
		$p.find('div.reelVideo').empty().append($m);
		
		qtEmbed($reel_id +"_mov",$j(this).attr('href'),480,240,"true");
		
		$p.find('.mediaTitle').html($j(this).children('img').first().attr('title'));
		
		return false;
	});
	
	
});

function qtEmbed($dom_id,$src,$width,$height,$autostart){

	if (typeof(QT) != "undefined") { 
	
		var $new = QT.GenerateOBJECTText_XHTML($src, $width, $height, '','scale','tofit', 'autostart',$autostart);
		
		$($dom_id).replace($new);
		
	}
}