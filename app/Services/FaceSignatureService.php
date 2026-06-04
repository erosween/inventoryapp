<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FaceSignatureService
{
    private const GRID_SIZE = 12;

    public function storeImage(string $image, string $folder, string $prefix): ?string
    {
        $stored = $this->storeCompressedImage($image, $folder, $prefix, 960, 84, null);

        return $stored['path'] ?? null;
    }

    public function storeCompressedImage(string $image, string $folder, string $prefix, int $maxSide = 960, int $quality = 82, ?int $thumbnailSide = 240): ?array
    {
        $binary = $this->binaryFromDataUrl($image);
        if (!$binary) {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return null;
        }

        $safePrefix = preg_replace('/[^A-Za-z0-9_-]/', '_', $prefix);
        $filename = $safePrefix . '-' . now()->format('YmdHis') . '-' . substr(sha1($binary), 0, 8);
        $path = trim($folder, '/') . '/' . $filename . '.jpg';

        $main = $this->resizedImage($source, $maxSide);
        Storage::disk('public')->put($path, $this->jpegBinary($main, $quality));
        imagedestroy($main);

        $thumbnailPath = null;
        if ($thumbnailSide) {
            $thumbnailPath = trim($folder, '/') . '/thumbs/' . $filename . '.jpg';
            $thumbnail = $this->resizedImage($source, $thumbnailSide);
            Storage::disk('public')->put($thumbnailPath, $this->jpegBinary($thumbnail, 68));
            imagedestroy($thumbnail);
        }

        imagedestroy($source);

        return [
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    public function signatureFromDataUrl(?string $image): ?array
    {
        if (!$image) {
            return null;
        }

        $binary = $this->binaryFromDataUrl($image);
        if (!$binary) {
            return null;
        }

        return $this->signatureFromBinary($binary);
    }

    public function hash(array $signature): string
    {
        return hash('sha256', json_encode($signature));
    }

    public function similarity(array $known, array $probe): int
    {
        if (count($known) !== count($probe) || count($known) === 0) {
            return 0;
        }

        $dot = 0.0;
        $knownNorm = 0.0;
        $probeNorm = 0.0;

        foreach ($known as $index => $knownValue) {
            $probeValue = (float) $probe[$index];
            $knownValue = (float) $knownValue;
            $dot += $knownValue * $probeValue;
            $knownNorm += $knownValue ** 2;
            $probeNorm += $probeValue ** 2;
        }

        if ($knownNorm <= 0 || $probeNorm <= 0) {
            return 0;
        }

        $cosine = $dot / (sqrt($knownNorm) * sqrt($probeNorm));

        return max(0, min(100, (int) round((($cosine + 1) / 2) * 100)));
    }

    private function binaryFromDataUrl(string $image): ?string
    {
        if (!str_starts_with($image, 'data:image/')) {
            return null;
        }

        $parts = explode(',', $image, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $binary = base64_decode($parts[1], true);

        return $binary === false ? null : $binary;
    }

    private function signatureFromBinary(string $binary): ?array
    {
        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = (int) floor(min($width, $height) * 0.72);
        $x = (int) floor(($width - $side) / 2);
        $y = (int) floor(($height - $side) / 2);

        $thumb = imagecreatetruecolor(self::GRID_SIZE, self::GRID_SIZE);
        imagecopyresampled($thumb, $source, 0, 0, $x, $y, self::GRID_SIZE, self::GRID_SIZE, $side, $side);

        $values = [];
        for ($py = 0; $py < self::GRID_SIZE; $py++) {
            for ($px = 0; $px < self::GRID_SIZE; $px++) {
                $rgb = imagecolorat($thumb, $px, $py);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $values[] = (($red * 0.299) + ($green * 0.587) + ($blue * 0.114)) / 255;
            }
        }

        imagedestroy($thumb);
        imagedestroy($source);

        $average = array_sum($values) / count($values);
        $variance = sqrt(array_sum(array_map(fn ($value) => ($value - $average) ** 2, $values)) / count($values));
        $variance = $variance > 0 ? $variance : 1;

        return array_map(fn ($value) => round(($value - $average) / $variance, 4), $values);
    }

    private function resizedImage($source, int $maxSide)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxSide / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($target, 255, 255, 255);

        imagefill($target, 0, 0, $white);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    private function jpegBinary($image, int $quality): string
    {
        ob_start();
        imageinterlace($image, true);
        imagejpeg($image, null, max(40, min(92, $quality)));

        return (string) ob_get_clean();
    }
}
