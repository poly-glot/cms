<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\Entity\Membership;
use App\Model\Entity\Workspace;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Authentication\IdentityInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TenantResolutionMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = (array) $request->getAttribute('params', []);

        $slug = $params['workspaceSlug'] ?? null;
        if (!is_string($slug) || $slug === '') {
            return $handler->handle($request);
        }

        $workspace = $this->fetchTable('Workspaces')->find('bySlug', slug: $slug)->first();
        if (!$workspace instanceof Workspace) {
            throw new NotFoundException(sprintf('Workspace "%s" does not exist.', $slug));
        }

        $isAdminZone = ($params['prefix'] ?? null) === 'Admin';
        $role = $isAdminZone ? $this->resolveRole($request, $workspace->id) : null;

        return TenantContext::instance()->runScoped(
            $workspace->id,
            $role,
            static fn (): ResponseInterface => $handler->handle($request),
            workspaceSlug: $workspace->slug,
        );
    }

    private function resolveRole(ServerRequestInterface $request, int $workspaceId): ?UserRole
    {
        $identity = $request->getAttribute('identity');
        if (!$identity instanceof IdentityInterface) {
            return null;
        }

        $userId = $identity->getIdentifier();
        if (!is_int($userId)) {
            return null;
        }

        $membership = $this->fetchTable('Memberships')
            ->find()
            ->where(['Memberships.user_id' => $userId, 'Memberships.workspace_id' => $workspaceId])
            ->first();

        if (!$membership instanceof Membership) {
            throw new ForbiddenException('You are not a member of this workspace.');
        }

        return $membership->roleEnum;
    }
}
