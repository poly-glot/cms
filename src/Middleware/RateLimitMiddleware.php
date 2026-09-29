<?php

declare(strict_types=1);

namespace App\Middleware;

use Authentication\IdentityInterface;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private const string CACHE_CONFIG = 'ratelimit';

    private const float DEFAULT_RATE = 1.0;

    private const int DEFAULT_BURST = 10;

    private const float PUBLIC_RATE = 500.0;

    private const int PUBLIC_BURST = 500;

    /** @var array<int, array{0: string, 1: string}> */
    private const array GENEROUS = [
        ['Pages', 'view'],
        ['Graphql', 'execute'],
        ['Media', 'serve'],
        ['Media', 'library'],
        ['Pages', 'autosave'],
        ['Pages', 'attachTag'],
        ['Pages', 'detachTag'],
        ['Pages', 'reorder'],
        ['Posts', 'attachTag'],
        ['Posts', 'detachTag'],
        ['Tags', 'index'],
        ['Blocks', 'list'],
        ['Collections', 'list'],
        ['CollectionEntries', 'search'],
        ['CollectionEntries', 'resolve'],
        ['Comments', 'approve'],
        ['Comments', 'archive'],
        ['Comments', 'spam'],
        ['Comments', 'delete'],
        ['Comments', 'reply'],
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (Configure::read('RateLimit.enabled', true) !== true) {
            return $handler->handle($request);
        }

        $generous = $this->isGenerous($request);
        $rate = $generous ? self::PUBLIC_RATE : self::DEFAULT_RATE;
        $burst = (float) ($generous ? self::PUBLIC_BURST : self::DEFAULT_BURST);
        $cacheKey = 'rl_' . ($generous ? 'pub' : 'def') . '_' . md5($this->clientKey($request));

        $now = microtime(true);
        $bucket = Cache::read($cacheKey, self::CACHE_CONFIG);
        $tokens = $burst;
        $last = $now;
        if (is_array($bucket)) {
            $tokens = is_numeric($bucket['tokens'] ?? null) ? (float) $bucket['tokens'] : $burst;
            $last = is_numeric($bucket['ts'] ?? null) ? (float) $bucket['ts'] : $now;
        }

        $tokens = min($burst, $tokens + ($now - $last) * $rate);

        if ($tokens < 1.0) {
            return $this->tooManyRequests((int) max(1.0, ceil((1.0 - $tokens) / $rate)));
        }

        Cache::write($cacheKey, ['tokens' => $tokens - 1.0, 'ts' => $now], self::CACHE_CONFIG);

        return $handler->handle($request);
    }

    private function isGenerous(ServerRequestInterface $request): bool
    {
        $params = $request->getAttribute('params');
        if (!is_array($params)) {
            return false;
        }

        $signature = [$params['controller'] ?? null, $params['action'] ?? null];

        return in_array($signature, self::GENEROUS, true);
    }

    private function clientKey(ServerRequestInterface $request): string
    {
        $identity = $request->getAttribute('identity');
        if ($identity instanceof IdentityInterface) {
            $id = $identity->getIdentifier();
            if (is_int($id) || is_string($id)) {
                return 'user:' . $id;
            }
        }

        if ($request instanceof ServerRequest) {
            return 'ip:' . $request->clientIp();
        }

        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        return 'ip:' . (is_string($remote) ? $remote : 'unknown');
    }

    private function tooManyRequests(int $retryAfter): Response
    {
        return new Response()
            ->withStatus(429)
            ->withType('text/plain')
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withStringBody(sprintf('Rate limit exceeded. Please retry in %d second(s).', $retryAfter));
    }
}
