<?php

declare(strict_types=1);

namespace App;

use App\Command\PublishScheduledCommand;
use App\Middleware\HostHeaderMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\TenantResolutionMiddleware;
use App\Model\Entity\Block;
use App\Model\Entity\Collection;
use App\Model\Entity\CollectionEntry;
use App\Model\Entity\Comment;
use App\Model\Entity\ContentTypeFieldSchema;
use App\Model\Entity\Menu;
use App\Model\Entity\Post;
use App\Policy\AuthoredContentPolicy;
use App\Policy\EditorialPolicy;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use Authorization\Policy\ResolverInterface;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @extends BaseApplication<Application>
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{
    #[Override]
    public function bootstrap(): void
    {
        parent::bootstrap();
        FactoryLocator::add('Table', new TableLocator()->allowFallbackClass(false));
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new SecurityHeadersMiddleware())
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new HostHeaderMiddleware())
            ->add(new AssetMiddleware(['cacheTime' => Configure::read('Asset.cacheTime')]))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new CsrfProtectionMiddleware(['httponly' => true])
                ->skipCheckCallback(static fn (ServerRequestInterface $request): bool => str_ends_with($request->getUri()->getPath(), '/graphql')))
            ->add(new AuthenticationMiddleware($this))
            ->add(new RateLimitMiddleware())
            ->add(new TenantResolutionMiddleware())
            ->add(new AuthorizationMiddleware($this, ['requireAuthorizationCheck' => false]));

        return $middlewareQueue;
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService([
            'unauthenticatedRedirect' => '/login',
            'queryParam' => 'redirect',
        ]);

        $fields = [
            'username' => 'email',
            'password' => 'password',
        ];

        $identifierConfig = [
            'identifier' => [
                'Authentication.Password' => [
                    'fields' => $fields,
                    'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'Users'],
                ],
            ],
        ];

        $service->loadAuthenticator('Authentication.Session');
        $service->loadAuthenticator('Authentication.Form', $identifierConfig + [
            'fields' => $fields,
            'loginUrl' => '/login',
        ]);
        $service->loadAuthenticator('Authentication.Cookie', $identifierConfig + [
            'fields' => $fields,
            'rememberMeField' => 'remember_me',
            'cookie' => ['name' => 'CabinetRemember', 'expires' => '+30 days'],
        ]);

        return $service;
    }

    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        return new AuthorizationService(self::policyResolver());
    }

    public static function policyResolver(): ResolverInterface
    {
        $map = new MapResolver([
            Post::class => AuthoredContentPolicy::class,
            CollectionEntry::class => AuthoredContentPolicy::class,
            Block::class => EditorialPolicy::class,
            Collection::class => EditorialPolicy::class,
            Comment::class => EditorialPolicy::class,
            ContentTypeFieldSchema::class => EditorialPolicy::class,
            Menu::class => EditorialPolicy::class,
        ]);

        return new ResolverCollection([$map, new OrmResolver()]);
    }

    #[Override]
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);
        $commands->add('pages publish-scheduled', PublishScheduledCommand::class);

        return $commands;
    }
}
