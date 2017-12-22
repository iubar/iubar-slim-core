<?php
namespace Iubar\Slim\Core;

use Iubar\Slim\Core\JsonAbstractController;
use Iubar\Slim\Core\ResponseCode;
use Iubar\Misc\Encryption;
use Firebase\JWT\JWT;

abstract class ApiJwtController extends JsonAbstractController {

	abstract protected function getApikey($user_id);

	private $algorithm = 'HS512';

	public function __construct() {
		parent::__construct();
	}

	protected function isAuthenticated() {
		$jwt = $request->params('jwt');
		if ($jwt) {
			try {
				// decode the jwt using the key from config
				$secretKey = $this->getApikey($user_id);

				// You can add a leeway to account for when there is a clock skew times between
				// the signing and verifying servers. It is recommended that this leeway should
				// not be bigger than a few minutes.
				// Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
				JWT::$leeway = 60; // $leeway in seconds

				$token = JWT::decode($jwt, $secretKey, array(
					$this->algorithm
				));

				// NOTE: This will now be an object instead of an associative array. To get
				// an associative array, you will need to cast it as such:
				$decoded_array = (array) $decoded;

				if (!empty($decoded_array)) {
					return true;
				}
			} catch (\Exception $e) {
				// the token was not able to be decoded.
				// this is likely because the signature was not able to be verified (tampered token)
				header('HTTP/1.0 401 Unauthorized');
			}
		} else {
			// The request lacks the authorization token
			header('HTTP/1.0 400 Bad Request');
			echo 'Token not found in request';
		}

		return false;
	}

	public function buildJwtToken($user_id) {
		$jwt = $this->createToken($user_id);
		$unencodedArray = [
			'jwt' => $jwt
		];
		$jwt_as_json = json_encode($unencodedArray);
		return $jwt_as_json;
	}

	private function createToken($user_id) {
		$tokenId = base64_encode(mcrypt_create_iv(32));
		$issued_at = time();
		$not_before = $issuedAt + 10; // Adding 10 seconds
		$expire = $notBefore + 60; // Adding 60 seconds
		$server_name = $_SERVER['HTTP_HOST'];

		// Create the token as an array
		// https://tools.ietf.org/html/rfc7519#section-4.1.1
		$data = [
			'iss' => $server_name, // Issuer
			'iat' => $issued_at, // Issued at: time when the token was generated
			'nbf' => $not_before, // Not before
			'jti' => $tokenId, // Json Token Id: an unique identifier for the token
			'exp' => $expire, // Expire

			/*
			 * // Example: https://github.com/settings/tokens (see TravisCI)
			 * 'scopes' => {
			 * 'users' => {
			 * 'actions' => ['read', 'create']
			 * },
			 * 'users_app_metadata' => {
			 * 'actions' => ['read', 'create']
			 * }
			 * },
			 */

			'data' => [ // Data related to the signer user
			            // 'userId' => $rs['id'], // userid from the users table
			            // 'userName' => $username, // User name
				'userId' => $user_id
			]
		];


		// Extract the key, which is coming from the config file.
		// Best suggestion is the key to be a binary string and
		// store it in encoded in a config file.
		// Can be generated with base64_encode(openssl_random_pseudo_bytes(64));
		// keep it secure! You'll need the exact key to verify the token later.
		$secret_key = $this->getApikey($user_id);

		// Encode the array to a JWT string.
		// Second parameter is the key to encode the token.
		// The output string can be validated at http://jwt.io/
		$jwt = JWT::encode($data, // Data to be encoded in the JWT
			$secret_key, // The signing key
			$this->algorithm); // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
	}

	protected function decodeJwtToken($jwt) {
		$secretKey = $this->getApikey($user_id);
		JWT::$leeway = 60; // $leeway in seconds
		$token = JWT::decode($jwt, $secretKey, array(
			$this->algorithm
		));
		if (!$this->isJwtArrayValid($token)) {
			throw new \LogicException('Wrong Jwt array'); // TODO: meglio restituire errore 401 Unauthorized
		}
		return $token;
	}

	protected function getJwtToken($email) {
		$token = null;
		$api_key = $this->getApikey($email);
		if ($this->isUserRegistered($email)) {
			$token = $this->createToken($email);
		} else {
			throw new \InvalidArgumentException('Email or api key wrong');
		}

		return $token;
	}

	private function isJwtArrayValid(array $data) {
		$b = false;
		if (isset($data['data'])) {
			$email = $data['data']->user;
			if ($email !== null) {
				$b = $this->isUserRegistered($email);
			}
		}

		return $b;
	}

	private function isUserRegistered($email) {
		if (!$this->getApikey($email)) {
			return false
		}
		return true;
	}
}
