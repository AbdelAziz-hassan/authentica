<?php

namespace AbdelAzizHassan\Authentica\DTO\Nafath;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NafathDataRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $v = Validator::make($this->data, [
            'national_id'  => ['required', 'string', 'regex:/^\d{10}$/'],
            'phone_number' => ['required', 'string', 'regex:/^\d{9,15}$/'],
        ], [
            'national_id.regex'  => 'national_id must be a 10-digit number.',
            'phone_number.regex' => 'phone_number must be an international number (9–15 digits, no +).',
        ]);

        if ($v->fails()) throw new ValidationException($v);

        return $v->validated();
    }
}
