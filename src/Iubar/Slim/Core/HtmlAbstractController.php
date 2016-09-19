<?php

namespace Iubar\Slim\Core;

use Iubar\Slim\Core\AbstractController;

abstract class HtmlAbstractController extends AbstractController {
	
	public function __construct(){
		parent::__construct();		
	}
	
	protected function handleException($e){
	    $this->app->log->error($e->getMessage());
	    throw $e; // Whoops will output the error	    
	}
	
}