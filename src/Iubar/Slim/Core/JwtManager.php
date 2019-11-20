<?php

namespace Iubar\Slim\Core;

use Firebase\JWT\JWT;

class JwtManager {

	const ALGORITHM = 'HS512';

	public static function createToken($email, $api_key) {
		$token_id = base64_encode(random_bytes(32));
		$issued_at = time();
		// $not_before = $issued_at + self::getNotBeforeTime();
		$not_before = $issued_at;
		$expire = $issued_at + self::getExpireTime();
		$server_name = $_SERVER['HTTP_HOST'];

		// Create the token as an array
		// https://tools.ietf.org/html/rfc7519#section-4.1.1
		$data = [
			'iss' => $server_name, // Issuer
			'iat' => $issued_at, // Issued at: time when the token was generated
			'nbf' => $not_before, // Not before
			'jti' => $token_id, // Json Token Id: an unique identifier for the token
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
				'userId' => $email
			]
		];


		// Extract the key, which is coming from the config file.
		// Best suggestion is the key to be a binary string and
		// store it in encoded in a config file.
		// Can be generated with base64_encode(openssl_random_pseudo_bytes(64));
		// keep it secure! You'll need the exact key to verify the token later.
		$secret_key = $api_key;

		// Encode the array to a JWT string.
		// Second parameter is the key to encode the token.
		// The output string can be validated at http://jwt.io/
		return JWT::encode(
			$data, // Data to be encoded in the JWT
			$secret_key, // The signing key
			self::ALGORITHM // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
		);
	}
	
	public static function decodeToken($token, $api_key){
		return JWT::decode($token, $api_key, [self::ALGORITHM]);
	}

	private static function getNotBeforeTime(){
		return 600; // 10 minuti
	}

	private static function getExpireTime(){
		return 1200; // 20 minuti
	}

}
