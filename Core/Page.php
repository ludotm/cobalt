<?php

namespace Core;

use Core\Service;

class Page
{
	use \Core\Singleton;

	protected $layoutPath;
	protected $zonePath;
	public $html_base;
	public $layout;
	public $zone;
	public $section;
	public $template;

	public $base_title;
	public $title;
	public $metas = array();
	public $scripts = array();
	public $stylesheets = array();
	public $plugins = array();
	public $less = array();
	public $favicon;
	
	public $google_font = array();
	public $icon_type;

	public $vars;

	protected function __construct() {

	}


	

}