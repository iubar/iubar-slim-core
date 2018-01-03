<?php

namespace Iubar\Slim\Core;

use Iubar\Slim\Core\JsonSimpleSafeAbstractController;
use Iubar\Slim\Core\ResponseCode;
use Iubar\Misc\Encryption;
/**
 *
 * @author Daniele
 *
 * HMAC security
 */
abstract class JsonSafeAbstractController extends JsonSimpleSafeAbstractController {

	/**
	 * @override
	 * @return NULL|string
	 */
	protected function calcHash(){
		$hash = null;
		$request = $this->app->request;
		if($this->user && $this->ts_str){
			$api_key = $this->getApikey($this->user);
			if($api_key){
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $request->getRootUri() . $request->getResourceUri();
				$data = $url . $this->user . $this->ts_str . $api_key;
				$raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
				$hash = base64_encode($raw_hash);
			}
		}
		return $hash;
	}


	/**
	 * @override
	 * @return number
	 */
	protected function getLifetime(){
		return 1200; // 20 minuti
	}

}
