<?php

namespace Iubar\Slim\Twig\Functions;

class ComboBox extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('combobox', array($this, 'comboBoxFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function comboBoxFunction($name, $items = array(), $ng_model = null, $ng_required = null, $ng_change = null, $ng_disabled = null){

		$str = '<select class="form-control input-sm" name="'. $name .'" id="'. $name .'"';
		
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
		
		if ($ng_change !== null){
			$str .= ' ng-change="'. $ng_change .'"';
		}
		
		if ($ng_disabled !== null){
			$str .= ' ng-disabled="'. $ng_disabled .'"';
		}
		
		$str .= '>';
		
		$str .= '<option></option>';
		
		foreach ($items as $value){
			$str .= '<option value="'.$value['id'].'">'.$value['descrizione'].'</option>';
		}
		
		$str .= '</select>';
		
		return $str;
	}
	
	public function getName(){
		return 'combobox';
	}
	
}