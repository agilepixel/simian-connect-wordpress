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

		var $reel_id = $j(this).attr('rel');

		var $p = $j("#"+$reel_id);

		var $m = $j("<div />").attr('id', $reel_id +"_mov");

		var mediaClass = $j(this).parent('li').attr('class').replace('simian_media_','sim_dim');

		$p.find('div.reelVideo').empty().append($m);

		qtEmbed($reel_id +"_mov",$j(this).attr('href'),eval(mediaClass).width,eval(mediaClass).height,"true");

		$p.find('.mediaTitle').html($j(this).children('img').first().attr('title'));

		return false;
	});

	$j('ul.reelList').on('mouseenter','a',function() {
		$j(this).siblings('.overlay').addClass('hoverOver');
	});

	$j('ul.reelList').on('mouseleave','a',function() {
		if(!$j(this).siblings('.overlay').hasClass('selected')){
			$j(this).siblings('.overlay').removeClass('hoverOver');
		}
	});


});

function qtEmbed($dom_id,$src,$width,$height,$autostart){

	if (typeof(QT) != "undefined") { 

		var $new = QT.GenerateOBJECTText_XHTML($src, $width, $height, '','scale','tofit', 'autostart',$autostart);

		$($dom_id).replace($new);

	}
}