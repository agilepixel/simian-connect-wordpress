<?php
/**
 * Simian Connect
 *
 * @package     Core
 * @copyright   Copyright (c) 2011 The Code Pharmacy (http://thecodepharmacy.co.uk/)
 * @author 		Henry Allsuch <henry@thecodepharmacy.co.uk>
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
