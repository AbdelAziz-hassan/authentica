<?php

namespace AbdelAzizHassan\Authentica\DTO\Nafath;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VerifyByNafathRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $v = Validator::make($this->data, [
            'national_id' => ['required', 'string', 'regex:/^\d{10}$/'],
        ], [
            'national_id.regex' => 'national_id must be a 10-digit number.',
        ]);

        if ($v->fails()) throw new ValidationException($v);

        return $v->validated();
    }
}
