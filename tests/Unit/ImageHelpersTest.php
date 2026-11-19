<?php

namespace Tests\Unit;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageHelpersTest extends TestCase
{
    public function test_upload_image_returns_vercel_url_and_preserves_the_original_image(): void
    {
        config([
            'services.vercel_blob.token' => 'test-token',
            'services.vercel_blob.store_id' => 'store_test',
        ]);

        Http::fake([
            'https://blob.vercel-storage.com/*' => Http::response([
                'url' => 'https://example.public.blob.vercel-storage.com/brands/image.webp',
                'pathname' => 'brands/image.webp',
            ]),
        ]);

        $uploadedImage = UploadedFile::fake()->image('brand.png', 32, 24);
        $url = uploadImage('brands', $uploadedImage);

        $this->assertSame(
            'https://example.public.blob.vercel-storage.com/brands/image.webp',
            $url
        );

        Http::assertSent(function (ClientRequest $request) {
            $uploadedImage = getimagesizefromstring($request->body());

            return $request->method() === 'PUT'
                && str_contains($request->url(), 'pathname=brands%2F')
                && str_contains($request->url(), '.png')
                && $request->hasHeader('authorization', 'Bearer test-token')
                && $request->hasHeader('x-vercel-blob-access', 'public')
                && $request->hasHeader('x-content-type', 'image/png')
                && $uploadedImage !== false
                && $uploadedImage['mime'] === 'image/png'
                && $uploadedImage[0] === 32
                && $uploadedImage[1] === 24;
        });
    }

    public function test_get_image_preserves_urls_and_resolves_legacy_storage_paths(): void
    {
        $this->assertSame(
            'https://cdn.example/image.png',
            getImage('https://cdn.example/image.png')
        );
        $this->assertSame(
            asset('storage/products/old.png'),
            getImage('products/old.png')
        );
        $this->assertNull(getImage(null));
    }

    public function test_upload_image_requires_a_blob_token(): void
    {
        config(['services.vercel_blob.token' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Set BLOB_READ_WRITE_TOKEN');

        uploadImage('brands', UploadedFile::fake()->image('brand.png'));
    }
}
