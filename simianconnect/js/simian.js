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

	$j('dl.playlist').on('click','dd',function(event) {
	
		event.preventDefault();
		
		var $thumb = $j(this).parent();

		$j('dl.playlist .selected.hoverOver').removeClass('selected').removeClass('hoverOver');
		$j(this).children('.overlay').addClass('selected').addClass('hoverOver');
		
		//get the main video player id
		var $reel_id = $j(this).children('a').attr('rel');
		var $p = $j("#"+$reel_id);

		var $m = $j("<div />").attr('id', $reel_id +"_mov");
		$p.find('.current_video_player').empty().append($m);
		
		//grab the dimensions from the dynamic wp localization
		var $dim = window[$reel_id+'_sizes'][$thumb.attr('class')];
		
		var $img = $j(this).find('img');
		
		qtEmbed($reel_id +"_mov",$j(this).children('a').attr('href'),$dim['width'],$dim['height'],"true",$img.attr('src'));

		$p.find('.current_video_title').html($img.attr('title'));


		return false;
	});

	$j('dl.playlist').on('mouseenter','li',function() {
		$j(this).children('.overlay').addClass('hoverOver');
	});

	$j('dl.playlist').on('mouseleave','li',function() {
		if(!$j(this).children('.overlay').hasClass('selected')){
			$j(this).children('.overlay').removeClass('hoverOver');
		}
	});


});

function qtEmbed($dom_id,$src,$width,$height,$autostart,$poster){


	if(typeof(QTP) != "undefined" && typeof(QTP.Poster) != "undefined" && $poster != 'false') {

		
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

	} else {
		
		console.log("Quicktime not loaded!!!");
		
	}
}
