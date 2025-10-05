<?php

namespace AbdelAzizHassan\Authentica\DTO\Otp;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VerifyOtpRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $rules = [
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'otp'   => ['required', 'string'],
        ];

        $v = Validator::make($this->data, $rules);

        $v->after(function ($v) {
            if (empty($this->data['phone']) && empty($this->data['email'])) {
                $v->errors()->add('phone', 'Either phone or email is required based on the channel you used.');
            }
        });

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        return $v->validated();
    }
}
