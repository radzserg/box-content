<?php

namespace radzserg\BoxContent;

/**
 * Provide access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 */
class User extends BaseEntity
{

  


    /**
     * Creates platform user
     * @param $userParams
     * @return static
     */
    public function createPlatformUser($userParams)
    {
        $userParams['is_platform_access_only'] = true;
        $metadata = $this->client->getRequestHandler(false)->send(static::$path, null, $userParams);

        return new static($this->client, $metadata);
    }

}
