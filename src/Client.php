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
class Client
{

    const OBTAIN_TOKEN_URL = 'https://api.box.com/oauth2/token';

    const SUBTYPE_ENTERPRISE = 'enterprise';
    const SUBTYPE_USER = 'user';

    /**
     * Box application client ID
     * @var string
     */
    private $clientId;

    /**
     * Box application secret ID
     * @var string
     */
    private $secretId;

    /**
     * Box application public key ID
     * @var string
     */
    private $publicKeyId;

    /**
     * Box enterprise ID
     * @var - string
     */
    private $enterpriseId;

    /**
     * Box user ID
     * @var - string
     */
    private $boxUserId;

    /**
     * A path to private certificate generated for JWT
     * @var string
     */
    private $privateCertPath;

    /**
     * Cache file path to store app access token
     * @var null
     */
    private $appTokenCachePath = null;

    /**
     * Cache file path to store user access token
     * @var null
     */
    private $userTokenCachePath = null;

    /**
     * Password for certificate
     * @var - string
     */
    private $certPassword = null;


    /**
     * @var - access token
     */
    private $accessToken;

    /**
     * The request handler.
     * @var Request
     */
    private $requestHandler;


    /**
     * Specify what kind of token use for Authorization request
     * @var string
     */
    private $useTokenType = self::SUBTYPE_USER;

    public function __construct($config)
    {
        $requiredFields = ['clientId', 'secretId', 'publicKeyId', 'privateCertPath', 'certPassword'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw  new BoxContentException("Required field {$field} is not set");
            } else {
                $this->$field = $config[$field];
            }
        }
        $optionalFields = ['enterpriseId', 'boxUserId', 'appTokenCachePath', 'userTokenCachePath'];
        foreach ($optionalFields as $field) {
            if (!empty($config[$field])) {
                $this->$field = $config[$field];
            }
        }
    }


    /**
     * Set token type for authorization
     * @param $tokenType
     * @throws Exception
     */
    public function setTokenType($tokenType)
    {
        if (!in_array($tokenType, [static::SUBTYPE_ENTERPRISE, static::SUBTYPE_USER])) {
            throw new Exception("Undefined token type");
        }
        $this->accessToken = null;
        $this->useTokenType = $tokenType;
    }


    /**
     * Return the request handler.
     *
     * @return Request The request handler.
     */
    public function getRequestHandler()
    {
        $this->generateAuthToken();
        if (!isset($this->requestHandler)) {
            $this->setRequestHandler(new Request($this->accessToken));
        } else {
            // update access token
            $this->requestHandler->setAccessToken($this->accessToken);
        }
        
        return $this->requestHandler;
    }


    /**
     * Set the request handler.
     *
     * @param Request $requestHandler The request handler.
     *
     * @return void
     */
    public function setRequestHandler($requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    // DOCUMENTS

    /**
     * Get a list of all documents that meet the provided criteria.
     *
     * @param array|null $params Optional. An associative array to filter the
     *                           list of all documents uploaded. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - int|null 'limit' The number of documents to
     *                               return.
     *                             - string|DateTime|null 'createdBefore' Upper
     *                               date limit to filter by.
     *                             - string|DateTime|null 'createdAfter' Lower
     *                               date limit to filter by.
     *
     * @return array An array containing document instances matching the
     *               request.
     * @throws BoxContentException
     */
    public function findDocuments($params = [])
    {
        return Document::find($this, $params);
    }

    /**
     * Create a new document instance by ID, and load it with values requested
     * from the API.
     *
     * @param string $id The document ID.
     *
     * @param array $fields - array of field to return
     * @return Document A document instance using data from the API.
     */
    public function getDocument($id, $fields = [])
    {
        return Document::get($this, $id, $fields);
    }

    /**
     * Upload a local file and return a new document instance.
     *
     * @param resource $file The file resource to upload.
     * @param array|null $params Optional. An associative array of options
     *                           relating to the file upload. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - string|null 'name' Override the filename of
     *                               the file being uploaded.
     *                             - string[]|string|null 'thumbnails' An array
     *                               of dimensions in pixels, with each
     *                               dimension formatted as [width]x[height],
     *                               this can also be a comma-separated string.
     *                             - bool|null 'nonSvg' Create a second version
     *                               of the file that doesn't use SVG, for users
     *                               with browsers that don't support SVG?
     *
     * @return Document A new document instance.
     * @throws BoxContentException
     */
    public function uploadFile($file, $params = [])
    {
        return Document::uploadFile($this, $file, $params);
    }

    // USERS

    public function createPlatformUser($name)
    {
        return User::create($this, $name);
    }

    private function generateAuthToken()
    {
        if ($this->useTokenType == static::SUBTYPE_ENTERPRISE) {
            $this->getAppAccessToken();
        } else {
            $this->getUserAccessToken();
        }

    }


    /**
     * Obtain user access token
     * @throws BoxContentException
     */
    public function getUserAccessToken()
    {
        if (empty($this->boxUserId)) {
            throw new BoxContentException("Property boxUserId must be set to get user access token");
        }
        $this->accessToken = Token::getAccessToken($this->publicKeyId, $this->clientId, $this->secretId, $this->boxUserId,
            static::SUBTYPE_USER, $this->privateCertPath, $this->certPassword, $this->userTokenCachePath);
    }

    /**
     * Obtain app access token
     * @throws BoxContentException
     */
    public function getAppAccessToken()
    {
        if (empty($this->enterpriseId)) {
            throw new BoxContentException("Property enterpriseId must be set to get user access token");
        }
        $this->accessToken = Token::getAccessToken($this->publicKeyId, $this->clientId, $this->secretId, $this->enterpriseId,
            static::SUBTYPE_ENTERPRISE, $this->privateCertPath, $this->certPassword, $this->appTokenCachePath);
    }
}
