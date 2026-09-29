<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TagsTable;
use Cake\TestSuite\TestCase;

final class TagsTableTest extends TestCase
{
    protected array $fixtures = ['app.Tags'];
    private TagsTable $Tags;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Tags = $this->fetchTable('Tags');
    }

    public function testKebabCasesSlugOnSave(): void
    {
        $tag = $this->Tags->newEntity(['label' => 'About Us']);
        $saved = $this->Tags->save($tag);

        $this->assertNotFalse($saved);
        $this->assertSame('about-us', $saved->slug);
        $this->assertSame('About Us', $saved->label);
    }

    public function testKebabCasesNonAsciiLabelsToLowercaseAscii(): void
    {
        $tag = $this->Tags->newEntity(['label' => 'ÆØÅ Café']);
        $saved = $this->Tags->save($tag);

        $this->assertNotFalse($saved);
        $this->assertSame('aeoa-cafe', $saved->slug);
    }

    public function testEnforcesUniqueSlug(): void
    {
        $tag = $this->Tags->newEntity(['label' => 'heritage']);
        $saved = $this->Tags->save($tag);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('slug', $tag->getErrors());
    }

    public function testFindOrCreateReturnsExistingForSameSlug(): void
    {
        $first = $this->Tags->findOrCreateBySlug('heritage');
        $second = $this->Tags->findOrCreateBySlug('Heritage');

        $this->assertSame(1, $first->id);
        $this->assertSame(1, $second->id);
    }

    public function testFindOrCreateInsertsWhenMissing(): void
    {
        $tag = $this->Tags->findOrCreateBySlug('Brand New Tag');

        $this->assertSame('brand-new-tag', $tag->slug);
        $this->assertSame('Brand New Tag', $tag->label);
        $this->assertNotNull($tag->id);
    }
}
