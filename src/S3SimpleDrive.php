<?php
namespace EBethus\CacheRequest;

use Imannms\LaravelS3CacheDriver\S3Store;

class S3SimpleDrive extends S3Store
{

        /**
     * The root directory.
     *
     * @var string
     */
    protected $directory = '';

    /**
     * Get the full path for the given cache key.
     *
     * @param  string  $key
     * @return string
     */
    protected function path($key)
    {
        $parts = $key;
        return "{$this->directory}/$parts";
    }
}
