<?php

namespace Iubar\Slim\Core;

use Iubar\Slim\Core\JsonAbstractController;
use Iubar\Slim\Core\ResponseCode;

class JsonSafeAbstractController extends JsonAbstractController { 
    
    abstract protected function getApikey($user);
    
    public function __construct(){
		parent::__construct();       
        try {            
            if($this->isAuthenticated()){
               $this->app->log->debug(get_class($this) . '__construct(): ok, request is authenticated !');
            }      
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    protected function isAuthenticated(){
        $b = $this->validate();
        if(!$b){
            $this->sendHmacError();
        }
        return $b;
    }
    
    protected function sendHmacError($debug = false){
        $request = $this->app->request;
        $user = $request->get('user');
        $ts_str = rawurldecode($request->get('ts'));
        $hash = $request->get('hash');
    
        $ts = $this->parseTimestamp($ts_str);
    
        if(!$debug){
    
            $error = null;
            $code = null;
            if(!$user || !$ts_str || !$hash){
                $error = "Message authentication failed: Wrong message format !";
                $code = ResponseCode::BAD_REQUEST;
            }else if(!$ts){
                $error = "Message authentication failed: Wrong timestamp format!";
                $code = ResponseCode::BAD_REQUEST;
            }else if(!self::isTimeStampValid($ts)){
                $error = "Message authentication failed: Time offeset is too big !";
                $code = ResponseCode::UNAUTHORIZED;
            }else{
                $error = "Message authentication failed: Wrong hash !";
                $code = ResponseCode::UNAUTHORIZED;
            }
            $this->responseStatus($code, array(), $error);
    
        }else{
    
            // Questo blocco di codice è utile solo al fine di deguggare il metodo
    
            $ts_reparsed = "error";
            if($ts){
                $ts_reparsed = $ts->format(\DateTime::RFC3339); // Verifico se il parsing del timestamp è stato effettuato correttamente
            }
    
            $api_key = $this->getApikey($user);
            // $new_api_key = \Application\Utils\ApiKeyUtils::create();
            $expected = $this->calcHash($user, $api_key, $ts_str); // WARNING: security breach !!!
    
            $debug_data = array(
                'user'          =>  $user,
                'ts'            =>  rawurlencode($ts_str), // rawurlencode() è obbligatorio perchè la stringa contiene il segno "+"
                'hash'          =>  $hash,
                'hash_expected' =>  $expected,
                // 'new_api_key'=>  $this->formatApiKey($new_api_key),
                'ts_reparsed'   =>  rawurlencode($ts_reparsed)
    
            );
            $this->responseStatus(ResponseCode::UNAUTHORIZED, json_encode($debug_data), "Errore");
        }
    }    
    
    private function calcHash($user, $api_key, $ts_str){
        $hash = null;
        if($user && $ts_str && $api_key){
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $this->app->request->getRootUri() . $this->app->request->getResourceUri();
            $data = $url . $user . $ts_str . $api_key;
            $raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
            $hash = base64_encode($raw_hash);
        }
        return $hash;
    }
    
    protected function validate(){
        $user = $this->app->request->get('user');
    
        // From the comments in the PHP manual page (php.net/manual/en/function.urlencode.php) :
        // "Don't use urlencode() or urldecode() if the text includes an email address,
        // as it destroys the "+" character, a perfectly valid email address character.
        // Unless you're certain that you won't be encoding email addresses
        // AND you need the readability provided by the non-standard "+" usage,
        // instead always use use rawurlencode() or rawurldecode().
    
        // Nota: il formato data RFC3339 presenta il segno "+" nella descrizione della tiemzone
        // per tale motivo non utilizzo urlencode() / urldecode()
    
        $ts_str = rawurldecode($this->app->request->get('ts'));
        $hash = $this->app->request->get('hash');
        if(!$user || !$ts_str || !$hash){
            return false;
        }
    
        $api_key = $this->getApikey($user);
        if(!$api_key){
            return false;
        }
    
        $ts = $this->parseTimestamp($ts_str);
        if(!$ts || !self::isTimeStampValid($ts)){
            return false;
        }
    
        $expected = $this->calcHash($user, $api_key, $ts_str);
        if(!$expected){
            return false;
        }
    
        if(\Iubar\Misc\Encryption::hashEquals($expected, $hash)){ // hash_equals($expected, $hash); // Solo per PHP > 5.6
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
    
    
    
    private static function isTimeStampValid(\DateTime $dateTime, $max_sec=1200){ // default time-window is 20 minutes
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
            if($dateTime && $diffInSeconds>-$max_sec && $diffInSeconds<$max_sec){
                $b = true; // solo se $dateTime è compreso in un intervallo di +-20 minuti ritorno 'true'
            }
        }
        return $b;
    }
    
    private static function getTimeStampString(){
        $now = new \DateTime();
        $str = $now->format(\DateTime::RFC3339);
        return $str;
    }
    
}