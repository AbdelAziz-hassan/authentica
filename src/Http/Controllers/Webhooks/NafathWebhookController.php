<?php

namespace AbdelAzizHassan\Authentica\Http\Controllers\Webhooks;

use AbdelAzizHassan\Authentica\Webhooks\Events\NafathWebhookReceived;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use AbdelAzizHassan\Authentica\Webhooks\NafathAuth;

class NafathWebhookController extends Controller
{
    public function __invoke(Request $request, NafathAuth $auth)
    {
        // Always JSON
        $request->headers->set('Accept', 'application/json');

        // Auth (body_password | basic | jwt-delegated)
        $check = $auth->check($request);
        if ($check['ok'] === false) {
            return response()->json(['success' => false, 'message' => $check['message']], $check['status']);
        }
        if (!empty($check['needs_client_jwt_validation'])) {
            return response()->json(['success' => false, 'message' => 'JWT validation not handled by package'], 501);
        }

        // Validate payload
        try {
            $data = $request->validate([
                'TransactionId' => ['required','string'],
                'NationalId'    => ['required','string'],
                'Status'        => ['required','string','in:COMPLETED,REJECTED'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        // 3) Dispatch the single event
        event(new NafathWebhookReceived(
            $data['TransactionId'],
            $data['NationalId'],
            $data['Status'],
            $request->all()
        ));
        // TODO (next steps): idempotency + optional persistence + queue/callback
        return response()->json(['success' => true, 'message' => 'Webhook received'], 200);
    }
}
