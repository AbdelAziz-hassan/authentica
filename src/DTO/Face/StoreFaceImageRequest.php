<?php

namespace AbdelAzizHassan\Authentica\DTO\Face;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreFaceImageRequest
{
    public function __construct(private array $data) {}

    public function validate(): array
    {
        $rules = [
            'face_image' => ['required','string','regex:/^(data:image\/[a-zA-Z0-9.+-]+;base64,)?[A-Za-z0-9+\/=\r\n]+$/'],
        ];

        $v = Validator::make($this->data, $rules, [
            'face_image.regex' => 'face_image must be a base64-encoded image.',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        return $v->validated();
    }
}
