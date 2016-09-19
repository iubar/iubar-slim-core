<?php

namespace Iubar\Slim\Core;
	
abstract class AbstractController {
	
	protected $app = null;
	
	protected static $k = 0;
	
	abstract protected function handleException($e);
	
	public function __construct(){
		$this->app = \Slim\Slim::getInstance();
	}
		
	/**
	 * Converts characters to HTML entities
	 * This is important to avoid XSS attacks, and attempts to inject malicious code in your page.
	 *
	 * @param  string $str The string.
	 * @return string
	 */
	public function encodeHTML($str){
		return htmlentities($str, ENT_QUOTES, 'UTF-8');
	}

}