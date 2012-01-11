<?php
/*
 Plugin Name: Simian Connect
 Plugin URI: http://thecodepharmacy.co.uk/simian-connect/
 Description: Access all your Simian media and easily add them to your posts. Uses the Simian XML API.
 Version: 0.3
 Author: The Code Pharmacy
 Author URI: http://thecodepharmacy.co.uk/
 License: Proprietary
 */

/*
 Copyright (c) 2011 The Code Pharmacy
 All Rights Reserved.
 It is unlawful to reproduce, copy or otherwise reuse this software
 without express written permission of the author
 */

require_once('library/config/config.php');

$simian_connect_version = "0.4";
add_action('plugins_loaded', 'simian_update_db_check');

add_action('admin_init','simian_admin_init');
add_action('admin_menu', 'simian_menu');

// init process for button control
add_action('init', 'simian_addbuttons');

add_action('wp_enqueue_scripts', 'simian_call_requires');

add_action('wp_ajax_simian_ajax_get_reel', 'simian_ajax_get_reel');
add_action('wp_ajax_simian_select_reel', 'simian_ajax_select_reel');
add_shortcode( 'scompanyreel', 'simiancreel_tag_func' );
add_shortcode( 'swebreel', 'simianwreel_tag_func' );
register_activation_hook(__FILE__,'simian_install');
register_activation_hook(__FILE__, 'simian_settings_init');

//$wpdb->show_errors();

function simian_menu(){

	add_menu_page(__('Simian Connect'), __('Simian Connect'), "manage_options", "simian_connect","simian_client_config",plugins_url( 'media/simian-icon-16.png' , __FILE__ ));

	add_submenu_page('simian_connect',__('Simian Connect'),__('Reel Cache'),'manage_options', 'simian-cache-run', 'simian_cache_page');
	add_submenu_page('simian_connect',__('Simian Connect'),__('Debug'),'manage_options', 'simian-connect-config', 'simian_config');

}

function simian_config() {

	global $wpdb;

	$html = "";

	$html .= '<div class="wrap">';
	$html .= '<h2>Simian Connect Debug</h2>';
	$html .= '</div>';

	//function checks
	$html .= "<p>Checking for SimpleXML... ";
	if(!function_exists('simplexml_load_file')){
		$html .= "SimpleXML not available</p>";
	} else {
		$html .= "Success!</p>";
	}
	$html .= "<p>Checking for cURL... ";
	if(!function_exists('curl_init')){
		$html .= "cURL not available</p>";
	}
	else {
		$html .= "Success!</p>";
	}
	$html .= "<p>Checking for JSON... ";
	if(!function_exists('json_encode')){
		$html .= "JSON not available</p>";
	} else {
		$html .= "Success!</p>";
	}

	/*

	//testing into getting a quicker media list
	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com";

	$simian_post = array();
	$simian_post['auth_token'] = get_option('simian_client_api_key');
	$simian_post['section'] = "media";
	$simian_post['start_record'] = "0";
	$simian_post['limit'] = "10";

	$ch = curl_init($simian_url . "/v2/api/main/select_all");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $simian_post);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);

	if(!($reel = simplexml_load_string($response))){

	$html .= "API XML parse error";


	} else {

	foreach($reel as $media){

	$html .= "<p>" . $media->title . "</p>";

	}

	$html .= "<code>" . htmlentities($response). "</code>";

	}
	*/

	echo $html;

}

function simian_cache_page(){

	$html = "";

	$html .= '<div class="wrap">';
	$html .= '<h2>Simian Connect Reel Cache</h2>';
	$html .= '</div>';

	$html .= '<form id="simianCacheForm" method="post" action="#">';
	$html .= '<input name="simianReelMax" type="text" id="simianReelMax" value="250" maxlength="3" class="regular-text">';
	$html .= '<span class="description">Maximum Reel ID to search for.</span>';
	$html .= '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Start Caching"></p>';
	$html .= '</form>';
	$html .= '<p id="simianCacheStatus">&nbsp</p>';

	echo $html;

}

