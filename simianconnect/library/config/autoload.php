<?php
/**
* Overide the autoload implement a magneto style class loading system, negating the need for requires.
* @param string $class_name
* @return class|string the instanted class or a class not found eror
*/
function __autoload($class_name){

	global $cf;
	
	//santise class name
	
	$sl = $cf->sl;
	
	$mod_file_path = strtolower($cf->lib_url . $sl .$cf->mod_url . $sl . str_replace("_", "/", $class_name). ".php");
	$file_path = strtolower($cf->lib_url . $sl . str_replace("_", "/", $class_name). ".php");
	
	$mod_file_path_legacy = strtolower($cf->lib_url_legacy . $sl .$cf->mod_url . $sl . str_replace("_", "/", $class_name). ".php");
	$file_path_legacy = strtolower($cf->lib_url_legacy . $sl . str_replace("_", "/", $class_name). ".php");

	$mod_file_path_legacy2 = strtolower($cf->lib_url_legacy2 . $sl .$cf->mod_url . $sl . str_replace("_", "/", $class_name). ".php");
	$file_path_legacy2 = strtolower($cf->lib_url_legacy2 . $sl . str_replace("_", "/", $class_name). ".php");
	
	
	if(file_exists($mod_file_path)){ require_once($mod_file_path); }
	else if(file_exists($file_path)){ require_once($file_path); }
	else if(file_exists($mod_file_path_legacy)){ require_once($mod_file_path_legacy); }
	else if(file_exists($file_path_legacy)){ require_once($file_path_legacy); }
	else if(file_exists($mod_file_path_legacy2)){ require_once($mod_file_path_legacy2); }
	else if(file_exists($file_path_legacy2)){ require_once($file_path_legacy2); }
	
	//TODO need to replace with more silent repording (DB?)
	else { 
	
		echo "<style type=\"text/css\" media=\"screen\">pre {width:80%;color:#fff;background:#000;padding:2em;margin:2em auto; white-space:pre-wrap;font-size:11px; font-family: \"Courier New\",monospace; border-radius: 5px;}</style>";
					
		echo "<pre>";
		
		echo "Error - Class Not Found -> " . $class_name;
		
		echo "</pre>";
	
	}
    
}

?>