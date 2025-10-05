<?php

namespace AbdelAzizHassan\Authentica\DTO\Voice;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreVoiceRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $rules = [
            'audio' => ['required','string','regex:/^(data:audio\/[a-zA-Z0-9.+-]+;base64,)?[A-Za-z0-9+\/=\r\n]+$/'],
        ];

        $v = Validator::make($this->data, $rules, [
            'audio.regex' => 'audio must be a base64-encoded audio file.',
        ]);

        if ($v->fails()) throw new ValidationException($v);

        return $v->validated();
    }
}