function simian_ajax_select_reel() {

	global $wpdb;

	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com" . "/assets/";
	
	$html = "";
	
	$html .= "<p>Click on a reel from the selection below to insert it into your post: <input id=\"simian_select_filter\" type=\"text\" name=\"reel_filter\" value=\"Filter Reels\" /></p>";
	
	$reels = $wpdb->get_results(sprintf('SELECT r.reel_id, r.reel_title, r.reel_time, m.media_thumb from %1$s r LEFT JOIN %2$s m ON r.reel_id = m.reel_id GROUP BY r.reel_id ORDER BY r.reel_time DESC;',$wpdb->prefix . "simian_reels",$wpdb->prefix . "simian_media"));
	
	$html .= "<ul class=\"reel_select\">";
	
	foreach ($reels as $reel){
		$html .= "<li><a id=\"reel_id_".$reel->reel_id."\" href=\"#\"><img src=\"" .$simian_url .  $reel->media_thumb . "\" /></a><h4>".$reel->reel_id."</h4><p class=\"reel_title\">".$reel->reel_title."</p></li>";
	}
	
	$html .= "</ul>";
	if(count($reels)==0){
		$html .= "<p>No Reels Found. Make sure you run Simian->Cache Reels first.</p>";
	}

	$html .= "<div id=\"simian_reel_hover\"><h3>Title</h3><p>other info</p></div>";
	
	echo $html;

	die();
}


function simian_get_reel($reelid){

	global $wpdb;

	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com";

	$ch = curl_init($simian_url . "/v2/api/simian/get_reel");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "auth_token=".get_option('simian_client_api_key')."&reel_id=".$reelid."&reel_type=web_reels");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);

	if(!$return = simplexml_load_string($response)){

		return false;

	} else {
			
		$return->reel->name = str_replace("'", "\\'", $return->reel->name);

		$reeltime = date("Y-m-d H:i:s",strtotime($return->reel->create_date));

		$insertQuery = sprintf('INSERT INTO %1$s (reel_id,reel_title,reel_freshness,reel_time) VALUES (%2$d,\'%3$s\',NOW(),\'%4$s\') ON DUPLICATE KEY UPDATE reel_title = \'%3$s\', reel_freshness = NOW(), reel_time = \'%4$s\';' ,

		$wpdb->prefix . "simian_reels",
		$return->reel->id,
		$return->reel->name,
		$reeltime);

		$wpdb->query($insertQuery);

		foreach($return->media as $mediaitem){
				
			$mediaitem->title = str_replace("'", "\\'", $mediaitem->title);
				
			$insertMedia = sprintf('INSERT INTO %1$s (media_id,reel_id,media_title,media_thumb,media_url,media_mobile_url,media_width,media_height) VALUES (%2$d,%3$d,\'%4$s\',\'%5$s\',\'%6$s\',\'%7$s\',\'%8$s\',\'%9$s\') ON DUPLICATE KEY UPDATE media_title = \'%4$s\',media_thumb = \'%5$s\',media_url = \'%6$s\',media_mobile_url = \'%7$s\', media_width = \'%8$s\', media_height = \'%9$s\'',
			$wpdb->prefix . "simian_media",
			$mediaitem->id,
			$return->reel->id,
			$mediaitem->title,
			strip_url($mediaitem->thumbnail,$simian_url. "/assets/"),
			strip_url($mediaitem->media_file,$simian_url. "/assets/"),
			strip_url($mediaitem->media_file_mobile,$simian_url. "/assets/"),
			$mediaitem->media_width,
			$mediaitem->media_height
			);

			$wpdb->query($insertMedia);
		}
		return true;
	}

}

function strip_url($string, $url){

	return str_ireplace($url,"",$string);

}

