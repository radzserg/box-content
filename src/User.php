<?php

namespace radzserg\BoxContent;

/**
 * Provide access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 */
class User extends Base
{

    /**
     * The Document API path relative to the base API path.
     * @var string
     */
    public static $path = '/users';


    public static function create($client, $userParams = [])
    {
        $metadata = static::request($client, '', null, $userParams);

        return new static($client, $metadata['entries'][0]);
    }

}
