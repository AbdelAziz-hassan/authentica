<?php

namespace AbdelAzizHassan\Authentica\Support;

class HttpResponse
{
    /** @param array{ok:bool,status:int,body:array|null,message:?string} $res */
    public static function toLaravel(array $res, int $networkStatus = 503)
    {
        $status = $res['status'] === 0 ? $networkStatus : $res['status'];
        $body   = $res['body'] ?? ['success' => $res['ok'], 'message' => $res['message']];
        return response()->json($body, $status);
    }
}
