<?php

declare(strict_types=1);

namespace App\Controller;

use App\GraphQL\Registry;
use App\GraphQL\Support\EntityErrorMapper;
use App\GraphQL\Support\GraphqlContext;
use App\GraphQL\Support\GraphqlContextFactory;
use App\GraphQL\Support\UserError;
use App\Model\Tenancy\TenantContext;
use Authentication\Controller\Component\AuthenticationComponent;
use Cake\Http\Response;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use Override;

/**
 * @property AuthenticationComponent $Authentication
 */
final class GraphqlController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['execute']);
    }

    public function execute(): Response
    {
        try {
            $context = new GraphqlContextFactory()->fromRequest($this->request);
        } catch (UserError $error) {
            return $this->jsonResponse(['errors' => [['message' => $error->getMessage()]]]);
        }

        $operation = new Helper()->parseRequestParams(
            $this->request->getMethod(),
            (array) $this->request->getParsedBody(),
            $this->request->getQueryParams(),
        );

        $result = TenantContext::instance()->runScoped(
            $context->workspaceId,
            $context->role,
            fn (): mixed => $this->server($context)->executeRequest($operation),
            workspaceSlug: $context->workspaceSlug,
        );

        return $this->jsonResponse($result);
    }

    private function server(GraphqlContext $context): StandardServer
    {
        return new StandardServer(ServerConfig::create()
            ->setSchema(Registry::schema())
            ->setContext($context)
            ->setValidationRules($context->validationRules())
            ->setErrorsHandler(EntityErrorMapper::handle(...))
            ->setFieldResolver(Registry::resolveField(...))
            ->setDebugFlag($context->debugFlags()));
    }

    private function jsonResponse(mixed $payload): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody((string) json_encode($payload));
    }
}
