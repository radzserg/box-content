<?php

namespace radzserg\BoxContent;

/**
 * Provide access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 */
class User extends BaseEntity
{

    protected $path = '/users';


    /**
     * Creates platform user
     * @param $userParams
     * @return static
     */
    public function createPlatformUser($userParams)
    {
        $userParams['is_platform_access_only'] = true;
        $metadata = $this->client->getRequestHandler(false)->send($this->path, null, $userParams);

        return new static($this->client, $metadata);
    }

    /**
     * Return enterprise users
     * @return array
     */
    public function enterpriseUsers($filterTerm = null, $limit = null, $offset = null, $userType = null)
    {
        $usersMetadata = $this->client->getRequestHandler(false)->send($this->path, [
            'filter_tern' => $filterTerm,
            'limit' => $limit,
            'offset' => $offset,
            'user_type' => $userType
        ]);

        $users = [];
        if (!empty($usersMetadata['entries'])) {
            foreach ($usersMetadata['entries'] as $metadata) {
                $users[] = new static($this->client, $metadata);
            }
        }

        return $users;
    }

}
