<?php
/**
 * Simian Connect
 *
 * @package     config_config
 * @copyright   Copyright (c) 2014 Agile Pixel (http://agilepixel.io/)
 * @author 		Henry Allsuch <henry@agilepixel.io>
 * @author 		Richard Brown <richard@agilepixel.io>
 */

class config
{
    public $site = "Sharpstream";
    public $abs_url;
    public $request_url;
    public $home;
    public $site_name;
    public $host_friendly;
    public $admin_email = "admin@localhost.local";

    public $lib_url = "./library";
    public $js_modules_url = "./js/modules/";
    public $lib_url_legacy = "../../tcp/library";
    public $lib_url_legacy2 = "../tcp/library";
    public $mod_url = "modules";
    public $sl = "/";

    public $class_path = "site";
    public $default_class_name = "index";
    public $current_class_name = "index";

    public $main_controller = null;

    public $ns = "tcp";
    public $install_mode = "dev"; //dev, beta, final
    public $resources = array();
    public static $debug_output = true;

    /**
    * Check mysql file exists and try legacy files if not, then call other init functions in this class
    */
    public function __construct()
    {
        $this->setAbsoluteUrl();

     }

     /**
     * Use server variables to get the absolute path of the base script
     * @access private
     */
     private function setAbsoluteUrl()
     {
        $this->request_url = htmlentities($_SERVER['REQUEST_URI']);

        $home = dirname($_SERVER['PHP_SELF']);
        if ($home == "/") { $home = ""; }
        $home = str_replace("\\", "", $home);
        $this->home = $home  . "/";

        $this->host_friendly = str_replace("www.", "", $_SERVER["HTTP_HOST"]);
        $this->host_friendly = ltrim($this->host_friendly, ".");

        $this->site_name = "http://" .$_SERVER["HTTP_HOST"];
        $this->abs_url = $this->site_name . $home . "/";

     }

}
