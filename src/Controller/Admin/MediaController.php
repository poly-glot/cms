<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Exception\InvalidUploadException;
use App\Model\Entity\Media;
use App\Model\Entity\MediaRendition;
use App\Service\Media\MediaFileResponder;
use App\Service\Media\UploadService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;

final class MediaController extends AdminController
{
    private const int INDEX_PER_PAGE = 48;
    private const int LIBRARY_PER_PAGE = 24;

    /** @var list<string> */
    private const array MEDIA_KINDS = ['image', 'document', 'audio', 'video'];

    public function index(): void
    {
        [$query, $kind, $keyword] = $this->filteredMediaQuery();

        $this->set([
            'media' => $this->paginate($query, ['limit' => self::INDEX_PER_PAGE]),
            'activeKind' => $kind,
            'keyword' => $keyword,
        ]);
    }

    public function library(): Response
    {
        [$query] = $this->filteredMediaQuery();

        $pageParam = $this->request->getQuery('page');
        $page = is_numeric($pageParam) ? max(1, (int) $pageParam) : 1;
        $offset = ($page - 1) * self::LIBRARY_PER_PAGE;

        $total = $query->count();
        $items = $query->limit(self::LIBRARY_PER_PAGE)->offset($offset)->all()->toList();

        return $this->json([
            'items' => array_map($this->cardPayload(...), $items),
            'hasMore' => $offset + self::LIBRARY_PER_PAGE < $total,
            'total' => $total,
        ]);
    }

    public function detail(int $id): Response
    {
        $media = $this->fetchTable('Media')
            ->find()
            ->where(['Media.id' => $id])
            ->contain(['Renditions' => static fn (SelectQuery $query): SelectQuery => $query->orderByDesc('Renditions.width')])
            ->first() ?? throw new NotFoundException();
        $this->Authorization->authorize($media, 'view');

        return $this->json($this->detailPayload($media));
    }

    public function update(int $id): Response
    {
        $this->request->allowMethod(['post', 'patch']);
        $mediaTable = $this->fetchTable('Media');
        $media = $mediaTable->find()->where(['Media.id' => $id])->first() ?? throw new NotFoundException();
        $this->Authorization->authorize($media, 'update');

        $alt = $this->request->getData('alt');
        $media->alt = is_string($alt) && trim($alt) !== '' ? $alt : null;

        if ($mediaTable->save($media) === false) {
            return $this->json(['error' => 'Could not save.'], 422);
        }

        return $this->json(['id' => $media->id, 'alt' => $media->alt]);
    }

    public function upload(): Response
    {
        $this->request->allowMethod('post');
        $file = $this->request->getUploadedFile('file');

        if ($file === null) {
            return $this->json(['error' => 'No file uploaded.'], 400);
        }

        try {
            $media = new UploadService()->store($file, uploaderId: $this->currentUserId());
        } catch (InvalidUploadException $error) {
            return $this->json(['error' => $error->getMessage()], 422);
        }

        return $this->json($this->cardPayload($media));
    }

    public function serve(int $id): Response
    {
        $media = $this->fetchTable('Media')->find()->where(['Media.id' => $id])->first()
            ?? throw new NotFoundException();
        $this->Authorization->authorize($media, 'view');

        $rendition = $this->request->getQuery('rendition');

        return new MediaFileResponder()->respond($media, $this->response, is_string($rendition) ? $rendition : null);
    }

    public function delete(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $mediaTable = $this->fetchTable('Media');
        $media = $mediaTable->find()->where(['Media.id' => $id])->first()
            ?? throw new NotFoundException();
        $this->Authorization->authorize($media);

        $deleted = $mediaTable->delete($media);
        if ($deleted) {
            new MediaFileResponder()->purge($media);
        }

        if ($this->request->is('json')) {
            return $deleted
                ? $this->json(['deleted' => true])
                : $this->json(['error' => 'Could not delete.'], 422);
        }

        $deleted ? $this->Flash->success('Deleted.') : $this->Flash->error('Could not delete.');

        return $this->redirect(['prefix' => 'Admin', 'controller' => 'Media', 'action' => 'index']);
    }

    /**
     * @return array{0: SelectQuery<Media>, 1: string, 2: string}
     */
    private function filteredMediaQuery(): array
    {
        $media = $this->fetchTable('Media');
        $query = $media->find()
            ->orderByDesc('Media.created')
            ->orderByDesc('Media.id');

        $kind = $this->mediaKind($this->request->getQuery('kind'));
        if ($kind !== 'all') {
            $query = $media->findOfKind($query, $kind);
        }

        $keyword = $this->request->getQuery('q');
        $keyword = is_string($keyword) ? mb_substr(trim($keyword), 0, 100) : '';
        if ($keyword !== '') {
            $query = $media->findSearch($query, $keyword);
        }

        return [$query, $kind, $keyword];
    }

    private function mediaKind(mixed $value): string
    {
        return is_string($value) && in_array($value, self::MEDIA_KINDS, true) ? $value : 'all';
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'kind' => $media->kind,
            'extension' => $media->extension,
            'mime' => $media->mime,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'isImage' => $media->isImage,
            'alt' => $media->alt,
            'thumbUrl' => $this->workspacePath() . '/admin/media/serve/' . $media->id . '?rendition=thumb',
            'publicUrl' => $this->workspacePath() . '/media/' . $media->filename . '?rendition=large',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(Media $media): array
    {
        $base = $this->workspacePath() . '/admin/media/serve/' . $media->id;
        $renditions = array_map(
            static fn (MediaRendition $rendition): array => [
                'name' => $rendition->name,
                'width' => $rendition->width,
                'height' => $rendition->height,
                'size' => $rendition->size,
                'url' => $base . '?rendition=' . $rendition->name,
            ],
            $media->renditions,
        );

        return [
            'id' => $media->id,
            'name' => $media->name,
            'kind' => $media->kind,
            'mime' => $media->mime,
            'extension' => $media->extension,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'alt' => $media->alt,
            'isImage' => $media->isImage,
            'uploaded' => $media->created->format(\DATE_ATOM),
            'url' => $this->workspacePath() . '/admin/media/serve/' . $media->id,
            'previewUrl' => $this->workspacePath() . '/admin/media/serve/' . $media->id . '?rendition=large',
            'renditions' => $renditions,
        ];
    }
}
