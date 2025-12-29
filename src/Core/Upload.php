<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple file upload handler.
 * 
 * Handles file uploads securely with validation.
 * 
 * Usage:
 *   $file = Upload::file('avatar');
 *   if ($file->isValid()) {
 *       $path = $file->store('uploads/avatars');
 *   }
 */
final class Upload
{
    private array $file;
    private array $errors = [];
    
    /** @var array<string> Allowed MIME types (empty = allow all) */
    private array $allowedTypes = [];
    
    /** @var int Max file size in bytes (0 = no limit) */
    private int $maxSize = 0;
    
    /** @var string Upload directory base path */
    private static string $basePath = '';

    /**
     * Create upload instance from $_FILES key.
     */
    public function __construct(string $key)
    {
        $this->file = $_FILES[$key] ?? [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];
    }

    /**
     * Static factory.
     */
    public static function file(string $key): self
    {
        return new self($key);
    }

    /**
     * Set the base upload path.
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/\\');
    }

    /**
     * Get base path (defaults to storage/uploads).
     */
    private static function getBasePath(): string
    {
        if (self::$basePath === '') {
            self::$basePath = (defined('BASE_PATH') ? BASE_PATH : getcwd()) . '/storage/uploads';
        }
        return self::$basePath;
    }

    /**
     * Restrict allowed MIME types.
     * 
     * @param array<string> $types e.g., ['image/jpeg', 'image/png']
     */
    public function allowTypes(array $types): self
    {
        $this->allowedTypes = $types;
        return $this;
    }

    /**
     * Restrict to image types only.
     */
    public function allowImages(): self
    {
        return $this->allowTypes([
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ]);
    }

    /**
     * Restrict to document types.
     */
    public function allowDocuments(): self
    {
        return $this->allowTypes([
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ]);
    }

    /**
     * Set maximum file size.
     * 
     * @param int $bytes Max size in bytes
     */
    public function maxSize(int $bytes): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * Check if file was uploaded and is valid.
     */
    public function isValid(): bool
    {
        $this->errors = [];

        // Check for upload errors
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($this->file['error']);
            return false;
        }

        // Check if file was actually uploaded
        if (!is_uploaded_file($this->file['tmp_name'])) {
            $this->errors[] = 'File was not uploaded via HTTP POST';
            return false;
        }

        // Check file type
        if (!empty($this->allowedTypes)) {
            $mimeType = $this->getMimeType();
            if (!in_array($mimeType, $this->allowedTypes, true)) {
                $this->errors[] = "File type '{$mimeType}' is not allowed";
                return false;
            }
        }

        // Check file size
        if ($this->maxSize > 0 && $this->file['size'] > $this->maxSize) {
            $maxMB = round($this->maxSize / 1024 / 1024, 2);
            $this->errors[] = "File exceeds maximum size of {$maxMB}MB";
            return false;
        }

        return true;
    }

    /**
     * Check if a file was submitted.
     */
    public function exists(): bool
    {
        return $this->file['error'] !== UPLOAD_ERR_NO_FILE 
            && $this->file['tmp_name'] !== '';
    }

    /**
     * Get validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message.
     */
    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Store the file to a directory.
     * 
     * @param string $directory Relative to base path (e.g., 'avatars')
     * @param string|null $filename Custom filename (null = generate unique)
     * @return string|false Stored path relative to base, or false on failure
     */
    public function store(string $directory = '', ?string $filename = null): string|false
    {
        if (!$this->isValid()) {
            return false;
        }

        $basePath = self::getBasePath();
        $targetDir = $basePath . ($directory ? '/' . trim($directory, '/') : '');

        // Create directory if needed
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $this->errors[] = 'Failed to create upload directory';
                return false;
            }
        }

        // Generate filename
        if ($filename === null) {
            $extension = $this->getExtension();
            $filename = $this->generateFilename($extension);
        }

        $targetPath = $targetDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($this->file['tmp_name'], $targetPath)) {
            $this->errors[] = 'Failed to move uploaded file';
            return false;
        }

        // Return relative path
        $relativePath = ($directory ? trim($directory, '/') . '/' : '') . $filename;
        return $relativePath;
    }

    /**
     * Get original filename.
     */
    public function getOriginalName(): string
    {
        return $this->file['name'];
    }

    /**
     * Get file extension.
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
    }

    /**
     * Get actual MIME type (from file content, not client).
     */
    public function getMimeType(): string
    {
        if (!$this->exists()) {
            return '';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->file['tmp_name']);
        finfo_close($finfo);
        
        return $mimeType ?: '';
    }

    /**
     * Get file size in bytes.
     */
    public function getSize(): int
    {
        return $this->file['size'];
    }

    /**
     * Get human-readable file size.
     */
    public function getSizeFormatted(): string
    {
        $bytes = $this->file['size'];
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Generate unique filename.
     */
    private function generateFilename(string $extension): string
    {
        $unique = bin2hex(random_bytes(16));
        return $extension ? "{$unique}.{$extension}" : $unique;
    }

    /**
     * Get error message for upload error code.
     */
    private function getUploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE form directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by PHP extension',
            default => 'Unknown upload error',
        };
    }
}
