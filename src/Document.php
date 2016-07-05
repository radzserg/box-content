<?php

namespace radzserg\BoxContent;

/**
 * Provide access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 */
class Document extends Base
{
    /**
     * Document error codes.
     * @const string
     */
    const INVALID_FILE_ERROR = 'invalid_file';
    const INVALID_RESPONSE_ERROR = 'invalid_response';

    /**
     * An alternate hostname that file upload requests are sent to.
     * @const string
     */
    const FILE_UPLOAD_HOST = 'upload.box.com';

    /**
     * The Document API path relative to the base API path.
     * @var string
     */
    public static $path = '/files';


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
     * Download a thumbnail of a specific size for a file.
     *
     * @param int $width The width of the thumbnail in pixels.
     * @param int $height The height of the thumbnail in pixels.
     *
     * @param string $ext
     * @return string The contents of the downloaded thumbnail.
     */
    public function thumbnail($width, $height, $ext = 'jpg')
    {
        $path = static::$path . '/' . $this->id . "/thumbnail.{$ext}" ;
        $getParams = [
            'min_height' => $height,
            'min_width' => $width,
        ];
        return static::request($this->client, $path, $getParams, null, [
            'rawResponse' => true,
        ]);
    }


    /**
     * Create a new document instance by ID, and load it with values requested
     * from the API.
     *
     * @param Client $client The client instance to make requests from.
     * @param string $id The document ID.
     *
     * @param array $fields - array of fields to return
     * @return Document A document instance using data from the API.
     */
    public static function get($client, $id, $fields = [])
    {
        $getParams = [];
        if (!empty($fields)) {
            $getParams['fields'] = implode(',', $fields);
        }
        $metadata = static::request($client,  static::$path . '/' . $id, $getParams);

        return new self($client, $metadata);
    }

    /**
     * Upload a local file and return a new document instance.
     *
     * @param Client $client The client instance to make requests from.
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
    public static function uploadFile($client, $file, $params = [])
    {
        if (!is_resource($file)) {
            $message = '$file is not a valid file resource.';
            return static::error(static::INVALID_FILE_ERROR, $message);
        }

        return static::upload($client, $params, [
            'file' => $file,
            'host' => static::FILE_UPLOAD_HOST,
        ]);
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
     * Generic upload function used by the two other upload functions, which are
     * more specific than this one, and know how to handle upload by URL and
     * upload from filesystem.
     *
     * @param Client $client The client instance to make requests from.
     * @param array|null $postParams An associative array of POST params to be
     *                               sent in the body.
     * @param array|null $options An associative array of request options that
     *                            may modify the way the request is made.
     *
     * @return Document A new document instance.
     * @throws BoxContentException
     */
    private static function upload(
        $client,
        $postParams = [],
        $options = []
    )
    {
        $options['basePath'] = '';
        $metadata = static::request($client, '/api/2.0/files/content', null, $postParams, $options);
        return new self($client, $metadata['entries'][0]);
    }
}
