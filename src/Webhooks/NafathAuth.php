<?php

namespace AbdelAzizHassan\Authentica\Webhooks;

use Illuminate\Http\Request;

class NafathAuth
{
    /**
     * Returns a non-throwing shape:
     *  - body_password/basic => { ok: bool, status: int, message?: string }
     *  - jwt => { ok: null, status: 0, message: 'delegate:jwt', needs_client_jwt_validation: true }
     */
    public function check(Request $request): array
    {
        $cfg  = (array) config('authentica.webhooks.nafath');
        $mode = (string) ($cfg['auth'] ?? 'body_password');

        return match ($mode) {
            'basic' => $this->checkBasic($request, (array) ($cfg['basic'] ?? [])),
            'jwt'   => [
                'ok' => null,
                'status' => 0,
                'message' => 'delegate:jwt',
                'needs_client_jwt_validation' => true,
            ],
            default => $this->checkBodyPassword($request, (array) ($cfg['body_password'] ?? [])),
        };
    }

    protected function checkBodyPassword(Request $request, array $cfg): array
    {
        $label    = (string) ($cfg['password_label'] ?? 'Password');
        $expected = (string) ($cfg['password_value'] ?? '');

        if ($expected === '') {
            return ['ok' => false, 'status' => 500, 'message' => 'Server password not configured'];
        }

        $incoming = (string) ($request->input($label) ?? '');
        if ($incoming === '') {
            return ['ok' => false, 'status' => 403, 'message' => "Missing {$label}"];
        }

        $ok = hash_equals($expected, $incoming);
        return ['ok' => $ok, 'status' => $ok ? 200 : 403, 'message' => $ok ? null : 'Invalid password'];
    }

   protected function checkBasic(Request $request, array $cfg): array
    {
        $uLabel = (string)($cfg['username_label'] ?? 'username');
        $pLabel = (string)($cfg['password_label'] ?? 'password');
        $uVal   = (string)($cfg['username_value'] ?? '');
        $pVal   = (string)($cfg['password_value'] ?? '');

        if ($uVal === '' || $pVal === '') {
            return ['ok' => false, 'status' => 500, 'message' => 'Basic credentials not configured'];
        }

        // 1) Prefer the standard Authorization: Basic header
        $auth = (string)$request->header('Authorization', '');
        if (str_starts_with($auth, 'Basic ')) {
            $decoded = base64_decode(substr($auth, 6), true);
            if ($decoded === false || !str_contains($decoded, ':')) {
                return ['ok' => false, 'status' => 403, 'message' => 'Malformed Basic credentials'];
            }
            [$user, $pass] = explode(':', $decoded, 2);
            $ok = hash_equals($uVal, (string)$user) && hash_equals($pVal, (string)$pass);
            return ['ok' => $ok, 'status' => $ok ? 200 : 403, 'message' => $ok ? null : 'Invalid Basic credentials'];
        }

        // 2) Fallback: accept credentials via request body using configured labels
        $user = $request->input($uLabel);
        $pass = $request->input($pLabel);

        if ($user === null || $pass === null) {
            return [
                'ok' => false,
                'status' => 403,
                'message' => "Missing credentials: provide Basic Authorization header or '{$uLabel}' and '{$pLabel}' fields",
            ];
        }

        $ok = hash_equals($uVal, (string)$user) && hash_equals($pVal, (string)$pass);
        return ['ok' => $ok, 'status' => $ok ? 200 : 403, 'message' => $ok ? null : 'Invalid Basic credentials'];
    }

}
