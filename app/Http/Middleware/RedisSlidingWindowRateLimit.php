<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Infrastructure\RateLimit\RedisSlidingWindowRateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class RedisSlidingWindowRateLimit
{
    public function __construct(
        private readonly RedisSlidingWindowRateLimiter $rateLimiter,
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $prefix,
        string $maxRequests,
        string $windowSeconds,
        ?string $enabledConfig = null,
    ): Response {
        if ($enabledConfig !== null && ! (bool) config($enabledConfig, true)) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        $result = $this->rateLimiter->attempt(
            $this->key($request, $prefix),
            (int) $maxRequests,
            (int) $windowSeconds,
        );

        if (! $result->allowed) {
            return response()->json([
                'message' => 'Too many requests.',
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => (string) $result->retryAfterSeconds,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-RateLimit-Remaining', (string) $result->remaining);

        return $response;
    }

    private function key(Request $request, string $prefix): string
    {
        return sprintf(
            'rate-limit:%s:%s',
            $prefix,
            sha1(sprintf(
                '%s|%s',
                $request->route()->uri(),
                $request->ip() ?? 'unknown',
            )),
        );
    }
}