function simian_ajax_get_reel() {

	$jsonreturn = array();

	if(isset($_POST['reel_id'])){
		if(simian_get_reel($_POST['reel_id'])){
			$jsonreturn['status'] = "OK";
			echo json_encode($jsonreturn);
			die();
		} else {
			$jsonreturn['status'] = "XML ERROR";
			echo json_encode($jsonreturn);
			die();
		}
	} else {
		$jsonreturn['status'] = "NO REEL ID";
		echo json_encode($jsonreturn);
		die();
	}

}

function simian_settings_init() {

	add_option('simian_client_api_key','');
	add_option('simian_client_company_id','');
	add_option('simian_cache_time','3600');

	add_option('simian_default_show_playlist','0');
	add_option('simian_default_showposters','1');

	add_option('simian_use_jw','0');

	add_option('simian_default_width','640');
	add_option('simian_default_height','480');

	add_option('simian_debug_text','');

}

function simian_client_config(){

	$html = "";

	$html .= "<div class=\"wrap\">";
	$html .= "<h2>Simian Connect Configuration</h2>";

	$changes = false;
	
	if(isset($_POST['submit'])){
		
		//API
		$changes = admin_update_text("simianName","simian_client_company_id");
		$changes = admin_update_text("simianAPI","simian_client_api_key");
		$changes = admin_update_text("simianTime","simian_cache_time");
		
		//Reel Defaults
		$changes = admin_update_text("showTitle","simian_default_show_title");
		admin_update_checkbox("showNowPlayingTitle","simian_default_show_current_title");
		admin_update_checkbox("showPlaylist","simian_default_show_playlist");
		admin_update_checkbox("autoPlayPlaylist","simian_default_autoplay");
		admin_update_checkbox("useJW","simian_use_jw");
		
		//Current Video Defaults 
		$changes = admin_update_text("simianDefaultWidth","simian_default_width");
		$changes = admin_update_text("simianDefaultHeight","simian_default_height");
		admin_update_checkbox("showPoster","simian_default_showposters");
		
		//Playlist Defaults
		$changes = admin_update_text("simianDefaultWidth","simian_default_width");
		$changes = admin_update_text("simianDefaultHeight","simian_default_height");
		admin_update_checkbox("simianDefaultPlaylistTitles","simian_default_playlist_titles");			
	}

	if($changes){
		$html .= "<div id=\"setting-error-settings_updated\" class=\"updated settings-error\"><p><strong>Settings saved.</strong></p></div>";
	}

	$html .= "<form method=\"post\" action=\"http://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] . "\">";
	
	$html .= "<h3>API</h3>";
	
		$html .= admin_setting_input("simianName","Simian Company Name",get_option('simian_client_company_id'),
		"e.g. <strong>companyname</strong>.gosimian.com");
		
		$html .= admin_setting_input("simianAPI","Simian API Key",get_option('simian_client_api_key'),
		"Simian access key for XML API. Contact Simian support if not known.");
		
		$html .= admin_setting_input("simianTime","Cache time",get_option('simian_cache_time'),
		"Time (in minutes) that reel/media data is cached in the Wordpress DB for quick retrival.");
	
	$html .= "<h3>Reel Defaults</h3>";
	
		$html .= admin_setting_input("showTitle","Show Reel Title", checked( 1, get_option('simian_default_show_title'), false ),
		"Show the reel title by default.","checkbox");
		
		$html .= admin_setting_input("showNowPlayingTitle","Show Current Video Title", checked( 1, get_option('simian_default_show_current_title'), false ),
		"Show the title of the current playing video by default.","checkbox");
		
		$html .= admin_setting_input("showPlaylist","Show Playlist", checked( 1, get_option('simian_default_show_playlist'), false ),
		"Show the playlist by default.","checkbox");
		
		$html .= admin_setting_input("autoPlayPlaylist","Auto Play Playlist", checked( 1, get_option('simian_default_autoplay'), false ),
		"Play each video in this reel on page load.","checkbox");
			
		$html .= admin_setting_input("useJW","Use HTML5/Flash JW Player", checked(1, get_option('simian_use_jw'), false),
		"Use JW Player instead of Quicktime to display videos. <strong>Only works with certain encoded files. If unsure, leave unchecked.</strong></span>","checkbox");
	
	$html .= "<h3>Current Video Defaults</h3>";
	
		$html .= admin_setting_input("simianDefaultWidth","Video Width",get_option('simian_default_width'),
		"px (e.g. 640). Set to 0 to use the actual video width or to auto calculate if a height is given.");
		
		$html .= admin_setting_input("simianDefaultHeight","Video Height",get_option('simian_default_height'),
		"px (e.g. 480). Set to 0 to use the actual video height or to auto calculate if a width is given.");	
		
		$html .= admin_setting_input("showPoster","Show Poster Frame?", checked( 1, get_option('simian_default_showposters'), false ),
		"Show a still image on page load, instead of loading the video. Poster frames can be used to stop auto loading of video.","checkbox");
	
	$html .= "<h3>Playlist Defaults</h3>";
		
		$html .= admin_setting_input("simianDefaultThumbnailWidth","Thumbnail Width",get_option('simian_default_thumb_width'),
		"px (e.g. 240). Set to 0 to use the actual thumb height or to auto calculate if a height is given.");
		
		$html .= admin_setting_input("simianDefaultThumbnailHeight","Thumbnail Height",get_option('simian_default_thumb_height'),
		"px (e.g. 180). Set to 0 to use the actual thumb height or to auto calculate if a height is given.");
		
		$html .= admin_setting_input("simianDefaultPlaylistTitles","Show Titles on playlist",get_option('simian_default_playlist_titles'),
		"px (e.g. 180). Set to 0 to use the actual thumb height or to auto calculate if a height is given.");

	$html .= "<p class=\"submit\"><input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button-primary\" value=\"Save Changes\"></p>";
	$html .= "</form>";
	$html .= "</div>";
	
	echo $html;
	
}

