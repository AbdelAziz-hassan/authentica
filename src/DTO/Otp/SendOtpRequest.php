<?php

namespace AbdelAzizHassan\Authentica\DTO\Otp;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SendOtpRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $rules = [
            'method'         => ['nullable', 'in:whatsapp,sms,email'],
            'phone'          => ['nullable', 'string'],
            'email'          => ['nullable', 'email'],
            'template_id'    => ['nullable', 'integer'],
            'fallback_phone' => ['nullable', 'string'],
            'fallback_email' => ['nullable', 'email'],
            'otp'            => ['nullable', 'regex:/^[0-9]+$/', 'min:4', 'max:12'],
        ];

        $v = Validator::make($this->data, $rules);

        $v->after(function ($v) {
            $method = $this->data['method'] ?? null;

            // If method provided, enforce channel-specific field
            if ($method === 'sms' || $method === 'whatsapp') {
                if (empty($this->data['phone'])) {
                    $v->errors()->add('phone', 'phone is required when method is sms or whatsapp.');
                }
            }

            if ($method === 'email') {
                if (empty($this->data['email'])) {
                    $v->errors()->add('email', 'email is required when method is email.');
                }
            }
        });

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        return $v->validated();
    }
}
