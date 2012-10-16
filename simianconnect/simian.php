<?php
/*
 Plugin Name: Simian Connect
 Plugin URI: http://thecodepharmacy.co.uk/simian-connect/
 Description: Access all your Simian&trade; media and easily add them to your posts. Uses the Simian&trade; XML API.
 Version: 0.6
 Author: The Code Pharmacy
 Author URI: http://thecodepharmacy.co.uk/
 License: Proprietary
 */

/*
 Copyright (c) 2012 The Code Pharmacy
 All Rights Reserved.
 It is unlawful to reproduce, copy or otherwise reuse this software
 without express written permission of the author
 */

require_once('library/config/config.php');

$simian_connect_version = "0.6";
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

	add_menu_page(__('Simian&trade; Connect'), __('Simian&trade; Connect'), "manage_options", "simian_connect","simian_client_config",plugins_url( 'media/simian-icon-16.png' , __FILE__ ));
	add_submenu_page('simian_connect',__('Simian&trade; Connect'),__('Reel Cache'),'manage_options', 'simian-cache-run', 'simian_cache_page');
	add_submenu_page('simian_connect',__('Simian&trade; Connect'),__('Debug'),'manage_options', 'simian-connect-config', 'simian_config');

}

function simian_config() {

	global $wpdb;

	$html = "";

	$html .= '<div class="wrap">';
	$html .= '<h2>Simian&trade; Connect Debug</h2>';
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

	echo $html;

}

function simian_cache_page(){

	$html = "";

	$html .= '<div class="wrap">';
	$html .= '<h2>Simian&trade; Connect Reel Cache</h2>';
	$html .= '</div>';

	if(!get_option('simian_client_company_id')||!get_option('simian_client_api_key')){
		$html .= "<p id=\"simianCacheNotice\">Simian Connect must be <a href=\"".get_admin_url()."admin.php?page=simian_connect\">configured</a> first!</p>";
	} else {
		$html .= '<form id="simianCacheForm" method="post" action="#">';
		$html .= '<input name="simianReelMax" type="text" id="simianReelMax" value="250" maxlength="3" class="regular-text">';
		$html .= '<span class="description">Maximum Reel ID to search for.</span>';
		$html .= '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Start Caching"></p>';
		$html .= '</form>';
		$html .= '<p id="simianCacheStatus">&nbsp</p>';
	}

	echo $html;

}

function simian_ajax_select_reel() {

	global $wpdb;

	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com" . "/assets/";

	$html = "";

	if(!get_option('simian_client_company_id')||!get_option('simian_client_api_key')){
		$html .= "<p id=\"simianCacheNotice\">Simian Connect must be <a href=\"".get_admin_url()."admin.php?page=simian_connect\">configured</a> first!</p>";
		echo $html;
		die();
	}

	$html .= "<p>Click on a reel from the selection below to insert it into your post: <input id=\"simian_select_filter\" type=\"text\" name=\"reel_filter\" value=\"Filter Reels\" /></p>";

	$reels = $wpdb->get_results(sprintf('SELECT r.reel_id, r.reel_title, r.reel_time, m.media_thumb from %1$s r LEFT JOIN %2$s m ON r.reel_id = m.reel_id GROUP BY r.reel_id ORDER BY r.reel_time DESC;',$wpdb->prefix . "simian_reels",$wpdb->prefix . "simian_media"));

	if(count($reels)==0){
		$html .= "<p id=\"simianCacheNotice\">No Reels Found. Automatically filling cache...</p>";
		$html .= "<p id=\"simianCacheStatus\">&nbsp;</p>";
		$html .= "<script type=\"text/javascript\">";
		$html .= "cache_reel_adhoc()";
		$html .= "</script>";
	} else {
		$html .= "<p id=\"simianCacheNotice\">Checking for new reels...</p>";
		$html .= "<p id=\"simianCacheStatus\">&nbsp;</p>";
		$html .= "<script type=\"text/javascript\">";
		$html .= "cache_reel_new()";
		$html .= "</script>";
	}

	$html .= "<ul class=\"reel_select\">";

	foreach ($reels as $reel){
		$html .= "<li><a id=\"reel_id_".$reel->reel_id."\" href=\"#\"><img src=\"" .$simian_url .  $reel->media_thumb . "\" /></a><h4>".$reel->reel_id."</h4><p class=\"reel_title\">".$reel->reel_title."</p></li>";
	}

	$html .= "</ul>";


	echo $html;

	die();
}


