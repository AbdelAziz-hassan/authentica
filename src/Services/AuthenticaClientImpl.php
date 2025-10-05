<?php

namespace AbdelAzizHassan\Authentica\Services;

use AbdelAzizHassan\Authentica\Contracts\AuthenticaClient;
use AbdelAzizHassan\Authentica\DTO\Face\StoreFaceImageRequest;
use AbdelAzizHassan\Authentica\DTO\Face\VerifyByFaceRequest;
use AbdelAzizHassan\Authentica\DTO\Nafath\NafathDataRequest;
use AbdelAzizHassan\Authentica\DTO\Nafath\NafathVerifyRequest;
use AbdelAzizHassan\Authentica\DTO\Nafath\VerifyByNafathRequest;
use AbdelAzizHassan\Authentica\DTO\Otp\SendOtpRequest;
use AbdelAzizHassan\Authentica\DTO\Otp\VerifyOtpRequest;
use AbdelAzizHassan\Authentica\DTO\Voice\StoreVoiceRequest;
use AbdelAzizHassan\Authentica\DTO\Voice\VerifyByVoiceRequest;
use AbdelAzizHassan\Authentica\Http\AuthenticaHttp;
use AbdelAzizHassan\Authentica\Support\CircuitBreaker;

class AuthenticaClientImpl implements AuthenticaClient
{
    protected AuthenticaHttp $http;

    public function __construct(array $config)
    {
        $host = $config['host'] ?? 'https://api.authentica.sa/api/v2';
        $api  = $config['api_key'] ?? '';
        $timeout = (int)($config['timeout'] ?? 10);
        $connect = (int)($config['connect_timeout'] ?? 5);
        $breaker = app(CircuitBreaker::class);                 // no-op if disabled
        $circuit = (array) config('authentica.circuit', []);

        $this->http = new AuthenticaHttp(
            $host,
            $api,
            $timeout,
            $connect,
            $breaker,
            $circuit
        );
    }

    public function balance(): array
    {
        // GET /balance
        return $this->http->get('/balance');
    }

    public function sendOtp(array $payload): array
    {
        $data = (new SendOtpRequest($payload))->validate();
        return $this->http->post('/send-otp', $data);
    }

    public function verifyOtp(array $payload): array
    {
        $data = (new VerifyOtpRequest($payload))->validate();
        return $this->http->post('/verify-otp', $data);
    }

    public function verifyByNafath(array $payload): array
    {
        $data = (new VerifyByNafathRequest($payload))->validate();
        return $this->http->post('/verify-by-nafath', $data);
    }

    public function nafathRequest(array $payload): array
    {
        $data = (new NafathDataRequest($payload))->validate();
        return $this->http->post('/nafath/request', $data);
    }

    public function nafathVerify(array $payload): array
    {
        $data = (new NafathVerifyRequest($payload))->validate();
        return $this->http->post('/nafath/verify', $data);
    }

    public function verifyByFace(array $payload): array
    {
        $data = (new VerifyByFaceRequest($payload))->validate();
        return $this->http->post('/verify-by-face', $data);
    }

    public function storeFaceImage(string $userId, array $payload): array
    {
        $data = (new StoreFaceImageRequest($payload))->validate();
        return $this->http->post("/users/{$userId}/image", $data);
    }

    public function getFaceImage(string $userId): array
    {
        return $this->http->get("/users/{$userId}/image");
    }

    public function deleteFaceImage(string $userId): array
    {
        return $this->http->delete("/users/{$userId}/image");
    }

    public function verifyByVoice(array $payload): array
    {
        $data = (new VerifyByVoiceRequest($payload))->validate();
        return $this->http->post('/verify-by-voice', $data);
    }

    public function storeVoice(string $userId, array $payload): array
    {
        $data = (new StoreVoiceRequest($payload))->validate();
        return $this->http->post("/users/{$userId}/voice", $data);
    }

    public function getVoice(string $userId): array
    {
        return $this->http->get("/users/{$userId}/voice");
    }

    public function deleteVoice(string $userId): array
    {
        return $this->http->delete("/users/{$userId}/voice");
    }
}
