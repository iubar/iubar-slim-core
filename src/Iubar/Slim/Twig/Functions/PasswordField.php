<?php

namespace Iubar\Slim\Twig\Functions;

class PasswordField extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('passwordfield', array($this, 'passwordFieldFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function passwordFieldFunction($name){		
		$str = 'input type="password" class="form-control input-sm" name="'. $name .'" id="'. $name .'"';		
		return $str;
	}
	
	public function getName(){
		return 'passwordfield';
	}
	
}