function simian_get_reel($reel_id){

	global $wpdb;

	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com";

	$ch = curl_init($simian_url . "/v2/api/simian/get_reel");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "auth_token=".get_option('simian_client_api_key')."&reel_id=".$reel_id."&reel_type=web_reels");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);

	libxml_use_internal_errors(true);
	if(!$return = simplexml_load_string($response)){

		return false;

	} else {

		if(count($return->media) == 0){
			return array('reel_id'=>0,'reel_name'=>'No Media');
		}

		$responseArray = array();

		$return->reel->name = str_replace("'", "\\'", $return->reel->name);

		$reeltime = date("Y-m-d H:i:s",strtotime($return->reel->create_date));

		$insertQuery = sprintf('INSERT INTO %1$s (reel_id,reel_title,reel_freshness,reel_time) VALUES (%2$d,\'%3$s\',NOW(),\'%4$s\') ON DUPLICATE KEY UPDATE reel_title = \'%3$s\', reel_freshness = NOW(), reel_time = \'%4$s\';' ,
		$wpdb->prefix . "simian_reels",
		$return->reel->id,
		$return->reel->name,
		$reeltime);

		$wpdb->query($insertQuery);

		$responseArray['reel_id'] = (int) $return->reel->id;
		$responseArray['reel_name'] = (string) $return->reel->name;
		
		$compoundQuery = "INSERT INTO ".$wpdb->prefix . "simian_media (media_id,reel_id,media_title,media_thumb,media_url,media_mobile_url,media_width,media_height) VALUES ";
		foreach($return->media as $mediaitem){

			$mediaitem->title = str_replace("'", "\\'", $mediaitem->title);

			$insertMedia = sprintf('(%1$d,%2$d,\'%3$s\',\'%4$s\',\'%5$s\',\'%6$s\',\'%7$s\',\'%8$s\'),',
			$mediaitem->id,
			$return->reel->id,
			$mediaitem->title,
			strip_url($mediaitem->thumbnail,$simian_url. "/assets/"),
			strip_url($mediaitem->media_file,$simian_url. "/assets/"),
			strip_url($mediaitem->media_file_mobile,$simian_url. "/assets/"),
			$mediaitem->media_width,
			$mediaitem->media_height
			);
			$compoundQuery .= $insertMedia;

			if(!isset($responseArray['reel_thumb'])){
				$responseArray['reel_thumb'] = (string) $mediaitem->thumbnail;
			}
		}
		$compoundQuery = substr($compoundQuery, 0, -1);
		$compoundQuery .= " ON DUPLICATE KEY UPDATE media_title = VALUES(media_title),media_thumb = VALUES(media_thumb),media_url = VALUES(media_url),media_mobile_url = VALUES(media_mobile_url), media_width = VALUES(media_width), media_height = VALUES(media_height)";

		$wpdb->query($compoundQuery);
		return $responseArray;
	}

}

function strip_url($string, $url){

	return str_ireplace($url,"",$string);

}

