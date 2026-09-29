<?php

declare(strict_types=1);

namespace App\Service\Media;

use App\Exception\InvalidUploadException;
use App\Model\Entity\Media;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use finfo;
use Psr\Http\Message\UploadedFileInterface;

final class UploadService
{
    use LocatorAwareTrait;

    private const array ALLOWED = [
        'image/png' => ['png'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
    ];

    private const array MAX_SIZE = [
        'image' => 10 * 1024 * 1024,
        'application' => 50 * 1024 * 1024,
        'text' => 5 * 1024 * 1024,
    ];

    public function store(UploadedFileInterface $upload, int $uploaderId): Media
    {
        if ($upload->getError() !== \UPLOAD_ERR_OK) {
            throw new InvalidUploadException('Upload error: ' . $upload->getError());
        }

        $clientName = $this->safeName((string) $upload->getClientFilename());
        $size = (int) $upload->getSize();
        $stream = $upload->getStream();
        $uri = $stream->getMetadata('uri');
        $tmpPath = is_string($uri) ? $uri : '';

        $finfo = new finfo(\FILEINFO_MIME_TYPE);
        $detected = (string) $finfo->file($tmpPath);

        if (!isset(self::ALLOWED[$detected])) {
            throw new InvalidUploadException(sprintf('Unsupported mime: %s', $detected));
        }

        $extension = strtolower(pathinfo($clientName, \PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED[$detected], true)) {
            throw new InvalidUploadException(sprintf('Extension .%s does not match detected type %s', $extension, $detected));
        }

        $category = explode('/', $detected, 2)[0];
        $cap = self::MAX_SIZE[$category];
        if ($size > $cap) {
            throw new InvalidUploadException(sprintf('File too large: %d bytes (cap %d)', $size, $cap));
        }

        $width = null;
        $height = null;
        if ($category === 'image') {
            $dims = @getimagesize($tmpPath);
            if ($dims !== false) {
                $width = $dims[0];
                $height = $dims[1];
            }
        }

        $uuid = Text::uuid();
        $year = date('Y');
        $month = date('m');
        $configured = Configure::read('App.uploads.path', WWW_ROOT . '../storage/uploads');
        $rootPath = is_string($configured) ? $configured : WWW_ROOT . '../storage/uploads';
        $relative = $year . '/' . $month . '/' . $uuid;
        $privateDir = $rootPath . '/private/' . $relative;
        $publicDir = $rootPath . '/public/' . $relative;

        foreach ([$privateDir, $publicDir] as $target) {
            if (!mkdir($target, 0o755, true) && !is_dir($target)) {
                throw new InvalidUploadException('Failed to create storage directory');
            }
        }

        $originalPath = $privateDir . '/' . $clientName;
        $upload->moveTo($originalPath);

        $mediaTable = $this->fetchTable('Media');
        $media = $mediaTable->newEntity([
            'name' => $clientName,
        ]);
        $media->filename = $uuid;
        $media->mime = $detected;
        $media->size = $size;
        $media->width = $width;
        $media->height = $height;
        $media->uploaded_by = $uploaderId;

        $saved = $mediaTable->save($media);
        if ($saved === false) {
            unlink($originalPath);
            throw new InvalidUploadException('Failed to persist media row');
        }

        if ($category === 'image') {
            $this->storeRenditions($saved, $originalPath, $publicDir);
        }

        return $saved;
    }

    private function storeRenditions(Media $media, string $sourcePath, string $targetDir): void
    {
        $generated = new RenditionGenerator()->generate($sourcePath, $targetDir);
        if ($generated === []) {
            return;
        }

        $renditionsTable = $this->fetchTable('MediaRenditions');
        $rows = array_map(
            static fn (array $data): array => $data + ['media_id' => $media->id],
            $generated,
        );

        $renditionsTable->saveMany($renditionsTable->newEntities($rows));
    }

    /**
     * Reduces a client-supplied filename to a bare basename, stripping any
     * directory components (both separators) so the stored name can never carry
     * path-traversal segments into the on-disk path.
     */
    private function safeName(string $clientName): string
    {
        $name = basename(str_replace('\\', '/', $clientName));
        if (in_array($name, ['', '.', '..'], true)) {
            throw new InvalidUploadException('Invalid file name.');
        }

        return $name;
    }
}