function admin_update_text($input,$option){

	if(isset($_POST[$input])){
		update_option($option, $_POST[$input]);
		return true;
	}
	
	return false;
}

function admin_update_checkbox($input,$option){
	
	switch(isset($_POST[$input])){
	case true:
		update_option($option, 1);
	break;
	case false:
	default:
		update_option($option, 0);
	}

}

function admin_setting_input_td($id, $label, $value, $desc,$type="text"){
	
	$html = "";

	$html .= '<tr valign="top"><th scope="row"><label for="'.$id.'">'.$label.'</label></th><td>';
	
	switch($type){
	case "checkbox":
		$html .= '<input name="'.$id.'" type="checkbox" id="'.$id.'" value="1" '. $value .' class="regular-text" />';
	break;
	case "text":
	default:
		$html .= '<input name="'.$id.'" type="text" id="'.$id.'" value="'. $value .'" class="regular-text" />';
	}
	
	$html .= '<span class="description">'.$desc.'</span></td></tr>';

	return $html;

}

function admin_setting_input($id, $label, $value, $desc,$type="text"){
	
	$html = "";

	$html .= '<dt>'.$label.'</dt>';
	
	switch($type){
	case "checkbox":
		$html .= '<dd><input name="'.$id.'" type="checkbox" id="'.$id.'" value="1" '. $value .' class="regular-text" /></dd>';
	break;
	case "text":
	default:
		$html .= '<dd><input name="'.$id.'" type="text" id="'.$id.'" value="'. $value .'" class="regular-text" /></dd>';
	}
	
	$html .= '<dd class="description">'.$desc.'</dd>';

	return $html;

}

function simian_addbuttons(){

	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ){
		return;
	}

	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
		add_filter("mce_external_plugins", "add_simian_tinymce_plugin");
		add_filter('mce_buttons', 'register_simian_button');
	}
}

