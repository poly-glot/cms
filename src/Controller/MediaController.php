<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Media\MediaFileResponder;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Override;

/**
 * Public, read-only media serving. Referenced by image/file blocks on public
 * pages. Addressed by the media's UUID filename (a capability) rather than its
 * sequential id, so media is not enumerable.
 */
final class MediaController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['serve']);
    }

    public function serve(string $filename): Response
    {
        $media = $this->fetchTable('Media')->find()->where(['Media.filename' => $filename])->first()
            ?? throw new NotFoundException();

        $rendition = $this->request->getQuery('rendition');

        return new MediaFileResponder()->respond($media, $this->response, is_string($rendition) ? $rendition : null);
    }
}
