<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must not depend on a built Vite manifest (public/build).
        $this->withoutVite();
    }

    /**
     * A real (tiny) image upload built from a committed fixture, so tests do
     * not need the GD extension (UploadedFile::fake()->image() does).
     */
    protected function fakeImage(string $name = 'image.jpg'): UploadedFile
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $fixture = __DIR__.'/Fixtures/images/sample.'.(in_array($extension, ['png', 'webp'], true) ? $extension : 'jpg');

        $copy = tempnam(sys_get_temp_dir(), 'img');
        copy($fixture, $copy);

        return new UploadedFile($copy, $name, mime_content_type($copy), null, true);
    }
}
