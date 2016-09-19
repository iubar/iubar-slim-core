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
			// NOTA:
		// Urlencode() is used to encode strings for urls.
		// HTMLentities() is a way to encode a HTML string, so that when displayed in a file it's not parsed by the browser.
		// So I use htmlspecialchars (or htmlentities) to encode strings in HTML.
		//
		// And if this URL is placed in a HTML attribute (Such as the action attribute of a form element), you should further encode the URL with htmlspecialchars
	}

}