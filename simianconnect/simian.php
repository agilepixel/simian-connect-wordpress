<?php
/*
 Plugin Name: Simian Connect
 Plugin URI: http://thecodepharmacy.co.uk/simian-connect/
 Description: Access all your Simian media and easily add them to your posts. Uses the Simian XML API.
 Version: 0.2
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

$simian_connect_version = "0.2";

add_action('admin_menu', 'simian_menu');
add_action('admin_init', 'simian_settings_init');


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

add_action('wp_ajax_simian_ajax_get_reel', 'simian_ajax_get_reel');
add_action('wp_ajax_simian_select_reel', 'simian_ajax_select_reel');

function simian_ajax_select_reel() {

	global $wpdb;
	
	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com" . "/assets/";
	
	echo "<p>Click on a reel from the selection below to insert it into your post: <input id=\"simian_select_filter\" type=\"text\" name=\"reel_filter\" value=\"Filter Reels\" /></p>";
	$reels = $wpdb->get_results(sprintf('SELECT r.reel_id, r.reel_title, r.reel_time, m.media_thumb from %1$s r LEFT JOIN %2$s m ON r.reel_id = m.reel_id GROUP BY r.reel_id ORDER BY r.reel_time DESC;',$wpdb->prefix . "simian_reels",$wpdb->prefix . "simian_media"));
	echo "<ul class=\"reel_select\">";
	foreach ($reels as $reel){
	echo "<li><a id=\"reel_id_".$reel->reel_id."\" href=\"#\"><img src=\"" .$simian_url .  $reel->media_thumb . "\" /></a><h4>".$reel->reel_id."</h4><p class=\"reel_title\">".$reel->reel_title."</p></li>";
	}
	echo "</ul>";
	if(count($reels)==0){
		echo "<p>No Reels Found. Make sure you run Simian->Cache Reels first.</p>";
	}
	
	echo "<div id=\"simian_reel_hover\"><h3>Title</h3><p>other info</p></div>";
	
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
			
			$insertMedia = sprintf('INSERT INTO %1$s (media_id,reel_id,media_title,media_thumb,media_url,media_mobile_url) VALUES (%2$d,%3$d,\'%4$s\',\'%5$s\',\'%6$s\',\'%7$s\') ON DUPLICATE KEY UPDATE media_title = \'%4$s\',media_thumb = \'%5$s\',media_url = \'%6$s\',media_mobile_url = \'%7$s\'',
			$wpdb->prefix . "simian_media",
			$mediaitem->id,
			$return->reel->id,
			$mediaitem->title,
			strip_url($mediaitem->thumbnail,$simian_url. "/assets/"),
			strip_url($mediaitem->media_file,$simian_url. "/assets/"),
			strip_url($mediaitem->media_file_mobile,$simian_url. "/assets/"));
					
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

function simian_client_config(){

	echo '<div class="wrap">';
	echo '<h2>Simian Connect Configuration</h2>';

	$changes = false;

	if(isset($_POST['simianAPI'])){
		update_option('simian_client_api_key', $_POST['simianAPI']);
		$changes = true;
	}

	if(isset($_POST['simianName'])){
		update_option('simian_client_company_id', $_POST['simianName']);
		$changes = true;
	}

	if(isset($_POST['simianTime'])){
		update_option('simian_cache_time', $_POST['simianTime']);
		$changes = true;
	}
	
	if(isset($_POST['simianDefaultWidth'])){
		update_option('simian_default_width', $_POST['simianDefaultWidth']);
		$changes = true;
	}
	
	if(isset($_POST['simianDefaultHeight'])){
		update_option('simian_default_height', $_POST['simianDefaultHeight']);
		$changes = true;
	}
	
	if(isset($_POST['showReelList'])){
		update_option('simian_default_showreel', 1);
		$changes = true;
	} else {
		update_option('simian_default_showreel', 0);
		$changes = true;
	}
	
	if(isset($_POST['showPoster'])){
		update_option('simian_default_showposters', 1);
		$changes = true;
	} else {
		update_option('simian_default_showposters', 0);
		$changes = true;
	}

	if($changes){
		echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Settings saved.</strong></p></div>';
	}

	echo '<form method="post" action="http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].'">';
	echo '<table class="form-table">
			<tbody>
			<tr valign="top">
			<th scope="row"><label for="simianAPI">Simian API</label></th>
			<td><input name="simianAPI" type="text" id="simianAPI" value="'.get_option('simian_client_api_key').'" class="regular-text" />
			<span class="description">Simian access key for XML API. Contact Simian support if not known.</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="simianName">Simian Company Name</label></th>
			<td><input name="simianName" type="text" id="simianName" value="'.get_option('simian_client_company_id').'" class="regular-text" />
			<span class="description">e.g. <strong>companyname</strong>.gosimian.com</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="simianTime">Cache time</label></th>
			<td><input name="simianTime" type="text" id="simianTime" value="'.get_option('simian_cache_time').'" class="regular-text" />
			<span class="description">Time (in minutes) that reel/media data is cached in the Wordpress DB for quick retrival.</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="simianDefaultWidth">Default Video Width</label></th>
			<td><input name="simianDefaultWidth" type="text" id="simianDefaultWidth" value="'.get_option('simian_default_width').'" class="regular-text" />
			<span class="description">px (e.g. 640). Set to 0 to use real video size.</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="simianDefaultHeight">Default Video Height</label></th>
			<td><input name="simianDefaultHeight" type="text" id="simianDefaultHeight" value="'.get_option('simian_default_height').'" class="regular-text" />
			<span class="description">px (e.g. 480). Set to 0 to use real video size.</span></td>
			</tr>
			
			<th scope="row"><label for="showReelList">Show Reel List</label></th>
			<td><input name="showReelList" id="showReelList" type="checkbox" value="1" class="code" ' . checked( 1, get_option('simian_default_showreel'), false ) . ' />
			<span class="description">Show the Reel list by default in each tag</span></td>
			</tr>
			
			<th scope="row"><label for="showPoster">Show poster?</label></th>
			<td><input name="showPoster" id="showPoster" type="checkbox" value="1" class="code" ' . checked( 1, get_option('simian_default_showposters'), false ) . ' />
			<span class="description">Poster frames can be used to stop auto loading of movies.</span></td>
			</tr>

			</tbody></table>';
	echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>';
	echo '</form>';
	echo '</div>';
}

function simian_settings_init() {

	add_option('simian_connect_version','0.1');

	add_option('simian_client_api_key','');
	add_option('simian_client_company_id','');
	add_option('simian_cache_time','3600');	
	
	add_option('simian_default_showreel','0');
	add_option('simian_default_showposters','1');

	add_option('simian_default_width','640');
	add_option('simian_default_height','480');
	
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
	return $plugin_array;

}

// init process for button control
add_action('init', 'simian_addbuttons');

function simiancreel_tag_func($atts){

	return simianwreel_process($atts,"company");
	
}

function simianwreel_tag_func($atts){

	return simianwreel_process($atts,"web");

}

function simianwreel_process($atts, $type){

	$html = "error";
	if(isset($atts['id'])){
		
		//width & height
		$d_width = get_option('simian_default_width');
		$d_height = get_option('simian_default_height');

		if(isset($atts['height'])){ $height = intval($atts['height']); }
		else { $height = $d_height; }
		
		if(isset($atts['width'])){ $width = intval($atts['width']); }
		else { $width = $d_width; }
		
		//poster
		$poster = get_option('simian_default_showposters');
		
		if($poster === "1"){ $poster = true; }
		else if($poster === "0"){ $poster = false; }
		
		if(isset($atts['poster'])){
		
			if($atts['poster'] == "show"){	$poster = true; }
			if($atts['poster'] == "hide"){	$poster = false; }
		 
		}

		$html = simian_load_reel($atts['id'], $width, $height, $type, $poster);
		
	} else {
		$html .= "[reel id not provided]";
	}

	return $html;


}

add_shortcode( 'scompanyreel', 'simiancreel_tag_func' );
add_shortcode( 'swebreel', 'simianwreel_tag_func' );


function simian_load_reel($reelid, $width, $height, $type="web", $poster){
	
	global $wpdb;
	
	$simian_url = "http://".get_option('simian_client_company_id').".gosimian.com". "/assets/";
	
	$html = "";

	switch($type){
		case "company":
			$reel_type="company_reels";
			break;
		case "web":
			$reel_type="web_reels";
			break;
	}

	$result = $wpdb->get_row(sprintf("SELECT COUNT(reel_id) as count, reel_title from %1s WHERE reel_id = %2d AND reel_freshness > '%3s'",$wpdb->prefix . "simian_reels",$reelid,date('c',strtotime("-".get_option('simian_cache_time')." minutes"))));

	if($result->count == 0){
		$html .= "Second Run";
		simian_get_reel($reelid);
		$result = $wpdb->get_row(sprintf("SELECT COUNT(reel_id) as count, reel_title from %1s WHERE reel_id = %2d AND reel_freshness > '%3s'",$wpdb->prefix . "simian_reels",$reelid,date('c',strtotime("-".get_option('simian_cache_time')." minutes"))));
	}
	
	if($result->count > 0){
	
		$medialist = $wpdb->get_results(sprintf("SELECT media_title, media_url, media_thumb FROM %1s WHERE reel_id = %2d",$wpdb->prefix . "simian_media",$reelid));	
		
		$dom_id = "simreel_" . $reelid;
		
		$html .= "<div id=\"" . $dom_id . "\" class=\"reelPlayer\">";
		$html .= "<div class=\"reelVideo\">";
		
		$html .= simian_movie_html($dom_id,$medialist[0]->media_url,$medialist[0]->media_thumb, $width, $height, $poster);
		
		$html .= "</div>\n";
		
		$html .= "<h2 class=\"reelTitle\">".$result->reel_title."</h2>";
		$html .= "<h3 class=\"mediaTitle\">".$medialist[0]->media_title."</h3>";	
		
		if(get_option('simian_default_showreel')){
		
		
		$html .= "<ul class=\"reelList\">\n";
		foreach($medialist as $mediaitem){
			$html .= "<li>";
			$html .= "<a href=\"". $simian_url . $mediaitem->media_url."\" rel=\"".$dom_id."\">";
			$html .= "<img title=\"".$mediaitem->media_title."\" src=\"".$simian_url. $mediaitem->media_thumb."\" />";
			$html .= "</a>";
			$html .= "</li>\n";
		}
		
		$html .= "</ul>\n";
		
		}
		
		$html .= "<div class='clear_both'></div>\n";
		$html .= "</div>\n";
			
			
	} else {
		
		$html .= "No Reel Found (Bad Reel id?)";
		
	}

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

	$sql1 = "DROP TABLE IF EXISTS `" . $mediaTableName . "`;
			CREATE TABLE IF NOT EXISTS `" . $mediaTableName . "` (
			  `unique_media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `media_id` mediumint(9) NOT NULL,
			  `reel_id` mediumint(9) NOT NULL,
			  `media_title` varchar(55) NOT NULL,
			  `media_thumb` varchar(120) NOT NULL,
			  `media_url` varchar(120) NOT NULL,
			  `media_mobile_url` varchar(120) NOT NULL,
			  PRIMARY KEY (`unique_media_id`),
			  UNIQUE KEY `media_reel_link` (`media_id`,`reel_id`)
			) ENGINE=MyISAM;";
			
	$sql2 = "DROP TABLE IF EXISTS `" . $reelTableName . "`;
			CREATE TABLE IF NOT EXISTS `" . $reelTableName . "` (
			  `reel_id` mediumint(9) NOT NULL,
			  `reel_title` varchar(55) NOT NULL,
			  `reel_freshness` datetime NOT NULL,
			  `reel_time` datetime DEFAULT NULL,
			  PRIMARY KEY (`reel_id`)
			) ENGINE=MyISAM;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql1);
	dbDelta($sql2);
	
	update_option('simian_db_version',$simian_connect_version);

}

function simian_db_upgrade(){

	global $wpdb;
	global $simian_connect_version;
	
	$reelTableName = $wpdb->prefix . "simian_reels";
	
	$mediaTableName = $wpdb->prefix . "simian_media";

	$sql1 = "DROP TABLE IF EXISTS `" . $mediaTableName . "`;
			CREATE TABLE IF NOT EXISTS `" . $mediaTableName . "` (
			  `unique_media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `media_id` mediumint(9) NOT NULL,
			  `reel_id` mediumint(9) NOT NULL,
			  `media_title` varchar(55) NOT NULL,
			  `media_thumb` varchar(120) NOT NULL,
			  `media_url` varchar(120) NOT NULL,
			  `media_mobile_url` varchar(120) NOT NULL,
			  PRIMARY KEY (`unique_media_id`),
			  UNIQUE KEY `media_reel_link` (`media_id`,`reel_id`)
			) ENGINE=MyISAM;";
			
	$sql2 = "DROP TABLE IF EXISTS `" . $reelTableName . "`;
			CREATE TABLE IF NOT EXISTS `" . $reelTableName . "` (
			  `reel_id` mediumint(9) NOT NULL,
			  `reel_title` varchar(55) NOT NULL,
			  `reel_freshness` datetime NOT NULL,
			  `reel_time` datetime DEFAULT NULL,
			  PRIMARY KEY (`reel_id`)
			) ENGINE=MyISAM;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql1);
	dbDelta($sql2);
	
	update_option('simian_db_version',$simian_connect_version);
	
}

register_activation_hook(__FILE__,'simian_install');


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
wp_enqueue_script('jquery');

//wp_enqueue_script('swfobject');
//wp_enqueue_script('simianjw',plugin_dir_url(__FILE__).'jwplayer/jwplayer.js','swfobject');

wp_enqueue_script('prototype');
wp_enqueue_script('simianqtac','http://www.apple.com/library/quicktime/2.0/scripts/ac_quicktime.js','prototype');
wp_enqueue_script('simianqt','http://www.apple.com/library/quicktime/2.0/scripts/qtp_poster.js','prototype');
wp_enqueue_style('simianqtcss','http://www.apple.com/library/quicktime/2.0/stylesheets/qtp_poster.css','prototype');
wp_enqueue_script('simianjs',plugin_dir_url(__FILE__).'js/simian.js','jquery');

add_action('admin_init','simian_admin_init');

function simian_admin_init(){

	global $simian_connect_version;
	wp_enqueue_script('simianadminjs',plugin_dir_url(__FILE__).'js/simian_admin.js','jquery');
	wp_enqueue_style('simianadmincss',plugin_dir_url(__FILE__).'css/simian_admin.css');
	if(!get_option('simian_db_version')){
		simian_db_upgrade();
	}

}