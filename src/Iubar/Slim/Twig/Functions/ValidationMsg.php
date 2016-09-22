<?php

namespace Iubar\Slim\Twig\Functions;

class ValidationMsg extends \Twig_Extension {
	
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('validationMsg', array($this, 'validationMsgFunction'), array('is_safe' => array('html'))),
		);
	}
	
	public function validationMsgFunction($name, $min_length = null, $max_length = null, $pattern = null){
		$str = '<p ng-show="form.'.$name.'.$error.required && form.'.$name.'.$dirty" class="help-block">Campo obbligatorio</p>';
		
		if ($min_length !== null){
			$str .= '<p ng-show="form.'.$name.'.$error.minlength" class="help-block">Lunghezza minima: '.$min_length.'</p>';
		}
		
		if ($max_length !== null){
			$str .= '<p ng-show="form.'.$name.'.$error.maxlength" class="help-block">Lunghezza massima: '.$max_length.'</p>';
		}
		
		if ($pattern !== null){
			$msg = null;
			
			if ($pattern == 'cf') {
				$msg = 'Struttura codice fiscale errata';
			} else if ($pattern == 'num'){
				$msg = 'Inserire solo numeri';
			} else if ($pattern == 'email'){
				$msg = 'Email non valida';
			} else {
				$msg = 'Hai inserito dei caratteri non ammessi';
			}
			
			if ($pattern == 'email'){
				$str .= '<p ng-show="form.'.$name.'.$error.email" class="help-block">'.$msg.'</p>';
			} else {
				$str .= '<p ng-show="form.'.$name.'.$error.pattern" class="help-block">'.$msg.'</p>';
			}
		}
		
		return $str;
	}
	
	public function getName(){
		return 'validationMsg';
	}
	
}