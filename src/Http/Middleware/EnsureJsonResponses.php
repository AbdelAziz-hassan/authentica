<?php

namespace AbdelAzizHassan\Authentica\Http\Middleware;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureJsonResponses
{
    public function handle(Request $request, Closure $next)
    {
        // Force clients to be treated as JSON callers
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $next($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            $code = $this->httpStatusFromThrowable($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Internal server error',
            ], $code);
        }

        // If the downstream already returned JSON, just pass it through
        if ($response instanceof JsonResponse) {
            return $response;
        }

        // If a controller returned a Responsable that becomes JSON, let it through
        if ($response instanceof Responsable) {
            $sym = $response->toResponse($request);
            if ($sym instanceof JsonResponse) {
                return $sym;
            }
            return response()->json([
                'success' => true,
                'data' => $sym->getContent(),
            ], $sym->getStatusCode());
        }

        // Convert any other Symfony Response to JSON
        if ($response instanceof Response) {
            $content = $response->getContent();
            $decoded = json_decode($content, true);
            return response()->json(
                is_array($decoded) ? $decoded : ['success' => true, 'data' => $content],
                $response->getStatusCode()
            );
        }

        // Fallback: wrap scalars/arrays
        return response()->json(['success' => true, 'data' => $response], 200);
    }

    protected function httpStatusFromThrowable(Throwable $e): int
    {
        $code = (int) $e->getCode();
        return ($code >= 100 && $code < 600) ? $code : 500;
    }
}
