<?php

namespace Tests\Feature\Support;

use App\Support\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploaderTest extends TestCase
{
    private ImageUploader $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->uploader = new ImageUploader;
    }

    public function test_store_saves_file_in_directory(): void
    {
        $path = $this->uploader->store($this->fakeImage('a.jpg'), 'products');

        $this->assertStringStartsWith('products/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replace_with_new_file_deletes_the_old_one(): void
    {
        $old = $this->uploader->store($this->fakeImage('old.jpg'), 'products');

        $new = $this->uploader->replace($this->fakeImage('new.png'), $old, 'products');

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_replace_without_file_keeps_the_old_one(): void
    {
        $old = $this->uploader->store($this->fakeImage('old.jpg'), 'products');

        $this->assertSame($old, $this->uploader->replace(null, $old, 'products'));
        Storage::disk('public')->assertExists($old);
    }

    public function test_replace_with_remove_flag_deletes_and_returns_null(): void
    {
        $old = $this->uploader->store($this->fakeImage('old.jpg'), 'products');

        $this->assertNull($this->uploader->replace(null, $old, 'products', remove: true));
        Storage::disk('public')->assertMissing($old);
    }

    public function test_delete_ignores_null_paths(): void
    {
        $this->uploader->delete(null, null);

        $this->assertTrue(true);
    }

    public function test_validation_rules_restrict_type_and_size(): void
    {
        $rules = ['image' => ImageUploader::rules()];

        $this->assertTrue(validator(['image' => $this->fakeImage('ok.webp')], $rules)->passes());
        $this->assertTrue(validator(['image' => null], $rules)->passes());
        $this->assertFalse(validator(['image' => UploadedFile::fake()->create('big.jpg', 3000, 'image/jpeg')], $rules)->passes());
        $this->assertFalse(validator(['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], $rules)->passes());
        $this->assertFalse(validator(['image' => UploadedFile::fake()->create('anim.gif', 10, 'image/gif')], $rules)->passes());
    }
}
