<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OptimizedImageStorage
{
    private const MAX_EDGE = 1600;

    private const THUMBNAIL_EDGE = 360;

    private const FULL_QUALITY = 72;

    private const THUMBNAIL_QUALITY = 60;

    /**
     * Store a display-sized WebP plus a lightweight thumbnail.
     *
     * @return array{path: string, thumbnail_path: string}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $name = Str::uuid().'.webp';
        $path = trim($directory, '/').'/'.$name;
        $thumbnailPath = self::thumbnailPathFor($path);

        [$fullImage, $thumbnail] = extension_loaded('imagick') && \Imagick::queryFormats('WEBP') !== []
            ? $this->encodeWithImagick($file)
            : $this->encodeWithGd($file);

        $disk = Storage::disk('public');

        try {
            if (! $disk->put($path, $fullImage) || ! $disk->put($thumbnailPath, $thumbnail)) {
                throw new RuntimeException('Foto tidak dapat disimpan ke storage.');
            }
        } catch (\Throwable $exception) {
            $disk->delete([$path, $thumbnailPath]);
            throw $exception;
        }

        return ['path' => $path, 'thumbnail_path' => $thumbnailPath];
    }

    public static function thumbnailPathFor(string $path): string
    {
        $directory = dirname($path);

        return ($directory === '.' ? '' : $directory.'/').'thumbnails/'.basename($path);
    }

    public function isLikelyBlack(UploadedFile $file): bool
    {
        $bytes = file_get_contents($file->getRealPath());
        if ($bytes === false) {
            return true;
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $dark = 0;
        $samples = 0;
        $stepX = max(1, (int) floor($width / 24));
        $stepY = max(1, (int) floor($height / 24));
        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $luma = (0.299 * $rgb['red']) + (0.587 * $rgb['green']) + (0.114 * $rgb['blue']);
                $samples++;
                if ($luma < 10) $dark++;
            }
        }
        imagedestroy($image);

        return $samples > 0 && ($dark / $samples) >= 0.92;
    }

    /** @return array{string, string} */
    private function encodeWithImagick(UploadedFile $file): array
    {
        $image = new \Imagick($file->getRealPath());
        $image->setIteratorIndex(0);

        if (method_exists($image, 'autoOrientImage')) {
            $image->autoOrientImage();
        }

        $image->stripImage();
        $this->resizeImagick($image, self::MAX_EDGE);

        $thumbnail = clone $image;
        $this->resizeImagick($thumbnail, self::THUMBNAIL_EDGE);

        $fullBytes = $this->imagickWebp($image, self::FULL_QUALITY);
        $thumbnailBytes = $this->imagickWebp($thumbnail, self::THUMBNAIL_QUALITY);

        $image->clear();
        $thumbnail->clear();

        return [$fullBytes, $thumbnailBytes];
    }

    private function resizeImagick(\Imagick $image, int $maxEdge): void
    {
        if ($image->getImageWidth() <= $maxEdge && $image->getImageHeight() <= $maxEdge) {
            return;
        }

        $image->thumbnailImage($maxEdge, $maxEdge, true);
    }

    private function imagickWebp(\Imagick $image, int $quality): string
    {
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($quality);
        $image->setOption('webp:method', '6');

        return $image->getImageBlob();
    }

    /** @return array{string, string} */
    private function encodeWithGd(UploadedFile $file): array
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Server belum mendukung encoder WebP (GD/Imagick).');
        }

        $sourceBytes = file_get_contents($file->getRealPath());
        $source = $sourceBytes === false ? false : imagecreatefromstring($sourceBytes);

        if ($source === false) {
            throw new RuntimeException('Format foto tidak dapat diproses.');
        }

        $full = $this->resizeGd($source, self::MAX_EDGE);
        $thumbnail = $this->resizeGd($source, self::THUMBNAIL_EDGE);
        imagedestroy($source);

        $fullBytes = $this->gdWebp($full, self::FULL_QUALITY);
        $thumbnailBytes = $this->gdWebp($thumbnail, self::THUMBNAIL_QUALITY);
        imagedestroy($full);
        imagedestroy($thumbnail);

        return [$fullBytes, $thumbnailBytes];
    }

    /** @param \GdImage $source */
    private function resizeGd($source, int $maxEdge)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxEdge / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    /** @param \GdImage $image */
    private function gdWebp($image, int $quality): string
    {
        ob_start();
        $encoded = imagewebp($image, null, $quality);
        $bytes = ob_get_clean();

        if (! $encoded || ! is_string($bytes)) {
            throw new RuntimeException('Foto gagal dikompresi ke WebP.');
        }

        return $bytes;
    }
}
