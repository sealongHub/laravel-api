<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('vercelBlobHeaders')) {
    function vercelBlobHeaders(): array
    {
        $token = config('services.vercel_blob.token');

        if (! $token) {
            throw new RuntimeException('Vercel Blob is not configured. Set BLOB_READ_WRITE_TOKEN.');
        }

        $headers = [
            'authorization' => 'Bearer ' . $token,
            'x-api-version' => '13',
            'x-api-blob-request-id' => (string) Str::uuid(),
            'x-api-blob-request-attempt' => '0',
        ];

        if ($storeId = config('services.vercel_blob.store_id')) {
            $headers['x-vercel-blob-store-id'] = $storeId;
        }

        return $headers;
    }
}

if (! function_exists('uploadImage')) {
    function compressImageToWebp(UploadedFile $image): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new RuntimeException('GD with WebP support is required to upload images.');
        }

        $sourceContents = file_get_contents($image->getRealPath());
        if ($sourceContents === false) {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        $source = @imagecreatefromstring($sourceContents);
        if ($source === false) {
            throw new RuntimeException('The uploaded image could not be converted to WebP.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'webp-');
        if ($temporaryPath === false) {
            imagedestroy($source);
            throw new RuntimeException('A temporary file could not be created for image conversion.');
        }

        try {
            if (function_exists('imagepalettetotruecolor')) {
                imagepalettetotruecolor($source);
            }

            imagealphablending($source, false);
            imagesavealpha($source, true);

            if (! imagewebp($source, $temporaryPath, 80)) {
                throw new RuntimeException('The uploaded image could not be encoded as WebP.');
            }

            $webpContents = file_get_contents($temporaryPath);
            if ($webpContents === false) {
                throw new RuntimeException('The compressed WebP image could not be read.');
            }

            return $webpContents;
        } finally {
            imagedestroy($source);
            unlink($temporaryPath);
        }
    }

    function uploadImage(string $folderName, UploadedFile $image): string
    {
        $folderName = trim($folderName, '/');

        if ($folderName === '' || ! preg_match('~^[A-Za-z0-9._/-]+$~', $folderName)
            || in_array('..', explode('/', $folderName), true)) {
            throw new RuntimeException('The image folder name is invalid.');
        }

        $headers = vercelBlobHeaders();
        $pathname = $folderName . '/' . Str::uuid() . '.webp';
        $contents = compressImageToWebp($image);

        $response = Http::timeout(30)
            ->withHeaders(array_merge($headers, [
                'x-vercel-blob-access' => 'public',
                'x-add-random-suffix' => '0',
                'x-content-type' => 'image/webp',
                'x-content-length' => (string) strlen($contents),
            ]))
            ->withBody($contents, 'image/webp')
            ->put('https://blob.vercel-storage.com/?' . http_build_query([
                'pathname' => $pathname,
            ]));

        $response->throw();

        $url = $response->json('url');
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Vercel Blob returned an invalid image URL.');
        }

        return getImage($url);
    }
}

if (! function_exists('getImage')) {
    function getImage(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return asset('storage/' . ltrim($image, '/'));
    }
}

if (! function_exists('deleteImage')) {
    function deleteImage(?string $image): void
    {
        if (! $image) {
            return;
        }

        if (! filter_var($image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete(ltrim($image, '/'));
            return;
        }

        $host = parse_url($image, PHP_URL_HOST);
        if (! is_string($host) || ! str_ends_with($host, '.blob.vercel-storage.com')) {
            return;
        }

        $response = Http::timeout(30)
            ->withHeaders(vercelBlobHeaders())
            ->post('https://blob.vercel-storage.com/delete', [
                'urls' => [$image],
            ]);

        $response->throw();
    }
}
