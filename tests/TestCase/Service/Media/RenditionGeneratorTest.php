<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Media;

use App\Service\Media\RenditionGenerator;
use App\Test\DeletesDirectories;
use Cake\TestSuite\TestCase;

final class RenditionGeneratorTest extends TestCase
{
    use DeletesDirectories;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/cabinet-rendition-test-' . uniqid();
        mkdir($this->directory, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->directory);
        parent::tearDown();
    }

    public function testGeneratesDownscaledTiersAndSquareThumb(): void
    {
        $source = $this->writePng('photo.png', 1000, 600);

        $renditions = new RenditionGenerator()->generate($source);

        $names = array_column($renditions, 'name');
        sort($names);

        // 1000px wide: large (1920) is skipped as no upscaling; medium/small downscale.
        $this->assertSame(['medium', 'small', 'thumb'], $names);
    }

    public function testThumbIsCappedAndWritten(): void
    {
        $source = $this->writePng('wide.png', 800, 200);

        $renditions = new RenditionGenerator()->generate($source);
        $thumb = array_values(array_filter($renditions, static fn (array $r): bool => $r['name'] === 'thumb'))[0];

        $this->assertLessThanOrEqual(240, $thumb['width']);
        $this->assertLessThanOrEqual(240, $thumb['height']);
        $this->assertFileExists($this->directory . '/' . $thumb['filename']);
    }

    public function testSkipsRenditionsForImageSmallerThanEveryTier(): void
    {
        $source = $this->writePng('tiny.png', 120, 120);

        $renditions = new RenditionGenerator()->generate($source);

        // Only the thumb (a crop) is produced; no scaled tier applies.
        $this->assertSame(['thumb'], array_column($renditions, 'name'));
    }

    public function testProducesAllThreeScaledTiersAndThumbForWideImage(): void
    {
        $source = $this->writePng('hero.png', 2400, 1200);

        $names = array_column(new RenditionGenerator()->generate($source), 'name');
        sort($names);

        $this->assertSame(['large', 'medium', 'small', 'thumb'], $names);
    }

    public function testReturnsEmptyForNonImageSourceWithoutThrowing(): void
    {
        $path = $this->directory . '/not-really.png';
        file_put_contents($path, 'this is plain text, not an image');

        // The class contract is "never blocks the upload": a decode failure is
        // logged and swallowed, yielding no renditions rather than an exception.
        $this->assertSame([], new RenditionGenerator()->generate($path));
    }

    public function testReturnsEmptyForMissingSource(): void
    {
        $this->assertSame([], new RenditionGenerator()->generate($this->directory . '/missing.png'));
    }

    private function writePng(string $name, int $width, int $height): string
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        $color = imagecolorallocate($image, 120, 90, 60);
        $this->assertNotFalse($color);
        imagefill($image, 0, 0, $color);
        $path = $this->directory . '/' . $name;
        imagepng($image, $path);

        return $path;
    }
}