function register_simian_button($buttons){

	array_push($buttons, "separator", "simianc");
	return $buttons;

}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_simian_tinymce_plugin($plugin_array){

	$plugin_array['simianc'] = plugin_dir_url(__FILE__).'tinymce/editor_plugin.js';
	$plugin_array['wpfullscreen'] = plugin_dir_url(__FILE__).'tinymce/editor_plugin.js';
	return $plugin_array;

}

function simian_call_requires(){

	wp_enqueue_script('jquery');

	if(get_option('simian_use_jw')==1){
	
		wp_enqueue_script('swfobject');
		wp_enqueue_script('simianjw',plugin_dir_url(__FILE__).'jwplayer/jwplayer.js','swfobject');
	
	} else {
	
		wp_enqueue_script('prototype');
		wp_enqueue_script('simianqtac','http://www.apple.com/library/quicktime/2.0/scripts/ac_quicktime.js','prototype');
		wp_enqueue_script('simianqt','http://www.apple.com/library/quicktime/2.0/scripts/qtp_poster.js','prototype');
		wp_enqueue_style('simianqtcss','http://www.apple.com/library/quicktime/2.0/stylesheets/qtp_poster.css','prototype');
	
	}

	wp_enqueue_script('simianjs',plugin_dir_url(__FILE__).'js/simian.js','jquery');

}

function simiancreel_tag_func($atts){

	return simian_tag_process($atts,"company");

}

function simianwreel_tag_func($atts){

	return simian_tag_process($atts,"web");

}

function simian_tag_process($atts, $type){
	
	$html = "error";
	if(isset($atts['id'])){

		/* current video width & height */
		$d_width = intval(get_option('simian_default_width'));
		$d_height = intval(get_option('simian_default_height'));

		if(isset($atts['height'])){ $height = intval($atts['height']); }
		else if($d_height != 0){ $height = $d_height; }
		else { $height = null; }

		if(isset($atts['width'])){ $width = intval($atts['width']); }
		else if($d_width != 0){ $width = $d_width; }
		else { $width = null; }

		/* current video poster */
		$poster = get_option('simian_default_showposters');

		if($poster === "1"){ $poster = true; }
		else if($poster === "0"){ $poster = false; }

		if(isset($atts['poster'])){

			if($atts['poster'] == "show"){ $poster = true; }
			if($atts['poster'] == "hide"){ $poster = false; }
				
		}
		
		/* show playlist */
		$show_playlist = get_option('simian_default_show_playlist');
		
		if(isset($atts['playlist']) && $atts['playlist'] == "show"){ $show_playlist = true; }
		else if(isset($atts['playlist']) && $atts['playlist'] == "hide"){ $show_playlist = false; }
		
		
		$html = simian_load_reel($atts['id'], $width, $height, $type, $poster, $show_playlist);

	} else {

		$html .= "[reel id not provided]";

	}

	return $html;

}

