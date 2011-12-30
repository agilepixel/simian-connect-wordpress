<?php
/*
 Plugin Name: Simian Connect
 Description: Connector for Simian XML API
 Version: 0.2
 Author: The Code Pharmacy
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



$wpdb->show_errors();

function simian_menu() {
	add_menu_page(__('Simian'), __('Simian'), "manage_options", "simian_connect","simian_config",plugins_url( 'media/simian-icon-16.png' , __FILE__ ));
	add_submenu_page('simian_connect',__('Simian Connect Configuration'),__('Simian Options'),'manage_options', 'simian-connect-config', 'simian_client_config');
	add_submenu_page('simian_connect',__('Simian Cache'),__('Cache Reels'),'manage_options', 'simian-cache-run', 'simian_cache_page');
}

function simian_config() {
	echo '<div class="wrap">';
	echo '<h2>Quick Debug</h2>';
	echo '</div>';

	//function checks
	$html = "<p>Checking for SimpleXML... ";
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
	
	echo '<div class="wrap">';
	echo '<h2>Simian Reel Cache</h2>';
	echo '</div>';
	
	echo '<form id="simianCacheForm" method="post" action="#">';
	echo '<input name="simianReelMax" type="text" id="simianReelMax" value="250" maxlength="3" class="regular-text">';
	echo '<span class="description">Maximum Reel ID to search for.</span>';
	echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Start Caching"></p>';
	echo '</form>';
	echo '<p id="simianCacheStatus">&nbsp</p>';

	
}

add_action('wp_ajax_simian_ajax_get_reel', 'simian_ajax_get_reel');
add_action('wp_ajax_simian_select_reel', 'simian_ajax_select_reel');

function simian_ajax_select_reel() {
	global $wpdb;
	echo "<p>Click on a reel from the selection below to insert it into your post: <input id=\"simian_select_filter\" type=\"text\" name=\"reel_filter\" value=\"Filter Reels\" /></p>";
	$reels = $wpdb->get_results(sprintf('SELECT r.reel_id, r.reel_title, r.reel_time, m.media_thumb from %1$s r LEFT JOIN %2$s m ON r.reel_id = m.reel_id GROUP BY r.reel_id ORDER BY r.reel_time DESC;',$wpdb->prefix . "simian_reels",$wpdb->prefix . "simian_media"));
	echo "<ul class=\"reel_select\">";
	foreach ($reels as $reel){
	echo "<li class=\"left\"><a id=\"reel_id_".$reel->reel_id."\" href=\"#\"><img src=\"" . $reel->media_thumb . "\" /></a><h4>".$reel->reel_id."</h4><p class=\"reel_title\">".$reel->reel_title."</p></li>";
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
	$ch = curl_init("http://".get_option('simian_client_company_id').".gosimian.com/v2/api/simian/get_reel");
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
		
		$insertQuery = sprintf('INSERT INTO %1$s (reel_id,reel_title,reel_freshness,reel_time) VALUES (%2$d,\'%3$s\',NOW(),\'%4$s\') ON DUPLICATE KEY UPDATE reel_title = \'%3$s\', reel_freshness = NOW(), reel_time = \'%4$s\';' ,$wpdb->prefix . "simian_reels",$return->reel->id,$return->reel->name, $reeltime);
		
		
		$wpdb->query($insertQuery);

		foreach($return->media as $mediaitem){
			
			$mediaitem->title = str_replace("'", "\\'", $mediaitem->title);
			
			$insertMedia = sprintf('INSERT INTO %1$s (media_id,reel_id,media_title,media_thumb,media_url,media_mobile_url) VALUES (%2$d,%3$d,\'%4$s\',\'%5$s\',\'%6$s\',\'%7$s\') ON DUPLICATE KEY UPDATE media_title = \'%4$s\',media_thumb = \'%5$s\',media_url = \'%6$s\',media_mobile_url = \'%7$s\'' ,$wpdb->prefix . "simian_media",$mediaitem->id,$return->reel->id,$mediaitem->title,$mediaitem->thumbnail,$mediaitem->media_file,$mediaitem->media_file_mobile);
					
			$wpdb->query($insertMedia);
		}
		return true;
	}
	
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
	echo '<h2>API Options</h2>';

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

	if($changes){
		echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Settings saved.</strong></p></div>';
	}

	echo '<form method="post" action="http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].'">';
	echo '<table class="form-table">
			<tbody>
			<tr valign="top">
			<th scope="row"><label for="simianAPI">Simian API</label></th>
			<td><input name="simianAPI" type="text" id="simianAPI" value="'.get_option('simian_client_api_key').'" class="regular-text">
			<span class="description">Simian access key for XML API. Constact Simian support if not known.</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="simianName">Simian Company Name</label></th>
			<td><input name="simianName" type="text" id="simianName" value="'.get_option('simian_client_company_id').'" class="regular-text">
			<span class="description">e.g. <strong>companyname</strong>.gosimian.com</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="simianTime">Cache time</label></th>
			<td><input name="simianTime" type="text" id="simianTime" value="'.get_option('simian_cache_time').'" class="regular-text">
			<span class="description">Time (in minutes) that reel/media data is cached in the Wordpress DB for quick retrival.</span></td>
			</tr>
			</tbody></table>';
	echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>';
	echo '</form>';
	echo '</div>';
}

function simian_settings_init() {
	add_option('simian_client_api_key','');
	add_option('simian_client_company_id','');
	add_option('simian_cache_time','3600');
	add_option('simian_connect_version','0.1');
}

function simian_addbuttons() {
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

function register_simian_button($buttons) {
	array_push($buttons, "separator", "simianc");
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_simian_tinymce_plugin($plugin_array) {
	$plugin_array['simianc'] = plugin_dir_url(__FILE__).'tinymce/editor_plugin.js';
	return $plugin_array;
}

// init process for button control
add_action('init', 'simian_addbuttons');

function simiancreel_tag_func( $atts ) {

	$message = "error";
	if(isset($atts['id'])){
		simian_load_reel($atts['id'],"company");
		$message = "ID" . $atts['id'];
	} else {
		$message = "[reel id not provided]";
	}

	return $message;

}

function simianwreel_tag_func( $atts ) {

	$message = "error";
	if(isset($atts['id'])){
		$message = simian_load_reel($atts['id'],"web");
	} else {
		$message = "[reel id not provided]";
	}
	return $message;

}

add_shortcode( 'scompanyreel', 'simiancreel_tag_func' );
add_shortcode( 'swebreel', 'simianwreel_tag_func' );


function simian_load_reel($reelid,$type="web"){
	global $wpdb;
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
		$html .= "<div id=\"simreel_".$reelid."\" class=\"reelPlayer\">";
		$html .= "<div class=\"reelVideo\">";		
		$html .= simian_movie_html($medialist[0]->media_url,'640','480');
		
		$html .= "</div>\n";
		
				$html .= "<h2 class=\"reelTitle\">".$return->reel->name."</h2>";
		$html .= "<h3 class=\"mediaTitle\">".$return->media[0]->title."</h3>";
		$html .= "<ul class=\"reelList\">\n";
		foreach($medialist as $mediaitem){
			$html .= "<li>";
			$html .= "<a href=\"".$mediaitem->media_url."\">";
			$html .= "<img title=\"".$mediaitem->media_title."\" src=\"".$mediaitem->media_thumb."\" />";
			$html .= "</a>";
			$html .= "</li>\n";
		}
		$html .= "</ul>\n";
		$html .= "<div class='clear_both'></div>\n";
		$html .= "</div>\n";
			
			
	} else {
		
		$html .= "No Reel Found (Bad Reel id?)";
		
	}

	return $html;
}

function simian_movie_html($mediaurl,$width,$height){
	$height = $height+15;
	$html = "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\" height=\"256\" width=\"320\">
			<param name=\"src\" value=\"".$mediaurl."\">
			<param name=\"autoplay\" value=\"false\">
			<param name=\"type\" value=\"video/quicktime\" height=\"".$height."\" width=\"".$width."\">
			<embed src=\"".$mediaurl."\" height=\"".$height."\" width=\"".$width."\" autoplay=\"false\" type=\"video/quicktime\" pluginspage=\"http://www.apple.com/quicktime/download/\">
			</object>";
	return $html;
}

function simian_install() {
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
wp_enqueue_script('swfobject');
wp_enqueue_script('simianjs',plugin_dir_url(__FILE__).'js/simian.js','jquery');
wp_enqueue_script('simianjw',plugin_dir_url(__FILE__).'jwplayer/jwplayer.js','swfobject');

add_action('admin_init','simian_admin_init');
function simian_admin_init() {
	global $simian_connect_version;
	wp_enqueue_script('simianadminjs',plugin_dir_url(__FILE__).'js/simian_admin.js','jquery');
	wp_enqueue_style('simianadmincss',plugin_dir_url(__FILE__).'css/simian_admin.css');
	if(!get_option('simian_db_version')){
		simian_db_upgrade();
	}
}



