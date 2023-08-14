<?php
namespace EBethus\CacheRequest;

use Carbon\Carbon;
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

        $absURL = rtrim("{$url}/$path", '/');
        $key = $key ?? md5($absURL . json_encode($param). $method);
        $data = \Cache::store($this->driver)->remember($key, $this->cachetime, function () use ($absURL, $param, $cb, $method, $key) {
            try {
                $request = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Cache-Control' => 'no-cache',
                    'User-Agent' => 'Mozilla/5.0'
                ]);
                if ($cb) {
                    $cb($request);
                }

                switch ($method) {
                    case 'get':
                        $response = $request->get($absURL, $param);
                        break;
                    case 'post':
                        $response = $request->post($absURL, $param);
                        break;
                    default:
                        throw new \Exception("Error debe seleccionar un metodo");
                        break;
                }
            } catch (\Exception $exception) {
                $error = !\App::environment('production') ? $exception->getMessage() : 'Error de sistema';
                throw new \Exception($error);
            }

            $status = $response->status();

            $this->status = $status;
            
            $contentType = $response->header('Content-Type');
            $isJSON = strpos($contentType, 'json') !== false; 
            if ($status != Response::HTTP_OK) {
                $error = $isJSON ? $response->json() : $response->body();
                $info =  [
                    'status' => $response->status(),
                    'timestamp'=> Carbon::now()->format('d-m-Y H:i:s'),
                    'resultado' => $status == 404 ? 'No Encontrado': 'ERROR_DATOS',
                    'description' => $error,
                ];

                if ($this->debug) {
                    $info['url'] = $absURL;
                    $info['param'] = $param;
                    $info['key'] = $key;
                }

                return $info;
            } else {
                return $isJSON ? $response->json() : $response->body();
            }
        });
        
        if ($this->status != Response::HTTP_OK) {
            \Cache::store($this->driver)->forget($key);
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