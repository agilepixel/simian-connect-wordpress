<?php
/**
 * Simian Connect
 *
 * @package     Core
 * @copyright   Copyright (c) 2014 Agile Pixel (http://agilepixel.io/)
 * @author 		Henry Allsuch <henry@agilepixel.io>
 */
class core_core
{
    public $html = "";
    public $cf = null;

    protected $wpdb;

    public function __construct()
    {
        global $cf;

        $this->cf = $cf;

    }

    /**
    * Make the global wordpress db classes accessible through $this in any class
    */
    public function initDb()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

    }

}
