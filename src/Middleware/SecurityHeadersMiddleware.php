<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sends a Content-Security-Policy plus the usual hardening headers on every
 * response. The policy is deliberately script-strict ('self' only, no inline
 * script) — the app has no inline event handlers or inline <script> blocks, so
 * an injected script in user content (a comment, a page body) cannot execute.
 * Inline style attributes are still used in the admin chrome, so style allows
 * 'unsafe-inline'; Google Fonts is the only third-party origin.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    private const string POLICY = "default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; "
        . "img-src 'self' data:; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'";

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('Content-Security-Policy', self::POLICY)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
