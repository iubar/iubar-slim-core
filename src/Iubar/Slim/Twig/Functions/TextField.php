<?php

namespace Iubar\Slim\Twig\Functions;

class TextField extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('textfield', array($this, 'textFieldFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function textFieldFunction($name){		
		$str = 'input type="text" class="form-control input-sm" name="'. $name .'" id="'. $name .'"';		
		return $str;
	}
	
	public function getName(){
		return 'textfield';
	}
	
}