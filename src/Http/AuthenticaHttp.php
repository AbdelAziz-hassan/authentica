<?php

namespace AbdelAzizHassan\Authentica\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use AbdelAzizHassan\Authentica\Support\CircuitBreaker;

class AuthenticaHttp
{
    public function __construct(
        protected string $host,
        protected string $apiKey,
        protected int $timeout = 10,
        protected int $connectTimeout = 5,
        protected ?CircuitBreaker $breaker = null,
        protected array $circuitCfg = [], // <- pass config to know which statuses trip the breaker
    ) {}

    protected function client()
    {
        return Http::baseUrl($this->host)
            ->acceptJson()
            ->withHeaders([
                'X-Authorization' => $this->apiKey,
                'Content-Type'    => 'application/json',
            ])
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);
    }

    protected function failureStatuses(): array
    {
        return $this->circuitCfg['failure_statuses'] ?? [429, 500, 502, 503, 504];
    }

    protected function quickFailResponse(): array
    {
        return [
            'ok'      => false,
            'status'  => 503,
            'body'    => null,
            'message' => 'Upstream temporarily unavailable (circuit open)',
            'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
        ];
    }

    protected function markOutcome(bool $ok, int $status): void
    {
        if (!$this->breaker) return;

        if ($ok) {
            $this->breaker->recordSuccess();
            return;
        }

        // Network (0) or configured statuses trip the breaker; other 4xx don't.
        if ($status === 0 || in_array($status, $this->failureStatuses(), true)) {
            $this->breaker->recordFailure();
        } else {
            $this->breaker->recordSuccess(); // treat non-configured 4xx as non-upstream-failure
        }
    }

    /** @return array{ok:bool,status:int,body:array|null,message:?string,circuit?:array} */
    public function get(string $path): array
    {
        if ($this->breaker && !$this->breaker->allow()) {
            return $this->quickFailResponse();
        }

        try {
            $resp   = $this->client()->get($path);
            $status = $resp->status();
            $body   = $resp->json() ?? [];
            $ok     = $resp->successful();

            $this->markOutcome($ok, $status);

            return [
                'ok' => $ok,
                'status' => $status,
                'body' => $body['data'] ?? $body,
                'message' => is_array($body) && array_key_exists('message', $body) ? (string) $body['message'] : null,
                'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
            ];
        } catch (ConnectionException $e) {
            $this->markOutcome(false, 0);
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => 'Network error: '.$e->getMessage(),
                'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
            ];
        }
    }

    public function post(string $path, array $json): array
    {
        if ($this->breaker && !$this->breaker->allow()) {
            return $this->quickFailResponse();
        }

        try {
            $resp   = $this->client()->asJson()->post($path, $json);
            $status = $resp->status();
            $body   = $resp->json() ?? [];
            $ok     = $resp->successful();

            $this->markOutcome($ok, $status);

            return [
                'ok' => $ok,
                'status' => $status,
                'body' => $body['data'],
                'message' => is_array($body) && array_key_exists('message', $body) ? (string) $body['message'] : null,
                'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
            ];
        } catch (ConnectionException $e) {
            $this->markOutcome(false, 0);
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => 'Network error: '.$e->getMessage(),
                'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
            ];
        }
    }

    public function delete(string $path): array
    {
        if ($this->breaker && !$this->breaker->allow()) {
            return $this->quickFailResponse();
        }

        try {
            $resp   = $this->client()->delete($path);
            $status = $resp->status();
            $body   = $resp->json() ?? [];
            $ok     = $resp->successful();

            $this->markOutcome($ok, $status);

            return [
                'ok' => $ok,
                'status' => $status,
                'body' => $body['data'] ?? $body,
                'message' => is_array($body) && array_key_exists('message', $body) ? (string) $body['message'] : null,
                'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
            ];
        } catch (ConnectionException $e) {
            $this->markOutcome(false, 0);
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => 'Network error: '.$e->getMessage(),
                'circuit' => ['state' => $this->breaker?->state() ?? 'closed'],
            ];
        }
    }
}
