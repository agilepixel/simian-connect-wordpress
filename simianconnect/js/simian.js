//QT posters
if(typeof(QTP) != "undefined" && typeof(QTP.Poster) != "undefined") {
    QTP.Poster.prototype.clickText = "Click To Play";

    QTP.Poster.prototype.attributes = {
        controller: 'false',
        autoplay: 'true',
        bgcolor: 'black',
        scale: 'tofit',
        postdomevents: 'true'
    };
}

// reel movie loading
var $j = jQuery.noConflict();

$j(document).ready(function() {

    $j('#playlist').on('click', 'div',

    function(event) {

        event.preventDefault();

        var $thumb = $j(this).parent();

        $j('#playlist .selected.hoverOver').removeClass('selected').removeClass('hoverOver');

        $thumb.find('.simian_content').addClass('selected').addClass('hoverOver');

        // get the main video player id
        var $reel_id = $thumb.find('a').attr('rel');
        var $p = $j("." + $reel_id);

        var $m = $j("<div />").attr('id', $reel_id + "_mov");
        $p.find('.current_video_player').empty().append($m);

        // grab the dimensions from the dynamic wp localization
        var $dim = window[$reel_id + '_sizes'][$thumb.attr('class')];

        var $img = $thumb.find('img');

        qtEmbed($reel_id + "_mov", $thumb.find('a').attr('href'), $dim['width'], $dim['height'], "false", $img.attr('src'));

        $p.find('.current_video_title').html($img.attr('title'));

        return false;
    });

    $j('#playlist').on('mouseenter', 'div.thumb', function() {
        $j(this).siblings('.thumb_title').addClass('hoverOver');
    });

    $j('#playlist').on('mouseleave', 'div.thumb', function() {
        if(!$j(this).siblings('dt').hasClass('selected')) {
            $j(this).siblings('.thumb_title').removeClass('hoverOver');
        }
    });
    $j('.current_video_player').on('qt_ended', simian_next_playlist);

});

function qtEmbed($dom_id, $src, $width, $height, $autostart, $poster) {

    var $main = $j("#" + $dom_id);

    if(typeof(QTP) != "undefined" && typeof(QTP.Poster) != "undefined" && $poster !== 'false') {

        var $parent = $j('#' + $dom_id).parent();
        var $new = "<a href=\"" + $src + "\" rel=\"qtposter\" jscontroller=\"false\"><img src=\"" + $poster + "\" width=\"" + $width + "\" height=\"" + $height + "\" /></a>";

        $main.html($new);

        QTP.Poster.instantiatePosters();
        if($autostart === "true") {
            $parent.children('.QTP').click();
        }

    } else if(typeof(QT) != "undefined") {

        var $newqt = QT.GenerateOBJECTText_XHTML($src, $width, $height, '', 'scale', 'tofit', 'autostart', $autostart, 'EnableJavaScript', 'True', 'postdomevents', 'True', 'emb#name', $dom_id + '_embed');

        $main.html($newqt);

    } else {

        var flashvars = {
            src: $src,
            autostart: 'true',
            themeColor: '0395d3',
            mode: 'sidebyside',
            scaleMode: 'fit',
            frameColor: '333333',
            fontColor: 'cccccc',
            link: '',
            embed: ''
        };
        var params = {
            allowFullScreen: 'true'
        };
        var attributes = {
            id: $dom_id,
            name: 'myPlayer'
        };

        swfobject.embedSWF(jw_swf, 'myPlayerGoesHere', $width, $height, '9.0.0', 'expressInstall.swf', flashvars, params, attributes);

        //jwplayer($dom_id).setup({
        //    autostart: false,
        //    file: $src,
        //    flashplayer: jw_swf,
        //    height: $height,
        //   width: $width,
        //    events: {
        //        onComplete: function () {
        //            simian_next_playlist(null, $j('#' + this.id).parents('.current_video_player'));
        //       }
        //    }
        //});
    }

}

function simian_next_playlist(event, origin) {
    if(autoplay_playlist == 1) {
        if(!origin) {
            origin = $j(this);
        }
        var nextItem = origin.parent('.current_video').siblings('#playlist').find('.selected').parent('dl').next();
        if(nextItem.length > 0) {
            nextItem.children('div').click();
        } else {
            origin.parent('.current_video').siblings('#playlist').find('div.thumb').first().click();
        }
    }
}