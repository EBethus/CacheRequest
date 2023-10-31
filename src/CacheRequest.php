<?php
namespace EBethus\CacheRequest;

use Carbon\Carbon;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use Symfony\Component\HttpFoundation\Response;

class CacheRequest
{
    public $url;

    // media hora de cache
    public $cachetime = 1800;

    // mostrar info para debug
    public $debug = false;

    protected $status = Response::HTTP_OK;

    protected $driver;

    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    protected function request($method, $path, $param = [], callable $cb = null, $key = null)
    {
        $url = trim($this->url, '/');
        $path = trim($path, '/');

        if (empty($url)) {
            throw new \RuntimeException('URL is empty, set one!');
        }

        if (!in_array($method, ['get', 'post', 'put', 'delete', 'patch'])) {
            throw new \RuntimeException("Error debe seleccionar el método");
        }

        $absURL = rtrim("{$url}/$path", '/');

        if (is_null($key)) {
            $key = md5($absURL . json_encode($param). $method);
        }

        $data = Cache::store($this->driver)->get($key);
        if ($data) {
            return $data;
        }

        $request = Http::withHeaders([
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
            'User-Agent' => 'Mozilla/5.0'
        ]);

        if (is_callable($cb)) {
            $cb($request);
        }

        try {
            $response = $request->$method($absURL, $param);
        } catch (\Exception $exception) {
            $error = !\App::environment('production') ? $exception->getMessage() : 'Error de sistema';
            throw new \Exception($error);
        }

        $this->status = $response->status();
        $contentType = $response->header('Content-Type');
        $isJSON = strpos($contentType, 'json') !== false; 

        if (!in_array($this->status, [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED])) {
            $error = $isJSON ? $response->json() : $response->body();
            $info =  [
                'status' => $response->status(),
                'timestamp'=> Carbon::now()->format('d-m-Y H:i:s'),
                'resultado' => $this->status == 404 ? 'No Encontrado': 'ERROR_DATOS',
                'description' => $error,
            ];

            if ($this->debug) {
                $info['url'] = $absURL;
                $info['param'] = $param;
                $info['key'] = $key;
            }

            return $info;
        }

        $data = $isJSON ? $response->json() : $response->body();

        if ($method == 'get' && in_array($this->status, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]) && $key !== false) {
            \Cache::store($this->driver)->put($key, $data);
        }

        return $data;
    }

    public function post($path, $param = [], callable $cb = null, $key = null)
    {
        return $this->request('post', $path, $param, $cb, $key);
    }

    public function get($path, $param = [], callable $cb = null, $key = null)
    {
        return $this->request('get', $path, $param, $cb, $key);
    }

    public function put($path, $param = [], callable $cb = null, $key = null)
    {
        return $this->request('put', $path, $param, $cb, $key);
    }

    public function delete($path, $param = [], callable $cb = null, $key = null)
    {
        return $this->request('delete', $path, $param, $cb, $key);
    }

    public function patch($path, $param = [], callable $cb = null, $key = null)
    {
        return $this->request('patch', $path, $param, $cb, $key);
    }
}