<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\EncryptionService;
use Symfony\Component\HttpFoundation\Response;

class PayloadSecurityMiddleware
{
    protected EncryptionService $encryption;

    public function __construct(EncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check for the encryption header
        if (!$request->header('X-Encrypted')) {
            // FALLBACK: If not encrypted, proceed normally (allows incremental adoption)
            return $next($request);
        }

        // 2. Decrypt the body
        $rawPayload = $request->getContent();
        
        if (empty($rawPayload)) {
            return $next($request);
        }

        $decryptedBody = $this->encryption->decrypt($rawPayload);

        if ($decryptedBody === null) {
            return response()->json([
                'message' => 'Security Error: Decryption failed.'
            ], 400);
        }

        // 3. Replace the request input with decrypted data
        $decodedData = json_decode($decryptedBody, true);
        
        if (is_string($decodedData)) {
            $decodedData = json_decode($decodedData, true);
        }
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
            $request->replace($decodedData);
        } else {
            return response()->json([
                'message' => 'Security Error: Invalid JSON after decryption.'
            ], 400);
        }

        return $next($request);
    }
}
