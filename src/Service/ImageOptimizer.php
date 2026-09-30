<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Réduit le poids des images envoyées : redimensionnement, orientation EXIF, conversion WebP.
 *
 * Idempotent : un WebP déjà dans les limites (dimensions et poids) est laissé tel quel,
 * on peut donc relancer l'optimisation sans dégrader les images.
 */
class ImageOptimizer
{
    public const UPLOAD_DIR = 'public/uploads/images';

    /** Photo de profil : affichée en 420 px de large max, x2 pour les écrans haute densité. */
    public const PROFILE_PHOTO_MAX = 1000;

    /** Visuels de projets : cartes et grande carte perso. */
    public const PROJECT_IMAGE_MAX = 1600;

    private const QUALITY = 80;

    /** Au-delà, un WebP déjà aux bonnes dimensions est quand même ré-encodé. */
    private const MAX_WEBP_BYTES = 350_000;

    private readonly Filesystem $filesystem;

    private int $lastOriginalSize = 0;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Optimise un fichier du dossier d'upload et retourne son nouveau nom
     * (inchangé si rien n'a été fait ou en cas d'échec).
     */
    public function optimize(string $filename, int $maxSize): string
    {
        $path = $this->path($filename);

        if (!is_file($path) || !$this->needsOptimization($path, $maxSize)) {
            return $filename;
        }

        try {
            $target = $this->uniqueWebpName($filename);
            $this->convert($path, $this->path($target), $maxSize);
        } catch (\Throwable $e) {
            $this->logger->warning('Optimisation de l\'image impossible : {file} ({error})', ['file' => $filename, 'error' => $e->getMessage()]);

            return $filename;
        }

        if ($target !== $filename) {
            $this->filesystem->remove($path);
        }

        $this->logger->info('Image optimisée : {from} ({before} o) → {to} ({after} o)', [
            'from' => $filename,
            'to' => $target,
            'before' => $this->lastOriginalSize,
            'after' => filesize($this->path($target)),
        ]);

        return $target;
    }

    public function path(string $filename): string
    {
        return $this->projectDir.'/'.self::UPLOAD_DIR.'/'.$filename;
    }

    private function needsOptimization(string $path, int $maxSize): bool
    {
        $info = @getimagesize($path);
        if (false === $info) {
            return false; // pas une image lisible (SVG, fichier corrompu…) : on n'y touche pas
        }

        [$width, $height, $type] = $info;

        if (\IMAGETYPE_GIF === $type && $this->isAnimatedGif($path)) {
            return false; // GD perdrait l'animation
        }

        return \IMAGETYPE_WEBP !== $type
            || max($width, $height) > $maxSize
            || filesize($path) > self::MAX_WEBP_BYTES;
    }

    private function convert(string $source, string $target, int $maxSize): void
    {
        $this->lastOriginalSize = (int) filesize($source);

        $image = @imagecreatefromstring((string) file_get_contents($source));
        if (false === $image) {
            throw new \RuntimeException('format non pris en charge par GD');
        }

        $image = $this->applyExifOrientation($image, $source);

        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, $maxSize / max($width, $height));

        if ($ratio < 1) {
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
        } else {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        // Écriture dans un fichier temporaire puis renommage : jamais d'image à moitié écrite
        $tmp = $target.'.tmp';
        if (!imagewebp($image, $tmp, self::QUALITY)) {
            @unlink($tmp);
            throw new \RuntimeException('encodage WebP impossible');
        }

        // Un WebP déjà léger peut grossir au ré-encodage : on garde alors l'original
        if ($source === $target && filesize($tmp) >= $this->lastOriginalSize) {
            @unlink($tmp);

            return;
        }

        $this->filesystem->rename($tmp, $target, true);
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!\function_exists('exif_read_data') || \IMAGETYPE_JPEG !== @exif_imagetype($path)) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return false === $rotated ? $image : $rotated;
    }

    private function isAnimatedGif(string $path): bool
    {
        $content = (string) file_get_contents($path);

        return preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $content) > 1;
    }

    private function uniqueWebpName(string $filename): string
    {
        $base = pathinfo($filename, \PATHINFO_FILENAME);
        $candidate = $base.'.webp';

        if ($candidate === $filename || !is_file($this->path($candidate))) {
            return $candidate;
        }

        return $base.'-'.bin2hex(random_bytes(4)).'.webp';
    }
}
