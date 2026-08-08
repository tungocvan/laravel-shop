<?php

namespace Modules\Facebook\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Facebook\Exceptions\FacebookApiException;

class FacebookGraphClient
{
    public function __construct(
        private readonly FacebookErrorMapper $errors,
        private readonly FacebookRedactor $redactor,
    ) {}

    public function get(string $endpoint, array $query = [], ?string $accessToken = null, bool $versioned = true): array
    {
        return $this->request('get', $endpoint, $query, $accessToken, $versioned);
    }

    public function post(string $endpoint, array $data = [], ?string $accessToken = null, bool $versioned = true): array
    {
        return $this->request('post', $endpoint, $data, $accessToken, $versioned);
    }

    public function postMultipart(string $endpoint, array $data, string $field, string $path, ?string $accessToken = null): array
    {
        if ($accessToken) {
            $data['access_token'] = $accessToken;
        }

        $url = $this->url($endpoint);
        $response = Http::timeout((int) config('facebook.http_timeout', 30))
            ->connectTimeout((int) config('facebook.connect_timeout', 10))
            ->attach($field, fopen($path, 'r'), basename($path))
            ->post($url, $data);

        $json = $response->json() ?? [];

        if ($response->successful() && ! isset($json['error'])) {
            return is_array($json) ? $json : [];
        }

        $exception = $this->errors->fromResponse($response->status(), is_array($json) ? $json : []);
        $this->logFailure('post', $url, $response->status(), $data, $exception);

        throw $exception;
    }

    public function url(string $endpoint, bool $versioned = true): string
    {
        $base = rtrim((string) config('facebook.graph_base_url'), '/');
        $endpoint = ltrim($endpoint, '/');

        if (! $versioned) {
            return $base.'/'.$endpoint;
        }

        return $base.'/'.trim((string) config('facebook.graph_version'), '/').'/'.$endpoint;
    }

    private function request(string $method, string $endpoint, array $payload, ?string $accessToken, bool $versioned): array
    {
        if ($accessToken) {
            $payload['access_token'] = $accessToken;
        }

        $attempts = max(1, (int) config('facebook.max_retries', 3));
        $delay = max(0, (int) config('facebook.retry_delay', 1000));
        $url = $this->url($endpoint, $versioned);

        for ($try = 1; $try <= $attempts; $try++) {
            try {
                $response = Http::timeout((int) config('facebook.http_timeout', 30))
                    ->connectTimeout((int) config('facebook.connect_timeout', 10))
                    ->asForm()
                    ->{$method}($url, $payload);

                $json = $response->json() ?? [];

                if ($response->successful() && ! isset($json['error'])) {
                    return is_array($json) ? $json : [];
                }

                $exception = $this->errors->fromResponse($response->status(), is_array($json) ? $json : []);
                $this->logFailure($method, $url, $response->status(), $payload, $exception);

                if (! $exception->retryable() || $try === $attempts) {
                    throw $exception;
                }
            } catch (ConnectionException $exception) {
                if ($try === $attempts) {
                    $mapped = $this->errors->fromResponse(503, [
                        'error' => [
                            'message' => 'Không thể kết nối Meta Graph API: '.$exception->getMessage(),
                            'type' => 'ConnectionException',
                            'code' => 'connection_failure',
                        ],
                    ]);

                    throw $mapped;
                }
            }

            usleep($delay * 1000);
        }

        throw $this->errors->fromResponse(500, ['error' => ['message' => 'Meta Graph API request failed.']]);
    }

    private function logFailure(string $method, string $url, ?int $status, array $payload, FacebookApiException $exception): void
    {
        Log::channel('facebook')->warning('Meta Graph API request failed', [
            'method' => strtoupper($method),
            'endpoint' => strtok($url, '?'),
            'http_status' => $status,
            'payload' => $this->redactor->redact($payload),
            'error_code' => $exception->error->code,
            'error_subcode' => $exception->error->subcode,
            'error_type' => $exception->error->type,
            'trace_id' => $exception->error->traceId,
        ]);
    }
}
