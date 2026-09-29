<?php

declare(strict_types=1);

namespace App\Service\Media;

use Cake\Log\Log;
use Closure;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

final readonly class RenditionGenerator
{
    private const array SCALED = ['large' => 1920, 'medium' => 960, 'small' => 480];

    private const int THUMB_SIZE = 240;

    /**
     * @return list<array{name: string, filename: string, width: int, height: int, size: int}>
     */
    public function generate(string $sourcePath, ?string $targetDir = null): array
    {
        if (!is_file($sourcePath)) {
            return [];
        }

        $manager = new ImageManager(new Driver());
        $writeDir = $targetDir ?? dirname($sourcePath);

        $renditions = [];
        foreach ($this->transforms() as $name => $transform) {
            $rendition = $this->attempt($manager, $sourcePath, $writeDir, $name, $transform);
            if ($rendition !== null) {
                $renditions[] = $rendition;
            }
        }

        return $renditions;
    }

    /**
     * @return array<string, Closure(ImageInterface): ?ImageInterface>
     */
    private function transforms(): array
    {
        $transforms = [];
        foreach (self::SCALED as $name => $maxWidth) {
            $transforms[$name] = static fn (ImageInterface $image): ?ImageInterface => $image->width() <= $maxWidth ? null : $image->scaleDown(width: $maxWidth);
        }
        $transforms['thumb'] = static fn (ImageInterface $image): ImageInterface => $image->coverDown(self::THUMB_SIZE, self::THUMB_SIZE);

        return $transforms;
    }

    /**
     * @param Closure(ImageInterface): ?ImageInterface $transform
     * @return array{name: string, filename: string, width: int, height: int, size: int}|null
     */
    private function attempt(ImageManager $manager, string $sourcePath, string $targetDir, string $name, Closure $transform): ?array
    {
        try {
            $image = $transform($manager->decodePath($sourcePath));

            return $image === null ? null : $this->write($image, $sourcePath, $targetDir, $name);
        } catch (Throwable $error) {
            Log::warning('Rendition generation failed', ['name' => $name, 'source' => $sourcePath, 'exception' => $error]);

            return null;
        }
    }

    /**
     * @return array{name: string, filename: string, width: int, height: int, size: int}
     */
    private function write(ImageInterface $image, string $sourcePath, string $targetDir, string $name): array
    {
        $stem = pathinfo($sourcePath, \PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($sourcePath, \PATHINFO_EXTENSION)) ?: 'jpg';
        $filename = sprintf('%s-%s.%s', $stem, $name, $extension);
        $path = $targetDir . '/' . $filename;
        $image->save($path);

        return [
            'name' => $name,
            'filename' => $filename,
            'width' => $image->width(),
            'height' => $image->height(),
            'size' => (int) filesize($path),
        ];
    }
}
