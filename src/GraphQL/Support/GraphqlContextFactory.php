<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use App\Application;
use App\Model\Entity\Membership;
use App\Model\Entity\PersonalAccessToken;
use App\Model\Entity\User;
use App\Model\Enum\TokenScope;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Authentication\Identity as AuthenticationIdentity;
use Authorization\AuthorizationService;
use Authorization\Identity;
use Authorization\IdentityInterface;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\ServerRequestInterface;

final class GraphqlContextFactory
{
    use LocatorAwareTrait;

    public function fromRequest(ServerRequestInterface $request): GraphqlContext
    {
        $tenant = TenantContext::instance();
        $workspaceId = $tenant->requireWorkspaceId();

        $token = $this->resolveBearer($request);
        if ($token !== null && $token->workspace_id !== $workspaceId) {
            throw new UserError('Invalid or expired token');
        }

        $scopes = $token === null ? [] : $token->scopeList;
        $role = $token === null ? null : $this->roleFor($token->user->id, $workspaceId);
        $viewer = $token === null ? null : $this->identityFor($token->user);

        return new GraphqlContext(
            workspaceId: $workspaceId,
            workspaceSlug: (string) $tenant->workspaceSlug,
            role: $role,
            loaders: new BatchLoader(GraphqlContext::grantsAtLeast($scopes, TokenScope::Preview)),
            viewer: $viewer,
            scopes: $scopes,
            debug: Configure::read('debug') === true,
        );
    }

    private function resolveBearer(ServerRequestInterface $request): ?PersonalAccessToken
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $plain = trim(substr($header, 7));
        if ($plain === '') {
            return null;
        }

        $token = $this->fetchTable('PersonalAccessTokens')->resolve($plain);
        if ($token === null) {
            throw new UserError('Invalid or expired token');
        }

        return $token;
    }

    private function roleFor(int $userId, int $workspaceId): ?UserRole
    {
        $membership = $this->fetchTable('Memberships')->find()
            ->where(['Memberships.user_id' => $userId, 'Memberships.workspace_id' => $workspaceId])
            ->first();

        return $membership instanceof Membership ? $membership->roleEnum : null;
    }

    private function identityFor(User $user): IdentityInterface
    {
        return new Identity(new AuthorizationService(Application::policyResolver()), new AuthenticationIdentity($user));
    }
}
