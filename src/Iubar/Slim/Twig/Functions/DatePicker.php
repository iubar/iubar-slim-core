<?php

namespace Iubar\Slim\Twig\Functions;

class DatePicker extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('datepicker', array($this, 'datePickerFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function datePickerFunction($name){
		$str = 'input type="text" class="form-control input-sm" name="'. $name .'" id="'. $name .'" data-date-format="dd/MM/yyyy" data-placement="bottom" data-autoclose="true" data-timezone="UTC" bs-datepicker';
		return $str;
	}
	
	public function getName(){
		return 'datepicker';
	}
	
}