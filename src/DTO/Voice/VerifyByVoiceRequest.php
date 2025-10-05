<?php

namespace AbdelAzizHassan\Authentica\DTO\Voice;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VerifyByVoiceRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $rules = [
            'user_id'          => ['required','string','min:1'],
            // base64 audio; allow common Base64 chars and optional data URI prefix
            'registered_audio' => ['nullable','string','regex:/^(data:audio\/[a-zA-Z0-9.+-]+;base64,)?[A-Za-z0-9+\/=\r\n]+$/'],
            'query_audio'      => ['required','string','regex:/^(data:audio\/[a-zA-Z0-9.+-]+;base64,)?[A-Za-z0-9+\/=\r\n]+$/'],
        ];

        $v = Validator::make($this->data, $rules, [
            'registered_audio.regex' => 'registered_audio must be a base64-encoded audio file.',
            'query_audio.regex'      => 'query_audio must be a base64-encoded audio file.',
        ]);

        if ($v->fails()) throw new ValidationException($v);

        return $v->validated();
    }
}
