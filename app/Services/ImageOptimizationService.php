<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageOptimizationService
{
    private const MAX_WIDTH = 1200;

    private const MAX_HEIGHT = 800;

    private const THUMB_WIDTH = 300;

    private const THUMB_HEIGHT = 200;

    private const QUALITY = 80; // Compression quality (1-100)

    private ImageManager $imageManager;

    public function __construct()
    {
        // Initialize ImageManager with GD driver
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Process and optimize an uploaded image
     * Compresses, resizes, and generates thumbnails
     *
     * @return string Path to the optimized image
     */
    public function processImage(UploadedFile $file, string $directory = 'uploads', bool $generateThumbnail = true): string
    {
        try {
            $filename = $this->generateFilename($file);
            $path = $directory.'/'.$filename;

            // Read and resize the main image
            $image = $this->imageManager->read($file->getPathname());

            // Resize if larger than max dimensions
            if ($image->width() > self::MAX_WIDTH || $image->height() > self::MAX_HEIGHT) {
                $image->scaleDown(
                    width: self::MAX_WIDTH,
                    height: self::MAX_HEIGHT
                );
            }

            // Encode as WebP and save
            $encoded = $image->toWebp(quality: self::QUALITY);
            Storage::disk('public')->put($path, (string) $encoded);

            // Generate thumbnail if requested
            if ($generateThumbnail) {
                $this->generateThumbnail($file, $directory, $filename);
            }

            return $path;
        } catch (\Exception $e) {
            \Log::error('Image processing failed: '.$e->getMessage());

            // Fallback: store original image if processing fails
            return $file->store($directory, 'public');
        }
    }

    /**
     * Generate a thumbnail for the image
     *
     * @return string Path to thumbnail
     */
    private function generateThumbnail(UploadedFile $file, string $directory, string $filename): string
    {
        try {
            $thumbFilename = str_replace('.webp', '_thumb.webp', $filename);
            $thumbPath = $directory.'/thumbnails/'.$thumbFilename;

            $image = $this->imageManager->read($file->getPathname());
            $image->scaleDown(
                width: self::THUMB_WIDTH,
                height: self::THUMB_HEIGHT
            );

            $encoded = $image->toWebp(quality: self::QUALITY);
            Storage::disk('public')->put($thumbPath, (string) $encoded);

            return $thumbPath;
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed: '.$e->getMessage());

            return '';
        }
    }

    /**
     * Generate a unique filename with timestamp
     */
    private function generateFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitized = preg_replace('/[^a-zA-Z0-9-_]/', '-', $originalName);
        $timestamp = (string) Str::uuid();

        return strtolower($sanitized.'-'.$timestamp.'.webp');
    }

    /**
     * Delete an image and its thumbnail
     */
    public function deleteImage(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Delete thumbnail if it exists
            $thumbPath = str_replace('/', '/thumbnails/', $path);
            $thumbPath = str_replace('.webp', '_thumb.webp', $thumbPath);
            if (Storage::disk('public')->exists($thumbPath)) {
                Storage::disk('public')->delete($thumbPath);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Image deletion failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Generate SEO-friendly alt text from filename
     */
    public function generateAltText(string $filename): string
    {
        return ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
    }
}
