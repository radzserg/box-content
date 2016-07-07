<?php

namespace radzserg\BoxContent;

use DateTime;
use DateTimeZone;

/**
 * Acts as a base class for the different Box View APIs.
 */
abstract class BaseEntity
{
    /**
     * The API path relative to the base API path.
     * @var string
     */
    public static $path = '/';

    /**
     * The client instance to make requests from.
     * @var Client
     */
    protected $client;


    /**
     * The document ID.
     * @var string
     */
    private $id;

    /**
     * The document metadata.
     * @var array
     */
    private $data;


    /**
     * Instantiate the document.
     *
     * @param Client $client The client instance to make requests from.
     * @param array $data An associative array to instantiate the object with.
     *                    Use the following values:
     *                      - string 'id' The document ID.
     *                      - string|DateTime 'createdAt' The date the document
     *                        was created.
     *                      - string 'name' The document title.
     *                      - string 'status' The document status, which can be
     *                        'queued', 'processing', 'done', or 'error'.
     */
    public function __construct($client, $data)
    {
        $this->client = $client;
        $this->id = $data['id'];

        $this->setValues($data);
    }

    /**
     * Return document data
     * @param $key
     * @return mixed|null
     */
    public function getData($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Get the document ID.
     *
     * @return string The document ID.
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Update the current document instance with new metadata.
     *
     * @param array $data An associative array to instantiate the object with.
     *                    Use the following values:
     *                      - string|DateTime 'createdAt' The date the document
     *                        was created.
     *                      - string 'name' The document title.
     *                      - string 'status' The document status, which can be
     *                        'queued', 'processing', 'done', or 'error'.
     *
     * @return void
     */
    private function setValues($data)
    {
        $this->id = $data['id'];
        $this->data = $data;
    }
    
    /**
     * Handle an error. We handle errors by throwing an exception.
     *
     * @param string $error An error code representing the error
     *                      (use_underscore_separators).
     * @param string|null $message The error message.
     *
     * @return void
     * @throws BoxContentException
     */
    protected static function error($error, $message = null)
    {
        $exception = new BoxContentException($message);
        $exception->errorCode = $error;

        throw $exception;
    }

    /**
     * Send a new request to the API.
     *
     * @param Client $client The client instance to make requests from.
     * @param string $requestPath The path to add after the base path.
     * @param array|null $getParams Optional. An associative array of GET params
     *                              to be added to the URL.
     * @param array|null $postParams Optional. An associative array of POST
     *                               params to be sent in the body.
     * @param array|null $requestOptions Optional. An associative array of
     *                                   request options that may modify the way
     *                                   the request is made.
     *
     * @return array|string The response is pass-through from Request.
     * @throws BoxContentException
     */
    protected static function request(
        $client,
        $requestPath,
        $getParams = [],
        $postParams = [],
        $requestOptions = []
    )
    {
        return $client->getRequestHandler()->send(
            $requestPath,
            $getParams,
            $postParams,
            $requestOptions
        );
    }
}
