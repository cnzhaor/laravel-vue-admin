<?php

namespace App\Http\Middleware;

use App\Models\OperationLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperationLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $response = $next($request);

        if ($request->user() && !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $payload = $request->except(['password', 'password_confirmation']);
            OperationLog::query()->create([
                'user_id' => $request->user()->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'status' => $response->getStatusCode(),
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                'payload' => $payload ?: null,
            ]);
        }

        return $response;
    }
}

