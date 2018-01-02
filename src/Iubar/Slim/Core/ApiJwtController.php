<?php
namespace Iubar\Slim\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;

abstract class ApiJwtController extends JsonAbstractController {

	abstract protected function getApikey($user_id);

	public function __construct() {
		parent::__construct();
	}

	protected function isAuthenticated() {
		$request = \Slim\Slim::getInstance()->request;
		$token = $request->params('token');
		$user_id = $request->params('user_id');
		if ($token && $user_id) {
			try {
				// decode the jwt using the key from config
				$secret_key = $this->getApikey($user_id);

				// You can add a leeway to account for when there is a clock skew times between
				// the signing and verifying servers. It is recommended that this leeway should
				// not be bigger than a few minutes.
				// Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
				JWT::$leeway = 60; // $leeway in seconds

				$decoded = JWT::decode($token, $secret_key, array(
					JwtManager::ALGORITHM
				));

				// NOTE: This will now be an object instead of an associative array. To get
				// an associative array, you will need to cast it as such:
				$decoded_array = (array) $decoded;

				if (!empty($decoded_array)) {
					return true;
				}
			} catch (SignatureInvalidException $e) {
				$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (signature invalid)');
			} catch (BeforeValidException $e) {
				$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (before valid)');
			} catch (ExpiredException $e) {
				$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (expired)');
			}
		} else {
			// The request lacks the authorization token
			$this->responseStatus(ResponseCode::BAD_REQUEST, [], 'Token or user_id not found in request');
		}

		return false;
	}

	public function buildJwtToken($user_id) {
		$api_key = $this->getApikey($user_id);
		$jwt = JwtManager::createToken($user_id, $api_key);
		$unencoded_array = [
			'jwt' => $jwt
		];

		return json_encode($unencoded_array);
	}

	protected function decodeJwtToken($jwt) {
		$secret_key = $this->getApikey($user_id);
		JWT::$leeway = 60; // $leeway in seconds
		$token = JWT::decode($jwt, $secret_key, array(
			JwtManager::ALGORITHM
		));
		if (!$this->isJwtArrayValid($token)) {
			$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (wrong Jwt array)');
		}
		return $token;
	}

	protected function getJwtToken($user_id) {
		$token = null;
		$api_key = $this->getApikey($user_id);
		if ($this->isUserRegistered($user_id)) {
			$token = JwtManager::createToken($user_id, $api_key);
		} else {
			throw new \InvalidArgumentException('user_id or api key wrong');
		}

		return $token;
	}

	private function isJwtArrayValid(array $data) {
		$b = false;
		if (isset($data['data'])) {
			$user_id = $data['data']->userId;
			if ($user_id !== null) {
				$b = $this->isUserRegistered($user_id);
			}
		}

		return $b;
	}

	private function isUserRegistered($user_id) {
		if (!$this->getApikey($user_id)) {
			return false;
		}
		return true;
	}
}
