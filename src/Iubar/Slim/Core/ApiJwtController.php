<?php
namespace Iubar\Slim\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;

abstract class ApiJwtController extends JsonAbstractController {

	abstract protected function getApikey($email);

	public function __construct() {
		parent::__construct();
	}

	protected function isAuthenticated() {
		$token = null;
		$email = null;
		$data = $this->readData();

		if (isset($data['auth'])){
			$auth = $data['auth'];
			if(isset($auth['token'])){
				$token = $auth['token'];
			}

			if(isset($auth['email'])){
				$email = $auth['email'];
			}
		} else {
			if(isset($data['token'])){
				$token = $data['token'];
			}

			if(isset($data['email'])){
				$email = $data['email'];
			}
		}

		if ($token && $email) {
			try {
				// decode the jwt using the key from config
				$secret_key = $this->getApikey($email);

				// You can add a leeway to account for when there is a clock skew times between
				// the signing and verifying servers. It is recommended that this leeway should
				// not be bigger than a few minutes.
				// Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
				JWT::$leeway = 60; // $leeway in seconds

				$decoded = JWT::decode($token, new Key($secret_key, JwtManager::ALGORITHM));

				// NOTE: This will now be an object instead of an associative array. To get
				// an associative array, you will need to cast it as such:
				$decoded_array = (array) $decoded;

				if (!empty($decoded_array)) {
					return true;
				}
			} catch (SignatureInvalidException $e) {
				$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (signature invalid): ' . $e->getMessage());
			} catch (BeforeValidException $e) {
				$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (before valid): ' . $e->getMessage());
			} catch (ExpiredException $e) {
				$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (expired): ' . $e->getMessage());
			}
		} else {
			// The request lacks the authorization token
			$this->responseStatus(ResponseCode::BAD_REQUEST, [], 'Token or email not found in the request');
		}

		return false;
	}

	public function buildJwtToken($email) {
		$api_key = $this->getApikey($email);
		$jwt = JwtManager::createToken($email, $api_key);
		$unencoded_array = [
			'jwt' => $jwt
		];

		return json_encode($unencoded_array);
	}

	protected function decodeJwtToken($jwt) {
		$secret_key = $this->getApikey($email);
		JWT::$leeway = 60; // $leeway in seconds
		$token = JWT::decode($jwt, $secret_key, array(
			JwtManager::ALGORITHM
		));
		if (!$this->isJwtArrayValid($token)) {
			$this->responseStatus(ResponseCode::UNAUTHORIZED, [], 'Unauthorized (wrong Jwt array)');
		}
		return $token;
	}

	protected function getJwtToken($email) {
		$token = null;
		$api_key = $this->getApikey($email);
		if ($this->isUserRegistered($email)) {
			$token = JwtManager::createToken($email, $api_key);
		} else {
			throw new \InvalidArgumentException('email or api key wrong');
		}
		return $token;
	}

	private function isJwtArrayValid(array $data) {
		$b = false;
		if (isset($data['data'])) {
			$email = $data['data']->userId;
			if ($email !== null) {
				$b = $this->isUserRegistered($email);
			}
		}
		return $b;
	}

	private function isUserRegistered($email) {
		if (!$this->getApikey($email)) {
			return false;
		}
		return true;
	}
}
