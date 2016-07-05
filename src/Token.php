<?php

namespace radzserg\BoxContent;

use Exception;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Builder;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Provides access to the Box Content API.
 * @see - https://docs.box.com/reference#file-object
 */
class Token
{

    const OBTAIN_TOKEN_URL = 'https://api.box.com/oauth2/token';

    /**
     * Obtain access token
     * @param $publicKeyId
     * @param $clientId
     * @param $secretId
     * @param $subject
     * @param $boxSubType
     * @param $privateCertPath
     * @param $certPassword
     * @param null $accessTokenCachePath
     * @return null
     * @throws BoxContentException
     */
    public static function getAccessToken($publicKeyId, $clientId, $secretId, $subject, $boxSubType, $privateCertPath,
                                    $certPassword, $accessTokenCachePath = null)
    {
        if ($accessTokenCachePath && file_exists($accessTokenCachePath)) {
            $token = @file_get_contents($accessTokenCachePath);
            if ($token) {
                $token = json_decode($token, true);
                if ($token['expires_at'] > time()) {
                    return $token['access_token'];
                }
            }
        }

        $signer = new Sha256();
        $jwt = (new Builder())
            ->setHeader('alg', 'RS256')
            ->setHeader('typ', 'JWT')
            ->setHeader('kid', $publicKeyId)
            ->setIssuer($clientId)
            ->setAudience(static::OBTAIN_TOKEN_URL)
            ->setSubject($subject)
            ->set('box_sub_type', $boxSubType)
            ->setId(uniqid() . uniqid())
            ->setExpiration(time() + 30)
            ->sign($signer, new Key("file://{$privateCertPath}", $certPassword)) // creates a signature using your private key
            ->getToken();

        $assertion = (string)$jwt;

        $token = null;
        try {
            $guzzle = new GuzzleClient();
            $response = $guzzle->post(static::OBTAIN_TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                    'client_id' => $clientId,
                    'client_secret' => $secretId
                ]
            ]);

            $token = json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw new BoxContentException("Cannot obtain access token. Details: " . $e->getMessage());
        }

        if (!$token) {
            return null;
        }

        $accessToken = $token['access_token'];
        $expiresAt = time() + $token['expires_in'] - 5;

        if ($accessTokenCachePath) {
            $dirPath = dirname($accessTokenCachePath);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0777, true);
            }
            if (!@file_put_contents($accessTokenCachePath, json_encode([
                'access_token' => $accessToken,
                'expires_at' => $expiresAt
            ]))) {
                throw new BoxContentException("Cannot save access token to cache file.");
            }
            @chmod($accessTokenCachePath, 0666);
        }

        return $accessToken;
    }
}
