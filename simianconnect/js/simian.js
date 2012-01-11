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

	$j('ul.reelList').on('click','li',function() {

		$j('ul.reelList .selected.hoverOver').removeClass('selected').removeClass('hoverOver');
		$j(this).children('.overlay').addClass('selected').addClass('hoverOver');

		var $reel_id = $j(this).children('a').attr('rel');

		var $p = $j("#"+$reel_id);

		var $m = $j("<div />").attr('id', $reel_id +"_mov");

		var mediaClass = $j(this).attr('class').replace('simian_media_','sim_dim');

		$p.find('div.reelVideo .reelContainer').empty().append($m);

		qtEmbed($reel_id +"_mov",$j(this).children('a').attr('href'),eval(mediaClass).width,eval(mediaClass).height,"true",$j(this).find('img').attr('src'));

		$p.find('.mediaTitle').html($j(this).siblings('img').first().attr('title'));

		return false;
	});

	$j('ul.reelList').on('mouseenter','li',function() {
		$j(this).children('.overlay').addClass('hoverOver');
	});

	$j('ul.reelList').on('mouseleave','li',function() {
		if(!$j(this).children('.overlay').hasClass('selected')){
			$j(this).children('.overlay').removeClass('hoverOver');
		}
	});


});

function qtEmbed($dom_id,$src,$width,$height,$autostart,$poster){

	if (typeof(QTP) != "undefined" && typeof(QTP.Poster) != "undefined") {
		
		var $parent = $j('#'+$dom_id).parent();
		var $new = "<a href=\""+$src+"\" rel=\"qtposter\" jscontroller=\"false\"><img src=\""+$poster+"\" width=\""+$width+"\" height=\""+$height+"\" /></a>";
		$($dom_id).replace($new);
		QTP.Poster.instantiatePosters();
		if($autostart==="true"){
			$parent.children('.QTP').click();
		}
		
	} else if (typeof(QT) != "undefined") { 

		var $new = QT.GenerateOBJECTText_XHTML($src, $width, $height, '','scale','tofit', 'autostart',$autostart);
		$($dom_id).replace($new);
		QTP.Poster.instantiatePosters();

	} else {
		
		console.log("Quicktime not loaded!!!");
		
	}
}
