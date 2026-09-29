<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\CommentStatus;
use App\Model\Tenancy\TenantContext;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

final class CommentsController extends AdminController
{
    public function index(): void
    {
        $comments = $this->paginate(
            $this->fetchTable('Comments')->find('forModeration'),
            ['limit' => 25],
        );

        $this->set(['comments' => $comments]);
    }

    public function approve(int $id): ?Response
    {
        return $this->transition($id, CommentStatus::Approved);
    }

    public function archive(int $id): ?Response
    {
        return $this->transition($id, CommentStatus::Archived);
    }

    public function spam(int $id): ?Response
    {
        return $this->transition($id, CommentStatus::Spam);
    }

    public function delete(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $comments = $this->fetchTable('Comments');
        $comment = $comments->find()->where(['Comments.id' => $id])->first() ?? throw new NotFoundException();
        $this->Authorization->authorize($comment, 'moderate');

        $comments->delete($comment)
            ? $this->Flash->success('Comment deleted.')
            : $this->Flash->error('Could not delete the comment.');

        return $this->redirect(['action' => 'index']);
    }

    public function reply(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $comments = $this->fetchTable('Comments');
        $parent = $comments->find()->where(['Comments.id' => $id])->first() ?? throw new NotFoundException();
        $this->Authorization->authorize($parent, 'moderate');

        $body = $this->request->getData('body');
        if (!is_string($body) || trim($body) === '') {
            $this->Flash->error('A reply cannot be empty.');

            return $this->redirect(['action' => 'index']);
        }

        // Replying implies acceptance: approve a still-pending parent so the
        // thread (and this approved reply) is publicly visible, not stranded.
        if ($parent->status !== CommentStatus::Approved->value) {
            $parent->status = CommentStatus::Approved->value;
            $parent->is_read = true;
            $comments->save($parent);
        }

        $reply = $comments->newEntity([
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
            'parent_id' => $parent->id,
            'author_name' => $this->moderatorName(),
            'body' => $body,
        ]);
        $reply->status = CommentStatus::Approved->value;
        $reply->is_read = true;

        $comments->save($reply)
            ? $this->Flash->success('Reply posted.')
            : $this->Flash->error('Could not post the reply.');

        return $this->redirect(['action' => 'index']);
    }

    public function approveSelected(): ?Response
    {
        $this->request->allowMethod('post');
        $ids = array_values(array_filter(array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0,
            (array) $this->request->getData('ids', []),
        )));

        if ($ids === []) {
            $this->Flash->error('No comments selected.');

            return $this->redirect(['action' => 'index']);
        }

        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $approved = $this->fetchTable('Comments')->updateAll(
            ['status' => CommentStatus::Approved->value, 'is_read' => true],
            [
                'Comments.id IN' => $ids,
                'Comments.status' => CommentStatus::Pending->value,
                'Comments.workspace_id' => $workspaceId,
            ],
        );
        $this->Flash->success(sprintf('%d comment(s) approved.', $approved));

        return $this->redirect(['action' => 'index']);
    }

    public function markAllRead(): ?Response
    {
        $this->request->allowMethod('post');
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $this->fetchTable('Comments')->updateAll(
            ['is_read' => true],
            ['Comments.is_read' => false, 'Comments.workspace_id' => $workspaceId],
        );
        $this->Flash->success('All comments marked as read.');

        return $this->redirect(['action' => 'index']);
    }

    private function transition(int $id, CommentStatus $status): ?Response
    {
        $this->request->allowMethod('post');
        $comments = $this->fetchTable('Comments');
        $comment = $comments->find()->where(['Comments.id' => $id])->first() ?? throw new NotFoundException();
        $this->Authorization->authorize($comment, 'moderate');

        $comment->status = $status->value;
        $comment->is_read = true;

        $comments->save($comment)
            ? $this->Flash->success(sprintf('Comment marked %s.', $status->label()))
            : $this->Flash->error('Could not update the comment.');

        return $this->redirect(['action' => 'index']);
    }

    private function moderatorName(): string
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return 'Moderator';
        }

        $name = $identity->getOriginalData()['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : 'Moderator';
    }
}
