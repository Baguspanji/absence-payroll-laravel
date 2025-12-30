<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogIclockRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (str_starts_with($request->path(), 'iclock') && env('LOG_ICLOCK_REQUESTS', true)) {
            $logData = [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toDateTimeString(),
            ];

            // Add request data based on method
            if ($request->isMethod('get')) {
                $logData['query'] = $request->query();
            } elseif ($request->isMethod('post')) {
                // Log content for POST requests (like cdata)
                $logData['query'] = $request->query();
                $logData['content'] = $request->getContent();
            }

            $logData['respose'] = $next($request)->getContent();

            // Create a descriptive message
            $message = "Fingerprint device request: {$request->method()} {$request->path()}";

            // Log with info level
            Log::channel('iclock')->info($message, $logData);
        }

        return $next($request);
    }
}
