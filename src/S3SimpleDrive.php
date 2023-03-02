<?php
namespace EBethus\CacheRequest;

use Imannms\LaravelS3CacheDriver\S3Store;

class S3SimpleDrive extends S3Store
{

    /**
     * Get the full path for the given cache key.
     *
     * @param  string  $key
     * @return string
     */
    protected function path($key)
    {
        $parts = \Str::slug($key);
        return "{$this->directory}/$parts";
    }
}
