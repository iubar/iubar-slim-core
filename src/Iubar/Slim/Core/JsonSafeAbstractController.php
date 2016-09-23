<?php

namespace Iubar\Slim\Core;

use Iubar\Core\JsonAbstractController;
use Iubar\Core\ResponseCode;

/**
 *
 * @author Daniele
 *
 * HMAC security
 */
abstract class JsonSafeAbstractController extends JsonAbstractController {

    abstract protected function getApikey($user);

    private $user = null;
    private $ts_str = null;
    private $hash = null;

    public function __construct(){
        parent::__construct();
    }

    protected function isAuthenticated(){
        $request = $this->app->request;
        $this->user = $request->params('user');
        $this->ts_str = rawurldecode($request->params('ts'));
        $this->hash = $request->params('hash');

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
            $error .= "\ts_str = $this->ts_str";
            $error .= "\user = $this->user";
            $error .= "\hash = $this->hash";
            $code = ResponseCode::BAD_REQUEST;
        }else if(!$ts){
            $error = "Message authentication failed: Wrong timestamp format!";
            $code = ResponseCode::BAD_REQUEST;
        }else if(!self::isTimeStampValid($ts)){
            $error = "Message authentication failed: Time offeset is too big !";
            $code = ResponseCode::UNAUTHORIZED;
        }else{
            $api_key = $this->getApikey($this->user);
            // $new_api_key = \Application\Utils\ApiKeyUtils::create();
            $expected_hash = $this->calcHash(); // WARNING: security breach !!!

            if($expected_hash != $this->hash){
                $error = "Message authentication failed: Wrong hash !";
                $code = ResponseCode::UNAUTHORIZED;
            }
        }
        $this->responseStatus($code, array(), $error);

    }

    private function calcHash(){
        $hash = null;
        $request = $this->app->request;
        if($this->user && $this->ts_str && $api_key){
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $request->getRootUri() . $request->getResourceUri();
            $data = $url . $this->user . $this->ts_str . $api_key;
            $raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
            $hash = base64_encode($raw_hash);
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
        if(!$ts || !self::isTimeStampValid($ts)){
            return false;
        }

        $expected = $this->calcHash();
        if(!$expected){
            return false;
        }

         

        if(\Iubar\Misc\Encryption::hashEquals($expected, $this->hash)){ // hash_equals($expected, $this->hash); // Solo per PHP > 5.6
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



    private static function isTimeStampValid(\DateTime $dateTime, $max_sec=1200){ // here default time-window is 20 minutes (1200 seconds)
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
        // Nota: il formato data RFC3339 presenta il segno "+" nella descrizione della tiemzone
        // per tale motivo non utilizzo urlencode() / urldecode()
        $str = $now->format(\DateTime::RFC3339);
        return $str;
    }

}