<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Infrastructure\RateLimit\RedisSlidingWindowRateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class ProductsRateLimit
{
    public function __construct(
        private readonly RedisSlidingWindowRateLimiter $rateLimiter,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('rate_limit.products.enabled', true)) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        $result = $this->rateLimiter->attempt(
            $this->key($request),
            (int) config('rate_limit.products.max_requests', 60),
            (int) config('rate_limit.products.window_seconds', 60),
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

    private function key(Request $request): string
    {
        return sprintf(
            '%s:%s',
            (string) config('rate_limit.products.prefix', 'rate-limit:products'),
            sha1(sprintf(
                '%s|%s',
                $request->route()->uri(),
                $request->ip() ?? 'unknown',
            )),
        );
    }
}
