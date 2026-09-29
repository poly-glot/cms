<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\CollectionEntry;
use App\Model\Entity\Page;
use App\Model\Entity\Post;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use App\Policy\AuthoredContentPolicy;
use App\Policy\PagePolicy;
use Authorization\IdentityInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthoredContentPolicyTest extends TestCase
{
    /**
     * @return array<string, array{AuthoredContentPolicy, class-string<Page|Post|CollectionEntry>}>
     */
    public static function contentTypes(): array
    {
        return [
            'page' => [new PagePolicy(), Page::class],
            'post' => [new AuthoredContentPolicy(), Post::class],
            'collection entry' => [new AuthoredContentPolicy(), CollectionEntry::class],
        ];
    }

    /**
     * @param class-string<Page|Post|CollectionEntry> $entityClass
     */
    #[DataProvider('contentTypes')]
    public function testEditorialUserCanActOnAnyContent(AuthoredContentPolicy $policy, string $entityClass): void
    {
        $editor = $this->identity(id: 10, role: 'editor');
        $someoneElses = new $entityClass(['author_id' => 99]);

        $this->assertTrue($policy->canEdit($editor, $someoneElses));
        $this->assertTrue($policy->canDelete($editor, $someoneElses));
        $this->assertTrue($policy->canPublish($editor, $someoneElses));
        $this->assertTrue($policy->canSchedule($editor, $someoneElses));
    }

    /**
     * @param class-string<Page|Post|CollectionEntry> $entityClass
     */
    #[DataProvider('contentTypes')]
    public function testAuthorCanEditOwnContentButNotOthers(AuthoredContentPolicy $policy, string $entityClass): void
    {
        $author = $this->identity(id: 10, role: 'author');
        $own = new $entityClass(['author_id' => 10]);
        $someoneElses = new $entityClass(['author_id' => 99]);

        $this->assertTrue($policy->canEdit($author, $own));
        $this->assertFalse($policy->canEdit($author, $someoneElses));
    }

    /**
     * @param class-string<Page|Post|CollectionEntry> $entityClass
     */
    #[DataProvider('contentTypes')]
    public function testAuthorCannotPublishEvenOwnContent(AuthoredContentPolicy $policy, string $entityClass): void
    {
        $author = $this->identity(id: 10, role: 'author');
        $own = new $entityClass(['author_id' => 10]);

        $this->assertFalse($policy->canPublish($author, $own));
        $this->assertFalse($policy->canSchedule($author, $own));
    }

    /**
     * @param class-string<Page|Post|CollectionEntry> $entityClass
     */
    #[DataProvider('contentTypes')]
    public function testEveryoneCanAdd(AuthoredContentPolicy $policy, string $entityClass): void
    {
        $contributor = $this->identity(id: 10, role: 'contributor');

        $this->assertTrue($policy->canAdd($contributor, new $entityClass()));
    }

    private function identity(int $id, string $role): IdentityInterface
    {
        TenantContext::instance()->activate(1, UserRole::fromValue($role), 'cabinet');

        $stub = $this->createStub(IdentityInterface::class);
        $stub->method('offsetExists')->willReturnCallback(static fn (string $key): bool => $key === 'id');
        $stub->method('offsetGet')->willReturnCallback(static fn (string $key): ?int => $key === 'id' ? $id : null);

        return $stub;
    }
}
