<?php

namespace AbdelAzizHassan\Authentica\Contracts;

interface AuthenticaClient
{
    /**
     * Calls GET /balance
     *
     * @return array The decoded JSON response from Authentica.
     */
    public function balance(): array;

    /** @param array{method?:string,phone?:string,email?:string,template_id?:int,fallback_phone?:string,fallback_email?:string,otp?:string} $payload */
    public function sendOtp(array $payload): array;

    /** @param array{phone?:string,email?:string,otp:string} $payload */
    public function verifyOtp(array $payload): array;

    /** @param array{national_id:string} $payload */
    public function verifyByNafath(array $payload): array;

    /** @param array{national_id:string,phone_number:string} $payload */
    public function nafathRequest(array $payload): array;

    /** @param array{otp:string,request_id:string} $payload */
    public function nafathVerify(array $payload): array;

    /** @param array{user_id:string,registered_face_image?:string,query_face_image:string} $payload */
    public function verifyByFace(array $payload): array;

    /** @param array{face_image:string} $payload */
    public function storeFaceImage(string $userId, array $payload): array;

    public function getFaceImage(string $userId): array;

    public function deleteFaceImage(string $userId): array;

     /** @param array{user_id:string,registered_audio?:string,query_audio:string} $payload */
    public function verifyByVoice(array $payload): array;

    /** @param array{audio:string} $payload */
    public function storeVoice(string $userId, array $payload): array;

    public function getVoice(string $userId): array;

    public function deleteVoice(string $userId): array;
}