function simian_ajax_get_reel() {

	$jsonreturn = array();

	if(isset($_POST['reel_id'])){
		if($reelinfo = simian_get_reel($_POST['reel_id'])){
			$jsonreturn['status'] = "OK";
			$jsonreturn['details'] = $reelinfo;
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

	//API
	add_option('simian_client_company_id','');
	add_option('simian_client_api_key','');
	add_option('simian_cache_time','3600');

	//Reel Defaults
	add_option('simian_default_show_title','1');
	add_option('simian_default_show_playlist','1');
	add_option('simian_default_autoplay','0');
	add_option('simian_use_jw','0');

	add_option('simian_theme','theme1');

	//Current Video Defaults
	add_option('simian_default_show_current_title','1');
	add_option('simian_default_width','640');
	add_option('simian_default_height','480');
	add_option('simian_default_showposters','1');

	//Playlist Defaults
	add_option('simian_default_playlist_titles','1');
	add_option('simian_default_thumb_width','129');
	add_option('simian_default_thumb_height','96');

	//Debug
	add_option('simian_debug_text','');

}

function simian_client_config(){

	$html = "";

	$html .= "<div class=\"simian-admin wrap\">";
	$html .= "<h2>Simian&trade; Connect Configuration</h2>";

	$changes = false;

	if(isset($_POST['submit'])){

		//API
		$changes = admin_update_text("simianName","simian_client_company_id");
		$changes = admin_update_text("simianAPI","simian_client_api_key");
		$changes = admin_update_text("simianTime","simian_cache_time");

		//Skin Options
		admin_update_text("simianTheme","simian_theme");

		//Reel Defaults
		admin_update_checkbox("showTitle","simian_default_show_title");
		admin_update_checkbox("showPlaylist","simian_default_show_playlist");
		admin_update_checkbox("autoPlayPlaylist","simian_default_autoplay");
		admin_update_checkbox("useJW","simian_use_jw");

		//Current Video Defaults
		admin_update_checkbox("showNowPlayingTitle","simian_default_show_current_title");

		$changes = admin_update_text("simianDefaultWidth","simian_default_width");
		$changes = admin_update_text("simianDefaultHeight","simian_default_height");
		admin_update_checkbox("showPoster","simian_default_showposters");

		//Playlist Defaults
		admin_update_checkbox("simianDefaultPlaylistTitles","simian_default_playlist_titles");
		$changes = admin_update_text("simianDefaultThumbnailWidth","simian_default_thumb_width");
		$changes = admin_update_text("simianDefaultThumbnailHeight","simian_default_thumb_height");

	}

	if($changes){
		$html .= "<div id=\"setting-error-settings_updated\" class=\"updated settings-error\"><p><strong>Settings saved.</strong></p></div>";
	}

	$html .= "<form id=\"simian_settings_form\" method=\"post\" action=\"http://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] . "\">";

	$html .= "<h3>API</h3>";

	$html .= "<dl class=\"settings-group\">";

	$html .= admin_setting_input("simianName","Simian Company Name",get_option('simian_client_company_id'),
		"e.g. <strong>companyname</strong>.gosimian.com");

	$html .= admin_setting_input("simianAPI","Simian API Key",get_option('simian_client_api_key'),
		"Simian access key for XML API. Contact Simian support if not known.");

	$html .= admin_setting_input("simianTime","Cache time",get_option('simian_cache_time'),
		"Time (in minutes) that reel/media data is cached in the Wordpress DB for quick retrival.");

	$html .= "</dl>";

	$html .= "<h3>Reel Skin</h3>";

	$html .= "<dl class=\"settings-group\">";

	//This is the list of built in themes that come with the plugin
	$skinArray = array(
	"Theme #1"=>"theme1",
	"Theme #2"=>"theme2",
	"Theme #3"=>"theme3"
	);

	if(file_exists(get_template_directory() . '/simian/custom.css')||file_exists(get_template_directory() . '/simian/custom.php')||file_exists(get_template_directory() . '/simian/custom.js')){
		$skinArray["Theme Directory Style"] = "user_custom";
	}

	$html .= admin_setting_input("simianTheme","Choose a theme",get_option('simian_theme'),
		"Choose from one of our built in Reel Styles or add <strong>/simian/custom.css</strong> to your theme directory for a your own custom look","select",$skinArray);

	$html .= "</dl>";

	$html .= "<h3>Reel Defaults</h3>";

	$html .= "<dl class=\"settings-group\">";

	$html .= admin_setting_input("showTitle","Show Reel Title", checked( 1, get_option('simian_default_show_title'), false ),
		"Show the reel title by default.",
		"checkbox");

	$html .= admin_setting_input("showPlaylist","Show Playlist", checked( 1, get_option('simian_default_show_playlist'), false ),
		"Show the playlist by default.",
		"checkbox");

	$html .= admin_setting_input("autoPlayPlaylist","Auto-Play Playlist", checked( 1, get_option('simian_default_autoplay'), false ),
		"Play each video in the playlist on page load automatically in sequence.","checkbox");

	$html .= admin_setting_input("useJW","Use HTML5/Flash JW Player", checked(1, get_option('simian_use_jw'), false),
		"Use JW Player instead of Quicktime to display videos. <strong>Only works with certain encoded files. Check with Simian for encoding details. If unsure, leave unchecked.</strong></span>",
		"checkbox");

	$html .= "</dl>";

	$html .= "<h3>Current Video Defaults</h3>";

	$html .= "<dl class=\"settings-group\">";

	$html .= admin_setting_input("showNowPlayingTitle","Show Current Video Title", checked( 1, get_option('simian_default_show_current_title'), false ),
		"Show the title of the current playing video by default.","checkbox");

	$html .= admin_setting_input("simianDefaultWidth","Video Width",get_option('simian_default_width'),
		"px (e.g. 640). Set to 0 to use the actual video width or to auto calculate if a height is given.");

	$html .= admin_setting_input("simianDefaultHeight","Video Height",get_option('simian_default_height'),
		"px (e.g. 480). Set to 0 to use the actual video height or to auto calculate if a width is given.");	

	$html .= admin_setting_input("showPoster","Show Poster Frame?", checked( 1, get_option('simian_default_showposters'), false ),
		"Show a still image on page load, instead of loading the video. Poster frames can be used to stop auto loading of video.",
		"checkbox");

	$html .= "</dl>";

	$html .= "<h3>Playlist Defaults</h3>";

	$html .= "<dl class=\"settings-group\">";

	$html .= admin_setting_input("simianDefaultPlaylistTitles","Show Titles on playlist",
	checked( 1, get_option('simian_default_playlist_titles'), false ),
		"Show the titles of each video on the playlist.",
		"checkbox");

	$html .= admin_setting_input("simianDefaultThumbnailWidth","Thumbnail Width",get_option('simian_default_thumb_width'),
		"px (e.g. 129). Set to 0 to use the actual thumb width or to auto calculate if a height is given.");

	$html .= admin_setting_input("simianDefaultThumbnailHeight","Thumbnail Height",get_option('simian_default_thumb_height'),
		"px (e.g. 96). Set to 0 to use the actual thumb height or to auto calculate if a width is given.");

	$html .= "</dl>";

	$html .= "<p class=\"submit\"><input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button-primary\" value=\"Save Changes\"></p>";
	$html .= "</form>";

	$html .= "<p><i>Simian&trade; and the Simian&trade; logo are trademarks or servicesmarks of Gosimian.com</i></p>";
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

function admin_setting_input($id, $label, $value, $desc, $type="text", $options=null){

	$html = "";

	$html .= "<dt><label for=\"".$id."\">". $label . "</label></dt>";

	switch($type){
		case "checkbox":
			$html .= "<dd><input name=\"" . $id . "\" type=\"checkbox\" id=\"" . $id . "\" value=\"1\" ". $value . "class=\"regular-text\" /></dd>";
			break;
		case "select":
			$html .= "<dd><select name=\"" . $id . "\" id=\"" . $id . "\">";
			if(is_array($options)){
				foreach($options as $title=>$val){
					if($val == $value){
						$html .= "<option value=\"" . $val . "\" selected=\"selected\">".$title."</option>";
					} else {
						$html .= "<option value=\"" . $val . "\">".$title."</option>";
					}
				}
			}
			$html .= "</select></dd>";
			break;
		case "text":
		default:
			$html .= "<dd><input name=\"" . $id . "\" type=\"text\" id=\"" . $id . "\" value=\"" . $value ."\" class=\"regular-text\" /></dd>";
	}

	$html .= "<dd class=\"description\">".$desc."</dd>";

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
		//wp_enqueue_script('simianjw',plugin_dir_url(__FILE__).'jwplayer/jwplayer.js','swfobject');
		//wp_localize_script('simianjw','jw_swf',plugin_dir_url(__FILE__).'jwplayer/player.swf');

		wp_enqueue_script('simianjw',plugin_dir_url(__FILE__).'ovp/ovp.js','swfobject');
		wp_localize_script('simianjw','jw_swf',plugin_dir_url(__FILE__).'ovp/AkamaiFlashPlayer.swf');


	} else {

		wp_enqueue_script('prototype');
		wp_enqueue_script('simianqtac',plugin_dir_url(__FILE__).'quicktime/ac_quicktime.js','prototype');
		wp_enqueue_script('simianqt',plugin_dir_url(__FILE__).'quicktime/qtp_poster.js','prototype');
		wp_enqueue_style('simianqtcss','http://www.apple.com/library/quicktime/2.0/stylesheets/qtp_poster.css','prototype');

	}

	wp_enqueue_script('simianjs',plugin_dir_url(__FILE__).'js/simian.js','jquery');
	wp_localize_script('simianjs','autoplay_playlist',get_option('simian_default_autoplay',0));

	//custom css styles
	simian_theme();


}

function simiancreel_tag_func($atts){

	return simian_tag_process($atts,"company");

}

function simianwreel_tag_func($atts){

	return simian_tag_process($atts,"web");

}

function simian_tag_boolean($atts, $tag ,$option,$enum=null){

	//get default
	$default = (boolean) get_option($option);

	if(!isset($atts[$tag])){ return $default; }

	$boo = $atts[$tag];

	if($enum != null && count($enum) == 2){

		if($boo == $enum[0]){ return true; }
		if($boo == $enum[1]){ return false; }

	} else {

		if($boo == "true"){ return true; }
		if($boo == "false"){ return false; }

	}

}

function simian_tag_process($atts, $type){

	$html = "error";

	if(isset($atts['id'])){

		$html = simian_load_reel($atts['id'], $type, $atts);

	} else {

		$html .= "[reel id not provided]";

	}

	return $html;

}

function wp_get_reel($reel_id){

	global $wpdb;

	$reel = $wpdb->get_row(sprintf("SELECT COUNT(reel_id) as count, reel_title from %1s WHERE reel_id = %2d AND reel_freshness > '%3s'",$wpdb->prefix . "simian_reels",$reel_id,date('c',strtotime("-".get_option('simian_cache_time')." minutes"))));

	return $reel;

}

function wp_get_playlist($reel_id){

	global $wpdb;

	$playlist = $wpdb->get_results(sprintf("SELECT media_id, media_title, media_url, media_thumb, media_width, media_height  FROM %1s WHERE reel_id = %2d",$wpdb->prefix . "simian_media",$reel_id));

	return $playlist;

}

function simian_load_reel($reel_id, $type="web", $atts){

	/* reel options */
	$simian_options = array();
	$simian_options['reel_title'] = simian_tag_boolean($atts,"title","simian_default_show_title", array("show","hide"));
	$simian_options['show_playlist'] = simian_tag_boolean($atts,"playlist","simian_default_show_playlist", array("show","hide"));
	$simian_options['autoplay'] = simian_tag_boolean($atts,"autoplay","simian_default_autoplay");
	$simian_options['use_jw'] = simian_tag_boolean($atts,"use_jw","simian_use_jw");
	$simian_options['video_title'] = simian_tag_boolean($atts,"video_title","simian_default_show_current_title", array("show","hide"));
	$simian_options['poster'] = simian_tag_boolean($atts,"poster","simian_default_showposters", array("show","hide"));
	$simian_options['thumb_titles'] = simian_tag_boolean($atts,"thumb_titles","simian_default_playlist_titles", array("show","hide"));

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

	$reel = wp_get_reel($reel_id);

	if($reel->count == 0){

		//cache the reels via API
		simian_get_reel($reel_id);

		//try again
		$reel = wp_get_reel($reel_id);

	}

	if($reel->count > 0){

		$dom_id = "simreel_" . $reel_id;
		$playlist = wp_get_playlist($reel_id);
		$chosenTheme = get_option('simian_theme');

		//look for usable template file - first
		if($chosenTheme == "user_custom" && file_exists(get_template_directory() . '/simian/custom.php')){
			$templateFile = get_template_directory() . '/simian/custom.php';
		} elseif (file_exists(plugin_dir_path(__FILE__).'themes/'.$chosenTheme.'.php')){
			$templateFile = plugin_dir_path(__FILE__).'themes/'.$chosenTheme.'.php';
		} else {
			$templateFile = plugin_dir_path(__FILE__).'themes/default.php';
		}

		$frontVideo = $playlist[0];

		//store template output
		ob_start();
		include($templateFile);
		$html .= ob_get_contents();
		ob_end_clean ();
			
			
	} else {

		$html .= "No Reel Found (Please check your reel ID is valid)";

	}

	return $html;
}

function parse_dimensions($default, $tag, $original, $atts){

	$width = intval($default[0]);
	$height = intval($default[1]);

	$original_width = intval($original[0]);
	$original_height = intval($original[1]);

	//tag settings
	if(isset($atts[$tag[0]])){ $width = intval($tag[$tag[0]]); }
	if(isset($atts[$tag[1]])){ $height = intval($tag[$tag[1]]); }

	if($width == 0 && $height == 0){ $resize_mode = "original"; }
	else if($width == 0){ $resize_mode = "aspect_width"; }
	else if($height == 0){ $resize_mode = "aspect_height"; }
	else { $resize_mode = "use_custom"; }

	switch($resize_mode){
		case "original":

			$width = $original_width;
			$height = $original_height;

			break;
		case "aspect_width":

			$width = round(($original_width / $original_height) * $height);

			break;
		case "aspect_height":

			$height = round(($original_height / $original_width) * $width);

			break;
	}

	$dim = array();
	$dim['width'] = $width;
	$dim['height'] = $height;

	return $dim;

}

function simian_inline_javascript($dom_id,$movie_url,$dim){
	$html = "";
	$html .= "<script type=\"text/javascript\">";

	if(get_option('simian_use_jw')==1){
		$html .= "jwplayer(\"".$dom_id."\").setup(
			{
			autostart: false,
			file: \"".$movie_url."\",
			flashplayer: \"".plugin_dir_url(__FILE__)."jwplayer/player.swf\",
			height: ".$dim['height'].",
			width: ".$dim['width'].",
			events: {
   				onComplete: function() {
   					simian_next_playlist(null,\$j('#'+this.id).parents('.current_video_player'));
   				}
   			}
			});";
	} else {
		$html .= "qtEmbed('".$dom_id."','".$movie_url."','".$dim['width']."','".$dim['height']."', 'false', 'false');";
	}

	$html .= "</script>";
	return $html;
}

function simian_theme(){

	$chosenTheme = get_option('simian_theme');

	if($chosenTheme=="user_custom"){
		$csspath = get_template_directory() . '/simian/custom.css';
		$jspath = get_template_directory() . '/simian/custom.js';
		$jsurl = get_template_directory_uri() . '/simian/custom.js';
		$cssurl = get_template_directory_uri() . '/simian/custom.css';
	} else {
		$csspath = plugin_dir_path(__FILE__).'css/'.$chosenTheme.'.css';
		$jspath = plugin_dir_path(__FILE__).'js/'.$chosenTheme.'.js';
		$cssurl = plugin_dir_url(__FILE__).'css/'.$chosenTheme.'.css';
		$jsurl = plugin_dir_url(__FILE__).'js/'.$chosenTheme.'.js';
	}

	if(isset($jsurl)&&file_exists($jspath)){
		wp_enqueue_script('simian_style_js',$jsurl);
	}

	if(isset($cssurl)&&file_exists($csspath)){
		wp_enqueue_style('simian_theme',$cssurl);
	}

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

	$sql1 = "CREATE TABLE " . $mediaTableName . " (
			  unique_media_id int(10) unsigned NOT NULL AUTO_INCREMENT,
			  media_id mediumint(9) NOT NULL,
			  reel_id mediumint(9) NOT NULL,
			  media_title varchar(55) NOT NULL,
			  media_thumb varchar(120) NOT NULL,
			  media_url varchar(120) NOT NULL,
			  media_mobile_url varchar(120) NOT NULL,
			  media_width mediumint(9) NOT NULL,
			  media_height mediumint(9) NOT NULL,
			  PRIMARY KEY  (unique_media_id),
			  UNIQUE KEY media_reel_link (media_id,reel_id)
			);";

	$sql2 = "CREATE TABLE " . $reelTableName . " (
			  reel_id mediumint(9) NOT NULL,
			  reel_title varchar(55) NOT NULL,
			  reel_freshness datetime NOT NULL,
			  reel_time datetime DEFAULT NULL,
			  PRIMARY KEY  (reel_id)
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