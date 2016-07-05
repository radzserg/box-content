<?php

namespace radzserg\BoxContent;

use DateTime;
use DateTimeZone;

/**
 * Acts as a base class for the different Box View APIs.
 */
abstract class Base
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
     * Take a date in almost any format, and return a date string that is
     * formatted as an RFC 3339 timestamp.
     *
     * @param string|DateTime $date A date string in almost any format, or a
     *                              DateTime object.
     *
     * @return string An RFC 3339 timestamp.
     */
    protected static function date($date)
    {
        if (is_string($date)) $date = new DateTime($date);

        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('c');
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
