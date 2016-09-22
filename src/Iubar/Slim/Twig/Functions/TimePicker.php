<?php

namespace Iubar\Slim\Twig\Functions;

class TimePicker extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('timepicker', array($this, 'timePickerFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function timePickerFunction($name, $ng_model = null, $ng_required = null, $ng_min_length = null, $ng_max_length = null, $ng_change = null, $ng_disabled = null){
		
		$str = '<input type="text" class="form-control input-sm" name="'. $name .'" id="'. $name .'"';
		
		if ($ng_model !== null){
			$str .= ' ng-model="'. $ng_model .'"';
		}
		
		if ($ng_required !== null){
			if ($ng_required === true){
				$str .= ' required';
			} else {
				$str .= ' ng-required="'. $ng_required .'"';
			}
		}
		
		if ($ng_min_length !== null){
			$str .= ' ng-minlength="'. $ng_min_length .'"';
		}
		
		if ($ng_max_length !== null){
			$str .= ' ng-maxlength="'. $ng_max_length .'"';
		}
		
		if ($ng_change !== null){
			$str .= ' ng-change="'. $ng_change .'"';
		}
		
		if ($ng_disabled !== null){
			$str .= ' ng-disabled="'. $ng_disabled .'"';
		}
		
		$str .= ' data-time-format="HH:mm" data-autoclose="1" date-timezone="UTC" bs-timepicker>';
		
		return $str;
	}
	
	public function getName(){
		return 'timepicker';
	}
	
}