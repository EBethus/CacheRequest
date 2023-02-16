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

    protected $status = Response::HTTP_OK;

    protected function request($method, $path, $param = [], callable $cb = null)
    {
        $url = trim($this->url, '/');
        $path = trim($path, '/');

        if (empty($url)) {
            throw new \RunException('URL is empty, set one!');
        }

        $absURL = "{$url}/$path";
        $key = md5($absURL . json_encode($param). $method);
        $data = \Cache::remember($key, $this->cachetime, function () use ($absURL, $param, $cb, $method, $key) {
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
            if ($status != Response::HTTP_OK) {
                $error = $response->json();
                return [
                    'status' => $response->status(),
                    'timestamp'=>Carbon::now()->format('d-m-Y H:i:s'),
                    'resultado' => $status == 404 ? 'No Encontrado': 'ERROR_DATOS',
                    'description' => $error,
                ];
            } else {
                return $response->json();
            }
        });
        
        if ($this->status != Response::HTTP_OK) {
            \Cache::forget($key);
        }

        return $data;
    }

    public function post($path, $param = [], callable $cb = null)
    {
        return $this->request('post', $path, $param, $cb);
    }

    public function get($path, $param = [], callable $cb = null)
    {
        return $this->request('get', $path, $param, $cb);
    }
}
