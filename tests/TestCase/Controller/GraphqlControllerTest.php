<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\Log\Engine\ArrayLog;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

final class GraphqlControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /** @var array<int, string> */
    protected array $fixtures = [
        'app.Users',
        'app.Memberships',
        'app.Pages',
        'app.Posts',
        'app.Collections',
        'app.CollectionEntries',
        'app.Tags',
        'app.Taggables',
        'app.PersonalAccessTokens',
        'app.CollectionEntryReferences',
        'app.Comments',
    ];

    public function testAnonymousReadsPublishedPostBySlug(): void
    {
        $payload = $this->graphql('cabinet', '{ post(slug: "hello") { title slug status } }');

        $this->assertResponseOk();
        $this->assertSame('Hello from the workshop', Hash::get($payload, 'data.post.title'));
        $this->assertSame('LIVE', Hash::get($payload, 'data.post.status'));
    }

    public function testAnonymousCannotSeeDraft(): void
    {
        $payload = $this->graphql('cabinet', '{ post(slug: "draft-thoughts") { title } }');

        $this->assertResponseOk();
        $this->assertNull(Hash::get($payload, 'data.post'));
    }

    public function testTenantIsolation(): void
    {
        $payload = $this->graphql('atelier', '{ post(slug: "hello") { title } }');

        $this->assertResponseOk();
        $this->assertNull(Hash::get($payload, 'data.post'), 'A cabinet post must never surface on the atelier endpoint.');
    }

    public function testPreviewTokenSeesDraft(): void
    {
        $this->configRequest(['headers' => ['Authorization' => 'Bearer test-preview-token']]);
        $payload = $this->graphql('cabinet', '{ post(slug: "draft-thoughts") { title status } }');

        $this->assertResponseOk();
        $this->assertSame('Draft thoughts', Hash::get($payload, 'data.post.title'));
        $this->assertSame('DRAFT', Hash::get($payload, 'data.post.status'));
    }

    public function testTokenForOtherWorkspaceRejected(): void
    {
        $this->configRequest(['headers' => ['Authorization' => 'Bearer test-preview-token']]);
        $payload = $this->graphql('atelier', '{ post(slug: "hello") { title } }');

        $this->assertResponseOk();
        $this->assertNull(Hash::get($payload, 'data'), 'A workspace-1 token must resolve to nothing on the atelier endpoint.');
        $this->assertNotEmpty($payload['errors'] ?? null);
    }

    public function testAnonymousStillCannotPreview(): void
    {
        $payload = $this->graphql('cabinet', '{ post(slug: "draft-thoughts") { title } }');

        $this->assertResponseOk();
        $this->assertNull(Hash::get($payload, 'data.post'), 'Without a preview token the draft choke-point must stay closed.');
    }

    public function testUnknownWorkspaceIs404(): void
    {
        $this->post('/no-such-workspace/graphql', ['query' => '{ post(slug: "hello") { title } }']);

        $this->assertResponseCode(404);
    }

    public function testEntryDataExposedAsJsonAndFieldSchemaTyped(): void
    {
        $payload = $this->graphql('cabinet', '{
            collection(slug: "products") {
                fieldSchema { name type required }
                entries { items { title data } pageInfo { total } }
            }
        }');

        $this->assertResponseOk();
        $this->assertSame('price', Hash::get($payload, 'data.collection.fieldSchema.0.name'));
        $this->assertSame('NUMBER', Hash::get($payload, 'data.collection.fieldSchema.0.type'));
        $this->assertTrue(Hash::get($payload, 'data.collection.fieldSchema.0.required'));

        $this->assertSame(1, Hash::get($payload, 'data.collection.entries.pageInfo.total'));
        $this->assertSame('Maple Chair', Hash::get($payload, 'data.collection.entries.items.0.title'));
        $this->assertSame(420, Hash::get($payload, 'data.collection.entries.items.0.data.price'));
    }

    public function testPostsConnectionPaginates(): void
    {
        $this->seedLivePosts(15);

        $firstPage = $this->graphql('cabinet', '{ posts(page: 1, perPage: 10) { items { id } pageInfo { page total hasNextPage } } }');
        $this->assertResponseOk();
        $this->assertSame(16, Hash::get($firstPage, 'data.posts.pageInfo.total'));
        $this->assertTrue(Hash::get($firstPage, 'data.posts.pageInfo.hasNextPage'));

        $secondPage = $this->graphql('cabinet', '{ posts(page: 2, perPage: 10) { items { id } pageInfo { page hasNextPage } } }');
        $this->assertSame(2, Hash::get($secondPage, 'data.posts.pageInfo.page'));
        $this->assertFalse(Hash::get($secondPage, 'data.posts.pageInfo.hasNextPage'));
        $items = Hash::get($secondPage, 'data.posts.items');
        $this->assertIsArray($items);
        $this->assertCount(6, $items);
    }

    public function testAuthorBatchedNoNPlusOne(): void
    {
        $this->seedLivePost('alpha', 1);
        $this->seedLivePost('beta', 2);
        $this->seedLivePost('gamma', 3);

        $queryLog = new ArrayLog();
        $this->fetchTable('Users')->getConnection()->getDriver()->setLogger($queryLog);

        $this->graphql('cabinet', '{ posts(perPage: 50) { items { id author { id name } } } }');
        $this->assertResponseOk();

        $this->assertCount(1, preg_grep('/from\s+`?users`?\b/i', $queryLog->read()) ?: [], 'Author resolution must issue exactly one Users query.');
    }

    public function testWriteTokenCreatesPostRoundTrip(): void
    {
        $payload = $this->graphqlAs('test-admin-write', 'cabinet', 'mutation {
            createPost(input: { title: "API Journal", slug: "api-journal", body: "<p>From the API.</p>", status: LIVE }) {
                id slug status author { id name }
            }
        }');

        $this->assertResponseOk();
        $this->assertArrayNotHasKey('errors', $payload);
        $this->assertSame('api-journal', Hash::get($payload, 'data.createPost.slug'));
        $this->assertSame('LIVE', Hash::get($payload, 'data.createPost.status'));
        $this->assertSame('1', Hash::get($payload, 'data.createPost.author.id'));

        $post = $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'api-journal'])->firstOrFail();
        $this->assertSame(1, $post->author_id, 'author_id must be set from the token user, never the client.');

        $roundTrip = $this->graphql('cabinet', '{ post(slug: "api-journal") { title author { id } } }');
        $this->assertSame('API Journal', Hash::get($roundTrip, 'data.post.title'));
    }

    public function testEditorTokenCanPublishAuthorCannot(): void
    {
        $published = $this->graphqlAs('test-editor-write', 'cabinet', 'mutation { publishPost(id: 2) { status } }');
        $this->assertResponseOk();
        $this->assertSame('LIVE', Hash::get($published, 'data.publishPost.status'), 'An editor is editorial and may publish.');

        $denied = $this->graphqlAs('test-author-write', 'cabinet', 'mutation { publishPost(id: 1) { status } }');
        $this->assertSame('FORBIDDEN', Hash::get($denied, 'errors.0.extensions.code'), 'A contributor is not editorial and must be denied publish.');
    }

    public function testAuthorCannotEditAnothersPost(): void
    {
        $payload = $this->graphqlAs('test-author-write', 'cabinet', 'mutation {
            updatePost(id: 1, input: { title: "Hijacked" }) { id }
        }');

        $this->assertSame('FORBIDDEN', Hash::get($payload, 'errors.0.extensions.code'));

        $post = $this->fetchTable('Posts')->get(1);
        $this->assertSame('Hello from the workshop', $post->title, 'A non-owning contributor must not mutate another author\'s post.');
    }

    public function testMutationValidationErrorsMapToGraphqlErrors(): void
    {
        $payload = $this->graphqlAs('test-admin-write', 'cabinet', 'mutation {
            createPost(input: { title: "Clashing slug", slug: "hello", status: DRAFT }) { id }
        }');

        $this->assertResponseOk();
        $this->assertSame('VALIDATION', Hash::get($payload, 'errors.0.extensions.code'));
        $this->assertSame('slug', Hash::get($payload, 'errors.0.extensions.field'));

        $count = $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'hello'])->count();
        $this->assertSame(1, $count, 'A rejected create must not partially write a second row.');
    }

    public function testCreateEntryValidatesDynamicData(): void
    {
        $missing = $this->graphqlAs('test-admin-write', 'cabinet', 'mutation {
            createEntry(collection: "products", input: { title: "Oak Bench", slug: "oak-bench", data: {} }) { id }
        }');

        $this->assertSame('VALIDATION', Hash::get($missing, 'errors.0.extensions.code'));
        $this->assertSame('price', Hash::get($missing, 'errors.0.extensions.field'), 'A missing required custom field must surface as a dotted-path error.');
        $this->assertSame(0, $this->fetchTable('CollectionEntries')->find()->where(['slug' => 'oak-bench'])->count());

        $created = $this->graphqlAs('test-admin-write', 'cabinet', 'mutation {
            createEntry(collection: "products", input: { title: "Oak Bench", slug: "oak-bench", status: LIVE, data: { price: 275 } }) { id data }
        }');

        $this->assertResponseOk();
        $this->assertArrayNotHasKey('errors', $created);
        $this->assertSame(275, Hash::get($created, 'data.createEntry.data.price'));

        $entry = $this->fetchTable('CollectionEntries')->find()->where(['slug' => 'oak-bench'])->firstOrFail();
        $this->assertSame(1, $entry->author_id);
        $references = $this->fetchTable('CollectionEntryReferences')->find()->where(['source_entry_id' => $entry->id])->count();
        $this->assertSame(0, $references, 'Reference reconciliation must run on save (no reference fields => no edges).');
    }

    public function testReadOnlyTokenCannotMutate(): void
    {
        $payload = $this->graphqlAs('test-preview-token', 'cabinet', 'mutation {
            createPost(input: { title: "Should not persist", slug: "should-not-persist", status: DRAFT }) { id }
        }');

        $this->assertSame('FORBIDDEN', Hash::get($payload, 'errors.0.extensions.code'), 'A read/preview token lacks the write scope.');
        $this->assertSame(0, $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'should-not-persist'])->count());
    }

    public function testTokenWithoutMembershipCannotMutate(): void
    {
        $payload = $this->graphqlAs('test-nomember-write', 'atelier', 'mutation {
            createPost(input: { title: "No membership", slug: "no-membership", status: DRAFT }) { id }
        }');

        $this->assertSame('FORBIDDEN', Hash::get($payload, 'errors.0.extensions.code'), 'A write token whose user has no membership in the workspace must be denied.');

        $count = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            fn (): int => $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'no-membership'])->count(),
            workspaceSlug: 'atelier',
        );
        $this->assertSame(0, $count, 'The unmembered write must not have created a row.');
    }

    public function testMutationTenantIsolation(): void
    {
        $posts = $this->fetchTable('Posts');

        $foreign = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            static fn (): EntityInterface => $posts->saveOrFail($posts->newEntity([
                'title' => 'Atelier only',
                'slug' => 'atelier-only',
                'status' => 'live',
                'author_id' => 1,
                'published_at' => new DateTime('-1 hour'),
            ])),
            workspaceSlug: 'atelier',
        );

        $payload = $this->graphqlAs('test-admin-write', 'cabinet', sprintf(
            'mutation { updatePost(id: %d, input: { title: "Cross-tenant hijack" }) { id } }',
            $foreign->id,
        ));

        $this->assertResponseOk();
        $this->assertNull(Hash::get($payload, 'data.updatePost'));
        $this->assertNotEmpty($payload['errors'] ?? null);

        $reloaded = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            fn (): EntityInterface => $this->fetchTable('Posts')->get($foreign->id),
            workspaceSlug: 'atelier',
        );
        $this->assertSame('Atelier only', $reloaded->title, 'A cabinet token must never mutate an atelier row.');
    }

    public function testEditorModeratesCommentAuthorCannot(): void
    {
        $approved = $this->graphqlAs('test-editor-write', 'cabinet', 'mutation { approveComment(id: 1) { id status } }');
        $this->assertResponseOk();
        $this->assertSame('APPROVED', Hash::get($approved, 'data.approveComment.status'));

        $denied = $this->graphqlAs('test-author-write', 'cabinet', 'mutation { approveComment(id: 2) { id } }');
        $this->assertSame('FORBIDDEN', Hash::get($denied, 'errors.0.extensions.code'), 'Only editorial roles may moderate comments.');

        $comment = $this->fetchTable('Comments')->get(2);
        $this->assertSame('pending', $comment->status, 'A denied moderation must not change the comment.');
    }

    public function testQueryOverGetIsServed(): void
    {
        $payload = $this->graphqlOverGet('cabinet', '{ post(slug: "hello") { title } }');

        $this->assertResponseOk();
        $this->assertSame('Hello from the workshop', Hash::get($payload, 'data.post.title'));
    }

    public function testMutationOverGetIsRejected(): void
    {
        $this->configRequest(['headers' => ['Authorization' => 'Bearer test-admin-write']]);
        $payload = $this->graphqlOverGet('cabinet', 'mutation {
            createPost(input: { title: "Via GET", slug: "via-get", status: DRAFT }) { id }
        }');

        $this->assertResponseOk();
        $this->assertSame('GET supports only query operation', Hash::get($payload, 'errors.0.message'));
        $this->assertSame(0, $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'via-get'])->count(), 'A GET request must never run a mutation.');
    }

    /**
     * @return array<array-key, mixed>
     */
    private function graphql(string $workspaceSlug, string $query): array
    {
        $this->post('/' . $workspaceSlug . '/graphql', ['query' => $query]);

        return $this->decodedBody();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function graphqlOverGet(string $workspaceSlug, string $query): array
    {
        $this->get('/' . $workspaceSlug . '/graphql?query=' . rawurlencode($query));

        return $this->decodedBody();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodedBody(): array
    {
        $decoded = json_decode($this->_getBodyAsString(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function graphqlAs(string $token, string $workspaceSlug, string $query): array
    {
        $this->configRequest(['headers' => ['Authorization' => 'Bearer ' . $token]]);

        return $this->graphql($workspaceSlug, $query);
    }

    private function seedLivePosts(int $count): void
    {
        for ($index = 1; $index <= $count; ++$index) {
            $this->seedLivePost('bulk-live-' . $index, 1);
        }
    }

    private function seedLivePost(string $slug, int $authorId): void
    {
        $posts = $this->fetchTable('Posts');
        $posts->saveOrFail($posts->newEntity([
            'title' => 'Post ' . $slug,
            'slug' => $slug,
            'status' => 'live',
            'author_id' => $authorId,
            'published_at' => new DateTime('-1 hour'),
        ]));
    }
}
