<?php

namespace Iubar\Slim\Core;

use Iubar\Slim\Core\ResponseCode;

abstract class JsonAbstractController extends AbstractController {

    public function __construct(){
        parent::__construct();
        $this->app->response->header('Access-Control-Allow-Origin', '*');
        $this->app->response->header('Content-Type', 'application/json; charset=utf-8');
    }


    protected function readData(){
    	//$data = json_decode($request->getBody(), true) ?: $request->params();
    	$data = null;

    	$request = $this->app->request;
    	$ct = $request->getContentType();

    	$pos1 = strpos($ct, 'application/json');
    	$pos2 = strpos($ct, 'multipart/form-data');
    	// $pos3 = strpos($ct, 'application/x-www-form-urlencoded');

    	if ($pos1 !== false) { //  'application/json'
    		$body = $request->getBody();  // When you post application/json data it will not populate $_POST and it will not be available from $app->request->post()
    		$data = json_decode($body, true);
    	}else if($pos2 !== false){
    		$data = [
    			'files' => $_FILES,
    			'data' => $_REQUEST
    		];
    	}else {
    		$data = $request->params(); // The params() method will first search PUT variables, then POST variables, then GET variables. If no variables are found, null is returned.
    		// E' inutile invocare rawurldecode(), poichè  $request->params() effettua già la stessa decodifica
    	}

    	return $data;
    }

    protected function getJsonDecodedFromPost(){
        // $this->app->response()->header("Content-Type", "application/json");
        // obtain the JSON of the body of the request
        $post = json_decode($this->app->request()->getBody(), true); // make it a PHP associative array
        return $post;
    }

    protected function responseStatus($code, array $json_array = array(), $message = null){
        if ($message === null){
            if ($code === ResponseCode::SUCCESS){
                $message = 'Richiesta effettuata con successo';
            } else if ($code === ResponseCode::INTERNAL_SERVER_ERROR){
                $message = 'Si &egrave; verificato un errore interno';
            } else if ($code === ResponseCode::BAD_REQUEST){
                $message = 'Richiesta errata';
            }else{
                $message = 'no description';
            }
        }

        $response_array = array();
        $response_array['code'] = $code;
        $response_array['response'] = $message;

        if ( $json_array !== null && count($json_array) > 0){
            $response_array['data'] = $json_array;
        }

        $result = json_encode($response_array, JSON_PRETTY_PRINT);
        if( $result === false ) {
            $error = $this->getJsonEncodeError();
            throw new \Exception($error . " (error code " . json_last_error() . ")");
        } else {
            $this->app->response->setStatus($code);
            $this->app->response->write($result);
        }
    }

    private function getJsonEncodeError(){
        $error = null;
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ' - No errors';
                break;
            case JSON_ERROR_DEPTH:
                $error = ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error = ' - Unknown error';
                break;
        }
        return $error;
    }


    protected function handleException($e){
        $this->app->log->error($e->getMessage());
        if($this->app->config('debug2')){
            throw $e; // Whoops will output the error
        }else{
            $this->responseStatus(ResponseCode::INTERNAL_SERVER_ERROR); // Altrimenti Slim restituirebbe un messaggio di errore in html
        }
    }


}