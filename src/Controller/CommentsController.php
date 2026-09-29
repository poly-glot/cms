<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\CommentStatus;
use Cake\Http\Response;
use Override;

final class CommentsController extends AppController
{
    private const array TARGETS = ['Pages', 'Posts', 'CollectionEntries'];

    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['add']);
    }

    public function add(): ?Response
    {
        $this->request->allowMethod('post');
        $comments = $this->fetchTable('Comments');
        $settings = $this->fetchTable('Settings');

        [$type, $id] = $this->resolveTarget();
        $target = $type !== null && $id !== null
            ? $this->fetchTable($type)->find()->where(['id' => $id])->first()
            : null;

        $allowed = $target !== null && (bool) ($target->get('comments_enabled') ?? false);
        if (!$settings->isEnabled('allow_comments') || !$allowed) {
            $this->Flash->error('Comments are closed.');

            return $this->redirect($this->request->referer() ?? '/');
        }

        // Whitelist the public fields explicitly: status, commentable_type and
        // parent_id are set server-side, so the public form can neither
        // self-approve, post to an arbitrary table, nor plant replies (an
        // admin-only flow) on arbitrary comments.
        $comment = $comments->newEntity((array) $this->request->getData(), [
            'fields' => ['author_name', 'author_email', 'body'],
        ]);
        $comment->commentable_type = $type;
        $comment->commentable_id = $id;
        $comment->status = $settings->isEnabled('moderation_queue')
            ? CommentStatus::Pending->value
            : CommentStatus::Approved->value;

        if ($comments->save($comment)) {
            $this->Flash->success($settings->isEnabled('moderation_queue')
                ? 'Thank you — your comment is awaiting moderation.'
                : 'Thank you — your comment has been posted.');
        } else {
            $this->Flash->error('Sorry, your comment could not be posted. Please check your details and try again.');
        }

        return $this->redirect($this->request->referer() ?? '/');
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function resolveTarget(): array
    {
        $type = $this->request->getData('commentable_type');
        $id = $this->request->getData('commentable_id');
        if (in_array($type, self::TARGETS, true) && is_numeric($id)) {
            return [$type, (int) $id];
        }

        $pageId = $this->request->getData('page_id');
        if (is_numeric($pageId)) {
            return ['Pages', (int) $pageId];
        }

        return [null, null];
    }
}