function simian_load_reel($reelid, $width, $height, $type="web", $poster, $show_playlist){

	global $wpdb;

	$simian_url = "http://" . get_option('simian_client_company_id') . ".gosimian.com" . "/assets/";

	$html = "";

	switch($type){
		case "company":
			$reel_type = "company_reels";
			break;
		case "web":
			$reel_type = "web_reels";
			break;
	}

	$result = $wpdb->get_row(sprintf("SELECT COUNT(reel_id) as count, reel_title from %1s WHERE reel_id = %2d AND reel_freshness > '%3s'",$wpdb->prefix . "simian_reels",$reelid,date('c',strtotime("-".get_option('simian_cache_time')." minutes"))));

	if($result->count == 0){
	
		$html .= "Second Run";
		simian_get_reel($reelid);
		$result = $wpdb->get_row(sprintf("SELECT COUNT(reel_id) as count, reel_title from %1s WHERE reel_id = %2d AND reel_freshness > '%3s'",$wpdb->prefix . "simian_reels",$reelid,date('c',strtotime("-".get_option('simian_cache_time')." minutes"))));
	
	}

	if($result->count > 0){

		$medialist = $wpdb->get_results(sprintf("SELECT media_id, media_title, media_url, media_thumb, media_width, media_height  FROM %1s WHERE reel_id = %2d",$wpdb->prefix . "simian_media",$reelid));

		$dom_id = "simreel_" . $reelid;

		$html .= "<div id=\"" . $dom_id . "\" class=\"reel\">";
		
		$html .= "<h2 class=\"reel_title\">" . $result->reel_title . "</h2>";
		
		$html .= "<dl class=\"current_video\">";
		
		$html .= "<dt class=\"current_video_title\">" . $medialist[0]->media_title . "</dt>";
		
		$html .= "<dd class=\"current_video_player\">";		
		
		$customSize = false;
		if($height === null){
		
			 //use API dimensions
			 $height = $medialist[0]->media_height;
			 $width = $medialist[0]->media_width;
		
		} else {
		
			$customSize = true;
			$width = round(($medialist[0]->media_width/$medialist[0]->media_height)*$height);
		
		}
		
		// main player
		$html .= simian_movie_html($dom_id,$medialist[0]->media_url,$medialist[0]->media_thumb, $width, $height, $poster);
		$html .= "</dd>\n";
		
		
		$html .= "</dl>\n";
		
		if($show_playlist == true){

			$html .= simian_show_reel($simian_url,$dom_id,$medialist, $customSize, $height);

		}

		$html .= "<div class='clear_both'></div>\n";
		$html .= "</div>\n";
			
			
	} else {

		$html .= "No Reel Found (Bad Reel id?)";

	}

	return $html;
}

function simian_show_reel($simian_url,$dom_id,$medialist,$customSize, $height){

	$html = "";

	$html .= "<dl class=\"playlist\">\n";
	
	$firstSelect = true;
	foreach($medialist as $mediaitem){
	
		if($firstSelect){
		
			$html .= "<dt class=\"thumb_title selected hoverOver\">".$mediaitem->media_title."</dt>";
			$firstSelect = false;
		
		} else {
		
			$html .= "<dt class=\"thumb_title\">".$mediaitem->media_title."</dt>";
		
		}
	
		$html .= "<dd class=\"simian_media_".$mediaitem->media_id."\">";
		
		$html .= "<a href=\"". $simian_url . $mediaitem->media_url."\" rel=\"".$dom_id."\">";
			$html .= "<img title=\"".$mediaitem->media_title."\" src=\"".$simian_url. $mediaitem->media_thumb."\" />";
		$html .= "</a>";
		
		$html .= "</dd>\n";
		
		wp_enqueue_script('simian_size',plugin_dir_url(__FILE__).'js/simian_size.js');
		
		if($customSize){
		
			$data = array('width' => round(($mediaitem->media_width/$mediaitem->media_height)*$height), 'height' => $height );				
		
		} else {
		
			$data = array('width' => $mediaitem->media_width, 'height' => $mediaitem->media_height);
		
		}
		
		wp_localize_script( 'simian_size', 'sim_dim'.$mediaitem->media_id, $data );
		
	}
	
	$html .= "</dl>\n";
	
	return $html;

}

function simian_movie_html($dom_id,$mediaurl,$thumb,$width,$height, $poster){

	$dom_id = $dom_id . "_mov";

	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com" .  "/assets/";

	$movie_url =  $simian_url . $mediaurl;

	$html = "";
	//poster
	if($poster === true){

		$html .= "<a href=\"".$movie_url."\" rel=\"qtposter\" jscontroller=\"false\"><img src=\"". $simian_url . $thumb."\" width=\"".$width."\" height=\"".$height."\" /></a>";

	} else {

		$html .= "<div id=\"".$dom_id."\">".$dom_id."</div>";

		$html .= "<script type=\"text/javascript\">";
			$html .= "qtEmbed('".$dom_id."','".$movie_url."','".$width."','".$height."', 'false');";
		$html .= "</script>";

	}

	return $html;
}

