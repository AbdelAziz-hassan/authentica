<?php

namespace AbdelAzizHassan\Authentica\DTO\Nafath;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NafathVerifyRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $v = Validator::make($this->data, [
            'otp'        => ['required', 'string', 'regex:/^\d{4,8}$/'],
            'request_id' => ['required', 'string', 'min:10'],
        ]);

        if ($v->fails()) throw new ValidationException($v);

        return $v->validated();
    }
}
