<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductImageProcessor
{
    private ImageManager $images;

    public function __construct()
    {
        // Keep it explicit: GD is the most portable on Windows.
        $this->images = new ImageManager(new Driver());
    }

    public function storeUploaded(UploadedFile $file, string $dir = 'products', int $maxSize = 1600, int $quality = 80): string
    {
        $filename = Str::uuid()->toString().'.webp';
        $relativePath = trim($dir, '/').'/'.$filename;

        $this->encodeToWebpAndSave($file->getPathname(), $relativePath, $maxSize, $quality);

        return $relativePath;
    }

    /**
     * Re-encode an existing public disk image to WebP + resized.
     * Returns the (possibly updated) relative path.
     */
    public function optimizeExisting(string $relativePath, string $dir = 'products', int $maxSize = 1600, int $quality = 80): string
    {
        $relativePath = ltrim($relativePath, '/');

        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            return $relativePath;
        }

        $sourceAbsolute = $disk->path($relativePath);

        $base = pathinfo($relativePath, PATHINFO_FILENAME);
        $targetRelative = trim($dir, '/').'/'.$base.'.webp';

        // If the image is not in the expected folder, keep it under products/ for consistency.
        if (! str_starts_with($relativePath, trim($dir, '/').'/')) {
            $targetRelative = trim($dir, '/').'/'.Str::uuid()->toString().'.webp';
        }

        $this->encodeToWebpAndSave($sourceAbsolute, $targetRelative, $maxSize, $quality);

        if ($targetRelative !== $relativePath) {
            $disk->delete($relativePath);
        }

        return $targetRelative;
    }

    private function encodeToWebpAndSave(string $sourceAbsolutePath, string $targetRelativePath, int $maxSize, int $quality): void
    {
        $disk = Storage::disk('public');
        $targetAbsolute = $disk->path($targetRelativePath);

        $image = $this->images->read($sourceAbsolutePath);

        // Auto-size: constrain within maxSize x maxSize, keep aspect ratio.
        $image->scaleDown($maxSize, $maxSize);

        // WebP + compression.
        $image->toWebp($quality)->save($targetAbsolute);
    }
}

