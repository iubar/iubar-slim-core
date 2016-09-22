<?php

namespace Iubar\Slim\Twig\Functions;

class EmailField extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('emailfield', array($this, 'emailFieldFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function emailFieldFunction($name){		
		$str = 'input type="email" class="form-control input-sm" name="'. $name .'" id="'. $name .'"';		
		return $str;
	}
	
	public function getName(){
		return 'emailfield';
	}
	
}