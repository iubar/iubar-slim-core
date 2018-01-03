<?php

namespace Iubar\Slim\Core;

use Iubar\Slim\Core\JsonAbstractController;
use Iubar\Slim\Core\ResponseCode;
use Iubar\Misc\Encryption;
/**
 *
 * @author Daniele
 *
 * HMAC security
 */
abstract class JsonSimpleSafeAbstractController extends JsonAbstractController {

	abstract protected function getApikey($user_id);

	protected $user = null;
	protected $ts_str = null;
	protected $hash = null;

	public function __construct(){
		parent::__construct();
	}

	protected function isAuthenticated(){
		$data = $this->readData();
		$this->user = $data['user'];
		$this->ts_str = $data['ts'];
		$this->hash = $data['hash'];
		$b = $this->validate();
		if(!$b){
			$this->sendHmacError();
		}
		return $b;
	}

	private function sendHmacError(){
		$ts = $this->parseTimestamp($this->ts_str);
		$error = 'Erroe sconosciuto';
		$code = ResponseCode::BAD_REQUEST;
		if(!$this->user || !$this->ts_str || !$this->hash){
			$error = "Message authentication failed: Wrong message format !";
			$error .= " ( ts = $this->ts_str";
			$error .= "  user = $this->user";
			$error .= " hash = $this->hash )";
			$code = ResponseCode::BAD_REQUEST;
		}else if(!$ts){
			$error = "Message authentication failed: Wrong timestamp format!";
			$code = ResponseCode::BAD_REQUEST;
		}else if(!$this->isTimeStampValid($ts)){
			$error = "Message authentication failed: Time offeset is too big !";
			$code = ResponseCode::UNAUTHORIZED;
		}else{
			$api_key = $this->getApikey($this->user);
			// $new_api_key = \Application\Utils\ApiKeyUtils::create();
			$expected_hash = $this->calcHash();

			if(strlen($expected_hash)>0 && ($expected_hash != $this->hash)){
				$error = "Message authentication failed: Wrong hash !";
				$code = ResponseCode::UNAUTHORIZED;
			}
		}
		$this->responseStatus($code, array(), $error);

	}

	protected function calcHash(){
		$hash = null;
		$request = $this->app->request;
		if($this->user && $this->ts_str){
			$api_key = $this->getApikey($this->user);
			if($api_key){
				$data = $this->user . $this->ts_str . $api_key;
				$raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
				$hash = base64_encode($raw_hash);
			}
		}
		return $hash;
	}

	protected function validate(){

		// From the comments in the PHP manual page (php.net/manual/en/function.urlencode.php) :
		// "Don't use urlencode() or urldecode() if the text includes an email address,
		// as it destroys the "+" character, a perfectly valid email address character.
		// Unless you're certain that you won't be encoding email addresses
		// AND you need the readability provided by the non-standard "+" usage,
		// instead always use use rawurlencode() or rawurldecode().

		if(!$this->user || !$this->ts_str || !$this->hash){
			return false;
		}

		$api_key = $this->getApikey($this->user);
		if(!$api_key){
			return false;
		}

		$ts = $this->parseTimestamp($this->ts_str);
		if(!$ts || !$this->isTimeStampValid($ts)){
			return false;
		}

		$expected = $this->calcHash();
		if(!$expected){
			return false;
		}

		if(Encryption::hashEquals($expected, $this->hash)){ // hash_equals($expected, $this->hash); // Solo per PHP > 5.6
			return true;
		}else{
			return false;
		}
	}

	private function parseTimestamp($ts_str){
		$ts = null;
		if($ts_str){
			try{
				$ts = new \DateTime($ts_str);
			} catch (\Exception $e) {
				// $msg = $e->getMessage();
			}
		}
		return $ts;
	}



	protected function isTimeStampValid(\DateTime $dateTime){ // here default time-window is 20 minutes (1200 seconds)
		// Note: a common issue when using time-windows for request,
		// is that either server or client is not using the correct time.
		// Make sure both server and client are using systems like NTP to keep times in sync

		$b = false;
		if($dateTime){
			$now = new \DateTime();

			// Normalizzo le timezone dei due timestamp
			$dtz = new \DateTimeZone('Europe/Rome');
			$now->setTimezone($dtz);
			$dateTime->setTimezone($dtz);
			$diffInSeconds = $now->getTimestamp() - $dateTime->getTimestamp();
			$lifetime = $this->getLifetime();
			$max_clockoffset = 600; // 10 minuti
			if($dateTime && $diffInSeconds>-$max_clockoffset && $diffInSeconds<$lifetime){
				$b = true; // vale true se l'hash è stato calcolato in un intervallo tra -10 minuti e +20 minuti
			}
		}
		return $b;
	}

	protected function getLifetime(){
		return 1200; // 20 minuti
	}

	private static function getTimeStampString(){
		$now = new \DateTime();
		// Nota: il formato data RFC3339 presenta il segno "+" nella descrizione della tiemzone
		// per tale motivo non utilizzo urlencode() / urldecode()
		$str = $now->format(\DateTime::RFC3339);
		return $str;
	}


}