function simian_install(){

	global $wpdb;
	global $simian_connect_version;

	$reelTableName = $wpdb->prefix . "simian_reels";

	$mediaTableName = $wpdb->prefix . "simian_media";

	$sql1 = "CREATE TABLE `" . $mediaTableName . "` (
			  `unique_media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `media_id` mediumint(9) NOT NULL,
			  `reel_id` mediumint(9) NOT NULL,
			  `media_title` varchar(55) NOT NULL,
			  `media_thumb` varchar(120) NOT NULL,
			  `media_url` varchar(120) NOT NULL,
			  `media_mobile_url` varchar(120) NOT NULL,
			  `media_width` mediumint(9) NOT NULL,
			  `media_height` mediumint(9) NOT NULL,
			  PRIMARY KEY (`unique_media_id`),
			  UNIQUE KEY `media_reel_link` (`media_id`,`reel_id`)
			);";
		
	$sql2 = "CREATE TABLE `" . $reelTableName . "` (
			  `reel_id` mediumint(9) NOT NULL,
			  `reel_title` varchar(55) NOT NULL,
			  `reel_freshness` datetime NOT NULL,
			  `reel_time` datetime DEFAULT NULL,
			  PRIMARY KEY (`reel_id`)
			);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql1);
	dbDelta($sql2);

	update_option('simian_db_version',$simian_connect_version);
	update_option('simian_connect_version',$simian_connect_version);

}

function simian_update_db_check(){

	global $simian_connect_version;
	if (get_site_option('simian_db_version') != $simian_connect_version) {
		simian_db_upgrade();
	}

}

function simian_db_upgrade(){
	
	global $wpdb;
	global $simian_connect_version;

	$reelTableName = $wpdb->prefix . "simian_reels";

	$mediaTableName = $wpdb->prefix . "simian_media";

	$sql1 = "CREATE TABLE `" . $mediaTableName . "` (
			  `unique_media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `media_id` mediumint(9) NOT NULL,
			  `reel_id` mediumint(9) NOT NULL,
			  `media_title` varchar(55) NOT NULL,
			  `media_thumb` varchar(120) NOT NULL,
			  `media_url` varchar(120) NOT NULL,
			  `media_mobile_url` varchar(120) NOT NULL,
			  `media_width` mediumint(9) NOT NULL,
			  `media_height` mediumint(9) NOT NULL,
			  PRIMARY KEY (`unique_media_id`),
			  UNIQUE KEY `media_reel_link` (`media_id`,`reel_id`)
			);";
		
	$sql2 = "CREATE TABLE `" . $reelTableName . "` (
			  `reel_id` mediumint(9) NOT NULL,
			  `reel_title` varchar(55) NOT NULL,
			  `reel_freshness` datetime NOT NULL,
			  `reel_time` datetime DEFAULT NULL,
			  PRIMARY KEY (`reel_id`)
			);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql1);
	dbDelta($sql2);

	update_option('simian_db_version',$simian_connect_version);
	update_option('simian_connect_version',$simian_connect_version);

	update_option('simian_debug_text',$sql1);

}

function simian_client_import(){

}

function simian_new_media($data){
	global $wpdb;

	$data['media_freshness'] = date('c');

	print_r($data);
	if(!$wpdb->get_row("SELECT * FROM ".$wpdb->prefix."simian_media WHERE media_id = ".$data['media_id'])){
		echo "no data";
		$wpdb->insert( $wpdb->prefix."simian_media", $data,	array('%d','%s','%s','%s','%s','%s'));
	} else {
		$wpdb->update($wpdb->prefix."simian_media", $data, array( 'media_id' => $data['media_id'] ), array('%d','%s','%s','%s','%s','%s'), array( '%d' ));
		echo "yes data";
	}


}

function simian_admin_init(){

	wp_enqueue_script('simianadminjs',plugin_dir_url(__FILE__).'js/simian_admin.js','jquery');
	wp_enqueue_style('simianadmincss',plugin_dir_url(__FILE__).'css/simian_admin.css');

}