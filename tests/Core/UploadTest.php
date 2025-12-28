<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Upload;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/intent_upload_test_' . uniqid();
        Upload::setBasePath($this->testDir);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            $this->deleteDirectory($this->testDir);
        }
        Upload::setBasePath('');
    }

    private function deleteDirectory(string $dir): void
    {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : unlink($file);
        }
        rmdir($dir);
    }

    public function testFileFactoryReturnsInstance(): void
    {
        $upload = Upload::file('avatar');
        $this->assertInstanceOf(Upload::class, $upload);
    }

    public function testExistsReturnsFalseWhenNoFile(): void
    {
        $upload = Upload::file('nonexistent');
        $this->assertFalse($upload->exists());
    }

    public function testIsValidFailsWhenNoFile(): void
    {
        $upload = Upload::file('nonexistent');
        $this->assertFalse($upload->isValid());
        $this->assertNotEmpty($upload->errors());
    }

    public function testAllowTypesReturnsFluentInstance(): void
    {
        $upload = Upload::file('test');
        $result = $upload->allowTypes(['image/jpeg']);
        $this->assertSame($upload, $result);
    }

    public function testAllowImagesReturnsFluentInstance(): void
    {
        $upload = Upload::file('test');
        $result = $upload->allowImages();
        $this->assertSame($upload, $result);
    }

    public function testAllowDocumentsReturnsFluentInstance(): void
    {
        $upload = Upload::file('test');
        $result = $upload->allowDocuments();
        $this->assertSame($upload, $result);
    }

    public function testMaxSizeReturnsFluentInstance(): void
    {
        $upload = Upload::file('test');
        $result = $upload->maxSize(1024);
        $this->assertSame($upload, $result);
    }

    public function testGetOriginalName(): void
    {
        $_FILES['test'] = [
            'name' => 'my_photo.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $upload = Upload::file('test');
        $this->assertEquals('my_photo.jpg', $upload->getOriginalName());

        unset($_FILES['test']);
    }

    public function testGetExtension(): void
    {
        $_FILES['test'] = [
            'name' => 'my_photo.JPG',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $upload = Upload::file('test');
        $this->assertEquals('jpg', $upload->getExtension());

        unset($_FILES['test']);
    }

    public function testGetSize(): void
    {
        $_FILES['test'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 12345,
        ];

        $upload = Upload::file('test');
        $this->assertEquals(12345, $upload->getSize());

        unset($_FILES['test']);
    }

    public function testGetSizeFormatted(): void
    {
        $_FILES['test'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 1048576, // 1 MB
        ];

        $upload = Upload::file('test');
        $this->assertEquals('1 MB', $upload->getSizeFormatted());

        unset($_FILES['test']);
    }

    public function testErrorMessagesForUploadErrors(): void
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE,
            UPLOAD_ERR_PARTIAL,
            UPLOAD_ERR_NO_FILE,
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE,
            UPLOAD_ERR_EXTENSION,
        ];

        foreach ($errors as $errorCode) {
            $_FILES['test'] = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '',
                'error' => $errorCode,
                'size' => 0,
            ];

            $upload = Upload::file('test');
            $this->assertFalse($upload->isValid());
            $this->assertNotEmpty($upload->firstError());
        }

        unset($_FILES['test']);
    }

    public function testFirstErrorReturnsNullWhenNoErrors(): void
    {
        $upload = Upload::file('nonexistent');
        // Before validation, no errors
        $this->assertNull($upload->firstError());
    }
}
