<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\PersonalAccessToken;
use App\Model\Enum\TokenScope;
use App\Model\Tenancy\TenantContext;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

final class TokensController extends AdminController
{
    use AdminOnlyTrait;

    public function index(): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $plaintext = $this->request->getSession()->consume('Tokens.plaintext');

        $this->set([
            'tokens' => $this->fetchTable('PersonalAccessTokens')->find()
                ->where(['PersonalAccessTokens.workspace_id' => $workspaceId])
                ->contain('Users')
                ->orderByDesc('PersonalAccessTokens.created')
                ->all(),
            'scopes' => TokenScope::cases(),
            'plaintext' => is_string($plaintext) ? $plaintext : null,
        ]);
    }

    public function create(): ?Response
    {
        $this->request->allowMethod('post');
        $this->Authorization->authorize($this->fetchTable('PersonalAccessTokens')->newEmptyEntity(), 'create');

        $name = $this->stringInput('name');
        if ($name === '') {
            $this->Flash->error('Give the token a name.');

            return $this->redirect(['action' => 'index']);
        }

        $userId = $this->Authentication->getIdentity()?->getIdentifier();
        if (!is_int($userId)) {
            throw new NotFoundException();
        }

        $result = $this->fetchTable('PersonalAccessTokens')->issue(
            TenantContext::instance()->requireWorkspaceId(),
            $userId,
            $name,
            $this->requestedScopes(),
            $this->requestedExpiry(),
        );

        $this->request->getSession()->write('Tokens.plaintext', $result['token']);
        $this->Flash->success('Token created. Copy it now — it will not be shown again.');

        return $this->redirect(['action' => 'index']);
    }

    public function temporary(): Response
    {
        $this->request->allowMethod('post');
        $this->Authorization->authorize($this->fetchTable('PersonalAccessTokens')->newEmptyEntity(), 'create');

        $userId = $this->Authentication->getIdentity()?->getIdentifier();
        if (!is_int($userId)) {
            throw new NotFoundException();
        }

        $result = $this->fetchTable('PersonalAccessTokens')->issue(
            TenantContext::instance()->requireWorkspaceId(),
            $userId,
            'Playground (temporary)',
            [TokenScope::Read, TokenScope::Preview, TokenScope::Write],
            DateTime::now()->addHours(1),
        );

        return $this->json([
            'token' => $result['token'],
            'expiresAt' => $result['entity']->expires_at?->format(\DATE_ATOM),
        ]);
    }

    public function revoke(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $tokens = $this->fetchTable('PersonalAccessTokens');

        $token = $tokens->find()
            ->where(['PersonalAccessTokens.id' => $id, 'PersonalAccessTokens.workspace_id' => $workspaceId])
            ->first();
        if (!$token instanceof PersonalAccessToken) {
            throw new NotFoundException();
        }
        $this->Authorization->authorize($token, 'revoke');

        $tokens->delete($token)
            ? $this->Flash->success(sprintf('Revoked "%s".', $token->name))
            : $this->Flash->error('Could not revoke the token.');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return list<TokenScope>
     */
    private function requestedScopes(): array
    {
        $requested = $this->request->getData('scopes');
        $requested = is_array($requested) ? $requested : [];

        $scopes = [];
        foreach ($requested as $value) {
            $scope = is_string($value) ? TokenScope::tryFrom($value) : null;
            if ($scope !== null) {
                $scopes[$scope->value] = $scope;
            }
        }

        return $scopes === [] ? [TokenScope::Read] : array_values($scopes);
    }

    private function requestedExpiry(): ?DateTime
    {
        $raw = $this->request->getData('expires_at');

        return is_string($raw) && $raw !== '' ? new DateTime($raw) : null;
    }
}
