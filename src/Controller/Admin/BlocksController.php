<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Block;
use App\Model\Enum\BlockType;
use Cake\Http\Response;

final class BlocksController extends AdminController
{
    use BlockMediaTrait;

    public function index(): void
    {
        $type = $this->request->getQuery('type');
        $type = is_string($type) && $type !== '' ? $type : null;
        $blocks = $this->fetchTable('Blocks')->find('forLibrary', blockType: $type)->all();

        $this->set([
            'blocks' => $blocks,
            'mediaById' => $this->blockMediaMap($blocks),
            'activeType' => $type,
            'types' => BlockType::cases(),
        ]);
    }

    public function list(): Response
    {
        $blocks = $this->fetchTable('Blocks');
        $payload = $blocks->find('forPicker')->all()->map(static fn (Block $block): array => [
            'id' => $block->id,
            'type' => $block->block_type,
            'name' => $block->name,
        ])->toList();

        return $this->json($payload);
    }

    public function add(): ?Response
    {
        $blocks = $this->fetchTable('Blocks');
        $block = $blocks->newEmptyEntity();
        $type = $this->blockTypeParam($this->request->getQuery('type'));

        if ($this->request->is('post')) {
            /** @var array<string, mixed> $data */
            $data = (array) $this->request->getData();
            $block = $blocks->patchEntity($block, $data);
            $block->author_id = $this->currentUserId();
            $this->Authorization->authorize($block);

            if ($blocks->save($block)) {
                $this->Flash->success('Block created.');

                return $this->redirect(['action' => 'edit', $block->id]);
            }

            $this->Flash->error('There were errors saving the block.');
            $type = $this->blockTypeParam($data['block_type'] ?? null);
        }

        if ($type !== '' && ($block->block_type ?? '') === '') {
            $block->block_type = $type;
        }

        $this->set([
            'block' => $block,
            'type' => $type,
            'types' => BlockType::cases(),
            'previewMedia' => $type !== '' ? $this->blockMedia($block) : null,
        ]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $blocks = $this->fetchTable('Blocks');
        $block = $blocks->get($id);
        $this->Authorization->authorize($block);

        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var array<string, mixed> $data */
            $data = (array) $this->request->getData();
            $block = $blocks->patchEntity($block, $data);

            if ($blocks->save($block)) {
                $this->Flash->success('Block saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }

            $this->Flash->error('Could not save the block.');
        }

        $this->set([
            'block' => $block,
            'type' => $block->block_type,
            'types' => BlockType::cases(),
            'previewMedia' => $this->blockMedia($block),
        ]);

        return null;
    }

    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post']);
        $blocks = $this->fetchTable('Blocks');
        $block = $blocks->get($id);
        $this->Authorization->authorize($block);

        if ($blocks->delete($block)) {
            $this->Flash->success('Block deleted.');
        } else {
            $this->Flash->error('This block is in use on one or more pages and cannot be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    private function blockTypeParam(mixed $value): string
    {
        return is_string($value) && BlockType::tryFrom($value) !== null ? $value : '';
    }
}
