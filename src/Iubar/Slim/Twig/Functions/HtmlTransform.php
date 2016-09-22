<?php

namespace Iubar\Slim\Twig\Functions;

class HtmlTransform extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('htmlTransform', array($this, 'htmlTransformFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function htmlTransformFunction($html){
		return $html;
	}
	
	public function getName(){
		return 'htmlTransform';
	}
	
}