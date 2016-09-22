<?php

namespace Iubar\Slim\Twig\Functions;

class NgValidationClass extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('ngValidationClass', array($this, 'ngValidationClassFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function ngValidationClassFunction($name, $obj = null){
		$str = 'ng-class="{ \'has-error\': form.'. $name .'.$error.required || form.'. $name .'.$dirty && form.'. $name .'.$invalid, \'has-success\' : form.'. $name .'.$dirty && form.'. $name .'.$valid';
		
		if ($obj !== null){
			$str = 'ng-class="{ \'has-error\': '.$obj.' &&  (form.'. $name .'.$error.required || form.'. $name .'.$dirty && form.'. $name .'.$invalid), \'has-success\' : form.'. $name .'.$dirty && form.'. $name .'.$valid';
		}
		
		$str .= '}"';
		return $str;
	}
	
	public function getName(){
		return 'ngValidationClass';
	}
	
}