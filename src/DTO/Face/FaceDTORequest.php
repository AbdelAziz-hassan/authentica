<?php

namespace AbdelAzizHassan\Authentica\DTO\Face;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VerifyByFaceRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $rules = [
            'user_id'               => ['required','string','min:1'],
            // base64 images (allow common chars, +,/ , =, and optional data URI prefix)
            'registered_face_image' => ['nullable','string','regex:/^(data:image\/[a-zA-Z0-9.+-]+;base64,)?[A-Za-z0-9+\/=\r\n]+$/'],
            'query_face_image'      => ['required','string','regex:/^(data:image\/[a-zA-Z0-9.+-]+;base64,)?[A-Za-z0-9+\/=\r\n]+$/'],
        ];

        $v = Validator::make($this->data, $rules, [
            'registered_face_image.regex' => 'registered_face_image must be a base64-encoded image.',
            'query_face_image.regex'      => 'query_face_image must be a base64-encoded image.',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        return $v->validated();
    }
}
