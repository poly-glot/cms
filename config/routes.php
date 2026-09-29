<?php

declare(strict_types=1);

use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    Router::addUrlFilter(static function (array $params, ServerRequest $request): array {
        $slug = $request->getParam('workspaceSlug');
        if (is_string($slug) && $slug !== '' && !isset($params['workspaceSlug'])) {
            $params['workspaceSlug'] = $slug;
        }

        return $params;
    });

    $routes->scope('/', static function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Workspaces', 'action' => 'home']);
        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/signup', ['controller' => 'Signup', 'action' => 'index'])
            ->setMethods(['GET', 'POST']);
        $builder->connect('/users/set-password/{token}', ['controller' => 'Users', 'action' => 'setPassword'])
            ->setPatterns(['token' => '[a-f0-9]{64}'])->setPass(['token']);
        $builder->connect('/forgot-password', ['controller' => 'Users', 'action' => 'forgotPassword'])
            ->setMethods(['GET', 'POST']);
        $builder->connect('/reset-password/{token}', ['controller' => 'Users', 'action' => 'resetPassword'])
            ->setPatterns(['token' => '[a-f0-9]{64}'])->setPass(['token'])->setMethods(['GET', 'POST']);
        $builder->connect('/workspaces/new', ['controller' => 'Workspaces', 'action' => 'add'])
            ->setMethods(['GET', 'POST']);

        $builder->scope('/{workspaceSlug}', static function (RouteBuilder $ws): void {
            $ws->prefix('Admin', static function (RouteBuilder $admin): void {
                $admin->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);

                $admin->connect('/collections', ['controller' => 'Collections', 'action' => 'index']);
                $admin->connect('/collections/list', ['controller' => 'Collections', 'action' => 'list'])
                    ->setMethods(['GET']);
                $admin->connect('/collections/entry-search', ['controller' => 'CollectionEntries', 'action' => 'search'])
                    ->setMethods(['GET']);
                $admin->connect('/collections/entry-resolve', ['controller' => 'CollectionEntries', 'action' => 'resolve'])
                    ->setMethods(['GET']);
                $admin->connect('/collections/add', ['controller' => 'Collections', 'action' => 'add']);
                $admin->connect('/collections/edit/{id}', ['controller' => 'Collections', 'action' => 'edit'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id']);
                $admin->connect('/collections/delete/{id}', ['controller' => 'Collections', 'action' => 'delete'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/collections/{collectionId}/entries', ['controller' => 'CollectionEntries', 'action' => 'index'])
                    ->setPatterns(['collectionId' => '\d+'])->setPass(['collectionId']);
                $admin->connect('/collections/{collectionId}/entries/add', ['controller' => 'CollectionEntries', 'action' => 'add'])
                    ->setPatterns(['collectionId' => '\d+'])->setPass(['collectionId']);
                $admin->connect('/collections/{collectionId}/entries/edit/{id}', ['controller' => 'CollectionEntries', 'action' => 'edit'])
                    ->setPatterns(['collectionId' => '\d+', 'id' => '\d+'])->setPass(['collectionId', 'id']);
                $admin->connect('/collections/{collectionId}/entries/delete/{id}', ['controller' => 'CollectionEntries', 'action' => 'delete'])
                    ->setPatterns(['collectionId' => '\d+', 'id' => '\d+'])->setPass(['collectionId', 'id'])->setMethods(['POST']);

                $admin->connect('/posts', ['controller' => 'Posts', 'action' => 'index']);
                $admin->connect('/posts/add', ['controller' => 'Posts', 'action' => 'add']);
                $admin->connect('/posts/edit/{id}', ['controller' => 'Posts', 'action' => 'edit'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id']);
                $admin->connect('/posts/delete/{id}', ['controller' => 'Posts', 'action' => 'delete'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/posts/save-draft/{id}', ['controller' => 'Posts', 'action' => 'saveDraft'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/posts/publish/{id}', ['controller' => 'Posts', 'action' => 'publish'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/posts/schedule/{id}', ['controller' => 'Posts', 'action' => 'schedule'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/posts/{id}/tags/{slug}', ['controller' => 'Posts', 'action' => 'attachTag'])
                    ->setPatterns(['id' => '\d+', 'slug' => '[a-z0-9-]+'])
                    ->setPass(['id', 'slug'])->setMethods(['POST']);
                $admin->connect('/posts/{id}/tags/{slug}/detach', ['controller' => 'Posts', 'action' => 'detachTag'])
                    ->setPatterns(['id' => '\d+', 'slug' => '[a-z0-9-]+'])
                    ->setPass(['id', 'slug'])->setMethods(['POST']);

                $admin->connect('/content-model/{type}/fields', ['controller' => 'ContentFields', 'action' => 'edit'])
                    ->setPatterns(['type' => 'pages|posts'])->setPass(['type'])->setMethods(['GET', 'POST', 'PATCH']);

                $admin->connect('/pages', ['controller' => 'Pages', 'action' => 'index']);
                $admin->connect('/pages/reorder', ['controller' => 'Pages', 'action' => 'reorder'])
                    ->setMethods(['POST']);
                $admin->connect('/pages/bulk-status', ['controller' => 'Pages', 'action' => 'bulkStatus'])
                    ->setMethods(['POST']);
                $admin->connect('/pages/add', ['controller' => 'Pages', 'action' => 'add']);
                $admin->connect('/pages/edit/{id}', ['controller' => 'Pages', 'action' => 'edit'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id']);
                $admin->connect('/pages/delete/{id}', ['controller' => 'Pages', 'action' => 'delete'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])
                    ->setMethods(['POST']);

                $admin->connect('/pages/save-draft/{id}', ['controller' => 'Pages', 'action' => 'saveDraft'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/pages/publish/{id}', ['controller' => 'Pages', 'action' => 'publish'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/pages/schedule/{id}', ['controller' => 'Pages', 'action' => 'schedule'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/pages/autosave/{id}', ['controller' => 'Pages', 'action' => 'autosave'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['PATCH']);
                $admin->connect('/pages/restore/{id}/{version}', ['controller' => 'Pages', 'action' => 'restore'])
                    ->setPatterns(['id' => '\d+', 'version' => '\d+'])->setPass(['id', 'version'])->setMethods(['POST']);

                $admin->connect('/pages/{id}/tags/{slug}', ['controller' => 'Pages', 'action' => 'attachTag'])
                    ->setPatterns(['id' => '\d+', 'slug' => '[a-z0-9-]+'])
                    ->setPass(['id', 'slug'])->setMethods(['POST']);
                $admin->connect('/pages/{id}/tags/{slug}/detach', ['controller' => 'Pages', 'action' => 'detachTag'])
                    ->setPatterns(['id' => '\d+', 'slug' => '[a-z0-9-]+'])
                    ->setPass(['id', 'slug'])->setMethods(['POST']);
                $admin->connect('/tags', ['controller' => 'Tags', 'action' => 'index']);

                $admin->connect('/blocks', ['controller' => 'Blocks', 'action' => 'index']);
                $admin->connect('/blocks/list', ['controller' => 'Blocks', 'action' => 'list'])->setMethods(['GET']);
                $admin->connect('/blocks/add', ['controller' => 'Blocks', 'action' => 'add']);
                $admin->connect('/blocks/edit/{id}', ['controller' => 'Blocks', 'action' => 'edit'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id']);
                $admin->connect('/blocks/delete/{id}', ['controller' => 'Blocks', 'action' => 'delete'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])
                    ->setMethods(['POST']);

                $admin->connect('/account', ['controller' => 'Account', 'action' => 'index'])
                    ->setMethods(['GET', 'POST']);
                $admin->connect('/account/password', ['controller' => 'Account', 'action' => 'password'])
                    ->setMethods(['POST']);

                $admin->connect('/users', ['controller' => 'Users', 'action' => 'index']);
                $admin->connect('/users/invite', ['controller' => 'Users', 'action' => 'invite']);
                $admin->connect('/users/edit-role/{id}', ['controller' => 'Users', 'action' => 'editRole'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/users/send-reset/{id}', ['controller' => 'Users', 'action' => 'sendReset'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);

                $admin->connect('/tokens', ['controller' => 'Tokens', 'action' => 'index']);
                $admin->connect('/tokens/create', ['controller' => 'Tokens', 'action' => 'create'])
                    ->setMethods(['POST']);
                $admin->connect('/tokens/revoke/{id}', ['controller' => 'Tokens', 'action' => 'revoke'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                $admin->connect('/tokens/temporary', ['controller' => 'Tokens', 'action' => 'temporary'])
                    ->setMethods(['POST']);
                $admin->connect('/api-playground', ['controller' => 'ApiPlayground', 'action' => 'index']);

                $admin->connect('/appearance', ['controller' => 'Appearance', 'action' => 'index']);
                $admin->connect('/appearance/activate', ['controller' => 'Appearance', 'action' => 'activate'])
                    ->setMethods(['POST']);

                $admin->connect('/settings', ['controller' => 'Settings', 'action' => 'index']);
                $admin->connect('/settings/save', ['controller' => 'Settings', 'action' => 'save'])
                    ->setMethods(['POST']);

                $admin->connect('/navigation', ['controller' => 'Navigation', 'action' => 'index']);
                $admin->connect('/navigation/save', ['controller' => 'Navigation', 'action' => 'save'])
                    ->setMethods(['POST']);

                $admin->connect('/comments', ['controller' => 'Comments', 'action' => 'index']);
                $admin->connect('/comments/approve-selected', ['controller' => 'Comments', 'action' => 'approveSelected'])
                    ->setMethods(['POST']);
                $admin->connect('/comments/mark-all-read', ['controller' => 'Comments', 'action' => 'markAllRead'])
                    ->setMethods(['POST']);
                $admin->connect('/comments/reply/{id}', ['controller' => 'Comments', 'action' => 'reply'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                foreach (['approve', 'archive', 'spam', 'delete'] as $commentAction) {
                    $admin->connect('/comments/' . $commentAction . '/{id}', ['controller' => 'Comments', 'action' => $commentAction])
                        ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST']);
                }

                $admin->connect('/media', ['controller' => 'Media', 'action' => 'index']);
                $admin->connect('/media/library', ['controller' => 'Media', 'action' => 'library'])->setMethods(['GET']);
                $admin->connect('/media/upload', ['controller' => 'Media', 'action' => 'upload'])->setMethods(['POST']);
                $admin->connect('/media/detail/{id}', ['controller' => 'Media', 'action' => 'detail'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['GET']);
                $admin->connect('/media/update/{id}', ['controller' => 'Media', 'action' => 'update'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])->setMethods(['POST', 'PATCH']);
                $admin->connect('/media/serve/{id}', ['controller' => 'Media', 'action' => 'serve'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id']);
                $admin->connect('/media/delete/{id}', ['controller' => 'Media', 'action' => 'delete'])
                    ->setPatterns(['id' => '\d+'])->setPass(['id'])
                    ->setMethods(['POST']);
            });

            $ws->connect('/comments', ['controller' => 'Comments', 'action' => 'add'])
                ->setMethods(['POST']);

            $ws->connect('/media/{filename}', ['controller' => 'Media', 'action' => 'serve'])
                ->setPatterns(['filename' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])
                ->setPass(['filename']);

            $ws->connect('/blog', ['controller' => 'Posts', 'action' => 'index']);
            $ws->connect('/blog/{slug}', ['controller' => 'Posts', 'action' => 'view'])
                ->setPatterns(['slug' => '[a-z0-9-]+'])->setPass(['slug']);

            $ws->connect('/graphql', ['controller' => 'Graphql', 'action' => 'execute'])
                ->setMethods(['GET', 'POST']);

            $ws->connect('/*', ['controller' => 'Pages', 'action' => 'view']);
        });
    });
};
