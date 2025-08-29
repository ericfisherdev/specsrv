<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\File;
use App\Exception\BusinessLogicException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private readonly string $uploadsDirectory,
        private readonly int $maxUploadSize,
        private readonly array $allowedFileTypes,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger
    ) {
        // Ensure uploads directory exists
        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0755, true);
        }
    }

    public function uploadFile(
        UploadedFile $uploadedFile,
        string $entityType,
        int $entityId,
        ?string $customName = null
    ): File {
        $this->validateFile($uploadedFile);
        
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();
        
        // Use custom name if provided, otherwise use original filename
        $baseFilename = $customName ?? $originalFilename;
        $safeFilename = $this->slugger->slug($baseFilename)->lower();
        $filename = $safeFilename . '-' . uniqid() . '.' . $extension;
        
        // Create entity-specific subdirectory
        $entityDirectory = $this->uploadsDirectory . '/' . $entityType . '/' . $entityId;
        if (!is_dir($entityDirectory)) {
            mkdir($entityDirectory, 0755, true);
        }
        
        $filePath = $entityDirectory . '/' . $filename;
        
        // Move the file
        $uploadedFile->move($entityDirectory, $filename);
        
        // Create File entity
        $file = new File();
        $file->setFilename($uploadedFile->getClientOriginalName());
        $file->setPath($this->getRelativePath($filePath));
        $file->setType($uploadedFile->getMimeType() ?? 'application/octet-stream');
        $file->setEntityType($entityType);
        $file->setEntityId($entityId);
        
        $this->entityManager->persist($file);
        $this->entityManager->flush();
        
        return $file;
    }

    public function deleteFile(File $file): void
    {
        $fullPath = $this->uploadsDirectory . '/' . ltrim($file->getPath(), '/');
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
            
            // Clean up empty directories
            $directory = dirname($fullPath);
            if ($this->isDirectoryEmpty($directory) && $directory !== $this->uploadsDirectory) {
                rmdir($directory);
                
                // Also clean up parent if empty
                $parentDirectory = dirname($directory);
                if ($this->isDirectoryEmpty($parentDirectory) && $parentDirectory !== $this->uploadsDirectory) {
                    rmdir($parentDirectory);
                }
            }
        }
        
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }

    public function getFileContent(File $file): string
    {
        $fullPath = $this->uploadsDirectory . '/' . ltrim($file->getPath(), '/');
        
        if (!file_exists($fullPath)) {
            throw new BusinessLogicException('File not found on disk');
        }
        
        return file_get_contents($fullPath);
    }

    public function getFileSize(File $file): int
    {
        $fullPath = $this->uploadsDirectory . '/' . ltrim($file->getPath(), '/');
        
        if (!file_exists($fullPath)) {
            throw new BusinessLogicException('File not found on disk');
        }
        
        return filesize($fullPath);
    }

    public function fileExists(File $file): bool
    {
        $fullPath = $this->uploadsDirectory . '/' . ltrim($file->getPath(), '/');
        return file_exists($fullPath);
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new BusinessLogicException('Invalid file upload');
        }

        if ($file->getSize() > $this->maxUploadSize) {
            throw new BusinessLogicException(
                sprintf('File size (%d bytes) exceeds maximum allowed size (%d bytes)', 
                    $file->getSize(), 
                    $this->maxUploadSize
                )
            );
        }

        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $this->allowedFileTypes, true)) {
            throw new BusinessLogicException(
                sprintf('File type "%s" is not allowed. Allowed types: %s', 
                    $extension, 
                    implode(', ', $this->allowedFileTypes)
                )
            );
        }

        // Check for potentially dangerous files
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$/i', $filename)) {
            throw new BusinessLogicException('Executable files are not allowed');
        }
    }

    private function getRelativePath(string $fullPath): string
    {
        return str_replace($this->uploadsDirectory . '/', '', $fullPath);
    }

    private function isDirectoryEmpty(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true;
        }
        
        $files = scandir($directory);
        return count($files) <= 2; // Only . and .. entries
    }

    public function getUploadLimits(): array
    {
        return [
            'maxUploadSize' => $this->maxUploadSize,
            'allowedFileTypes' => $this->allowedFileTypes,
            'uploadsDirectory' => $this->uploadsDirectory
        ];
    }
}