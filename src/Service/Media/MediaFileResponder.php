<?php

declare(strict_types=1);

namespace App\Service\Media;

use App\Model\Entity\Media;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Resolves a Media entity to its on-disk files and streams them inline.
 * Shared by the authenticated admin serve and the public block serve so the
 * storage-path convention lives in one place. Renditions are addressed by name
 * with deterministic filenames ({stem}-{name}.{ext}) and fall back to the
 * original when the requested rendition was never generated.
 */
final class MediaFileResponder
{
    private const array RENDITIONS = ['large', 'medium', 'small', 'thumb'];

    public function respond(Media $media, Response $response, ?string $rendition = null): Response
    {
        // Canonicalise to an absolute path: the configured root may contain
        // `..` (e.g. WWW_ROOT/../storage), which Response::withFile() rejects.
        $path = realpath($this->path($media, $rendition));
        $root = realpath($this->root());

        // Containment guard: never stream a file resolved outside the uploads
        // root, even if a stored name somehow contains traversal segments.
        if ($path === false || $root === false || !str_starts_with($path, $root . \DIRECTORY_SEPARATOR)) {
            throw new NotFoundException();
        }

        return $response
            ->withType($media->mime)
            ->withHeader('Content-Disposition', 'inline; filename="' . addslashes((string) $media->name) . '"')
            ->withFile($path);
    }

    public function path(Media $media, ?string $rendition = null): string
    {
        $original = $this->privateDirectory($media) . '/' . $media->name;

        if ($rendition === null || !in_array($rendition, self::RENDITIONS, true)) {
            return $original;
        }

        $stem = pathinfo((string) $media->name, \PATHINFO_FILENAME);
        $extension = pathinfo((string) $media->name, \PATHINFO_EXTENSION);
        $renditionPath = sprintf('%s/%s-%s.%s', $this->publicDirectory($media), $stem, $rendition, $extension);

        return is_file($renditionPath) ? $renditionPath : $original;
    }

    public function directory(Media $media): string
    {
        return $this->privateDirectory($media);
    }

    public function privateDirectory(Media $media): string
    {
        return $this->root() . '/private/' . $this->relativePath($media);
    }

    public function publicDirectory(Media $media): string
    {
        return $this->root() . '/public/' . $this->relativePath($media);
    }

    public function publicRenditionPath(Media $media, string $rendition): string
    {
        $stem = pathinfo((string) $media->name, \PATHINFO_FILENAME);
        $extension = pathinfo((string) $media->name, \PATHINFO_EXTENSION);

        return sprintf('public/%s/%s-%s.%s', $this->relativePath($media), $stem, $rendition, $extension);
    }

    private function relativePath(Media $media): string
    {
        return $media->created->format('Y/m') . '/' . $media->filename;
    }

    private function root(): string
    {
        $configured = Configure::read('App.uploads.path');

        return is_string($configured) ? $configured : WWW_ROOT . '../storage/uploads';
    }

    /**
     * Removes the original and every rendition by deleting the media's UUID
     * directory. Each media lives in its own UUID folder, so this never touches
     * another row's files. Best-effort: a missing directory is a no-op.
     */
    public function purge(Media $media): void
    {
        foreach ([$this->privateDirectory($media), $this->publicDirectory($media)] as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            foreach (glob($directory . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory);
        }
    }
}
