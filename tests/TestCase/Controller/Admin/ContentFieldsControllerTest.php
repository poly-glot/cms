<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class ContentFieldsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Memberships',
        'app.ContentTypeFieldSchemas',
        'app.Posts',
        'app.Pages',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testEditRendersBuilderForPages(): void
    {
        $this->get('/cabinet/admin/content-model/pages/fields');

        $this->assertResponseOk();
        $this->assertResponseContains('Pages fields');
        $this->assertResponseContains('data-schema-builder');
        $this->assertResponseContains('data-add-field');
    }

    public function testEditRendersBuilderForPosts(): void
    {
        $this->get('/cabinet/admin/content-model/posts/fields');

        $this->assertResponseOk();
        $this->assertResponseContains('Posts fields');
        $this->assertResponseContains('data-schema-builder');
    }

    public function testSavePersistsSchemaAndRedirects(): void
    {
        $this->post('/cabinet/admin/content-model/posts/fields', [
            'field_schema_json' => (string) json_encode([
                ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'required' => true],
                ['name' => 'rating', 'label' => 'Rating', 'type' => 'number', 'required' => false],
            ]),
        ]);

        $this->assertRedirect('/cabinet/admin/content-model/posts/fields');

        $schema = $this->fetchTable('ContentTypeFieldSchemas')
            ->find()->where(['subject_type' => 'Posts'])->firstOrFail();
        $this->assertCount(2, $schema->field_schema);
        $this->assertSame('subtitle', $schema->field_schema[0]['name'] ?? null);
        $this->assertSame(1, $schema->workspace_id);
    }

    public function testSaveRejectsReservedFieldName(): void
    {
        $this->post('/cabinet/admin/content-model/pages/fields', [
            'field_schema_json' => (string) json_encode([
                ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
            ]),
        ]);

        $count = $this->fetchTable('ContentTypeFieldSchemas')
            ->find()->where(['subject_type' => 'Pages'])->count();
        $this->assertSame(0, $count);
    }

    public function testUnknownTypeIs404(): void
    {
        $this->get('/cabinet/admin/content-model/widgets/fields');

        $this->assertResponseCode(404);
    }

    public function testNonEditorialMemberForbidden(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'contributor']]);
        $this->get('/cabinet/admin/content-model/pages/fields');

        $this->assertResponseCode(403);
    }
}
