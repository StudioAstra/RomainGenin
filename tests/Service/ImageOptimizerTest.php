<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImageOptimizerTest extends KernelTestCase
{
    /** @var list<string> */
    private array $created = [];

    private ImageOptimizer $optimizer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->optimizer = self::getContainer()->get(ImageOptimizer::class);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->created);
        parent::tearDown();
    }

    public function testLargeJpegIsResizedAndConvertedToWebp(): void
    {
        $name = $this->createImage('jpg', 3000, 2000);
        $before = filesize($this->optimizer->path($name));

        $result = $this->optimizer->optimize($name, ImageOptimizer::PROJECT_IMAGE_MAX);

        self::assertStringEndsWith('.webp', $result);
        self::assertFileDoesNotExist($this->optimizer->path($name), 'l\'original est supprimé');
        [$width, $height, $type] = getimagesize($this->optimizer->path($result));
        self::assertSame(\IMAGETYPE_WEBP, $type);
        self::assertSame([1600, 1067], [$width, $height]);
        self::assertLessThan($before, filesize($this->optimizer->path($result)));
    }

    public function testPngTransparencyIsKept(): void
    {
        $name = $this->createImage('png', 400, 300, transparent: true);

        $result = $this->optimizer->optimize($name, ImageOptimizer::PROJECT_IMAGE_MAX);

        $image = imagecreatefromwebp($this->optimizer->path($result));
        $alpha = (imagecolorat($image, 0, 0) >> 24) & 0x7F;
        self::assertSame([400, 300], [imagesx($image), imagesy($image)]);
        self::assertGreaterThan(100, $alpha, 'le coin reste transparent');
    }

    public function testOptimizedWebpIsLeftUntouched(): void
    {
        $name = $this->optimizer->optimize($this->createImage('jpg', 3000, 2000), ImageOptimizer::PROJECT_IMAGE_MAX);
        $mtime = filemtime($this->optimizer->path($name));
        clearstatcache();

        self::assertSame($name, $this->optimizer->optimize($name, ImageOptimizer::PROJECT_IMAGE_MAX));
        self::assertSame($mtime, filemtime($this->optimizer->path($name)));
    }

    public function testNonImageFileIsIgnored(): void
    {
        $name = 'test-'.bin2hex(random_bytes(4)).'.jpg';
        file_put_contents($this->track($name), 'pas une image');

        self::assertSame($name, $this->optimizer->optimize($name, ImageOptimizer::PROJECT_IMAGE_MAX));
    }

    private function createImage(string $extension, int $width, int $height, bool $transparent = false): string
    {
        $image = imagecreatetruecolor($width, $height);
        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
            imagefilledellipse($image, $width / 2, $height / 2, $width / 2, $height / 2, imagecolorallocate($image, 32, 120, 72));
        } else {
            // Bruit pour obtenir un fichier réaliste (un aplat se compresse trop bien)
            for ($i = 0; $i < 800; ++$i) {
                imagefilledrectangle($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), random_int(0, 0xFFFFFF));
            }
        }

        $name = 'test-'.bin2hex(random_bytes(4)).'.'.$extension;
        $path = $this->track($name);
        'png' === $extension ? imagepng($image, $path) : imagejpeg($image, $path, 95);

        return $name;
    }

    private function track(string $name): string
    {
        $path = $this->optimizer->path($name);
        $this->created[] = $path;
        $this->created[] = $this->optimizer->path(pathinfo($name, \PATHINFO_FILENAME).'.webp');

        return $path;
    }
}
