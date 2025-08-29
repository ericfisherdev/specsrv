<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FileRepository;
use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/files')]
class FileController extends AbstractController
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private FileRepository $fileRepository
    ) {
    }

    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/zip',
        'application/x-zip-compressed',
        'text/x-php',
        'text/x-python',
        'text/javascript',
        'application/json',
        'text/css',
        'text/html',
        'application/xml',
        'text/xml'
    ];
    
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    #[Route('/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $files = $request->files->get('files', []);
        $projectId = $request->request->get('project_id');
        $taskId = $request->request->get('task_id');
        
        if (!is_array($files)) {
            $files = [$files];
        }
        
        // Determine entity type and ID
        $entityType = null;
        $entityId = null;
        
        if ($taskId) {
            $entityType = 'task';
            $entityId = (int) $taskId;
        } elseif ($projectId) {
            $entityType = 'project';
            $entityId = (int) $projectId;
        } else {
            return new JsonResponse([
                'success' => false,
                'errors' => [['filename' => 'Upload', 'error' => 'No task or project ID provided']]
            ], 400);
        }
        
        $uploadedFiles = [];
        $errors = [];
        
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $validation['error']
                ];
                continue;
            }
            
            try {
                // Use the FileUploadService to properly handle the upload
                $uploadedFile = $this->fileUploadService->uploadFile(
                    $file,
                    $entityType,
                    $entityId
                );
                
                $uploadedFiles[] = [
                    'id' => $uploadedFile->getId(),
                    'original_name' => $uploadedFile->getFilename(),
                    'filename' => $uploadedFile->getFilename(),
                    'size' => $this->fileUploadService->getFileSize($uploadedFile),
                    'mime_type' => $uploadedFile->getType()
                ];
                
            } catch (\Exception $e) {
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => 'Failed to upload file: ' . $e->getMessage()
                ];
            }
        }
        
        return new JsonResponse([
            'success' => count($uploadedFiles) > 0,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors
        ]);
    }
    
    #[Route('/download/{id}', name: 'app_file_download', methods: ['GET'])]
    public function download(int $id): Response
    {
        $file = $this->fileRepository->find($id);
        
        if (!$file) {
            throw $this->createNotFoundException('File not found');
        }
        
        // Check if user has permission to access this file
        // TODO: Add proper permission checking based on entity ownership
        
        if (!$this->fileUploadService->fileExists($file)) {
            throw $this->createNotFoundException('File not found on disk');
        }
        
        $fileContent = $this->fileUploadService->getFileContent($file);
        $fileSize = $this->fileUploadService->getFileSize($file);
        
        $response = new Response();
        $response->setContent($fileContent);
        $response->headers->set('Content-Type', $file->getType() ?: 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->getFilename() . '"');
        $response->headers->set('Content-Length', (string) $fileSize);
        
        return $response;
    }
    
    #[Route('/download-legacy/{filename}', name: 'app_file_download_legacy', methods: ['GET'])]
    public function downloadLegacy(string $filename): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('Project directory parameter must be a string');
        }
        $uploadPath = $projectDir . '/var/uploads';
        $filePath = $uploadPath . '/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }
        
        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            throw $this->createNotFoundException('File could not be read');
        }
        
        $response = new Response();
        $response->setContent($fileContents);
        $response->headers->set('Content-Type', mime_content_type($filePath) ?: 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
        
        return $response;
    }
    
    #[Route('/delete/{id}', name: 'app_file_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $file = $this->fileRepository->find($id);
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }
        
        // Check if user has permission to delete this file
        // TODO: Add proper permission checking based on entity ownership
        
        try {
            $this->fileUploadService->deleteFile($file);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Failed to delete file'], 500);
        }
    }
    
    #[Route('/delete-legacy/{filename}', name: 'app_file_delete_legacy', methods: ['DELETE'])]
    public function deleteLegacy(string $filename): JsonResponse
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('Project directory parameter must be a string');
        }
        $uploadPath = $projectDir . '/var/uploads';
        $filePath = $uploadPath . '/' . $filename;
        
        if (!file_exists($filePath)) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }
        
        if (unlink($filePath)) {
            return new JsonResponse(['success' => true]);
        }
        
        return new JsonResponse(['success' => false, 'error' => 'Failed to delete file'], 500);
    }
    
    #[Route('/preview/{filename}', name: 'app_file_preview', methods: ['GET'])]
    public function preview(string $filename): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('Project directory parameter must be a string');
        }
        $uploadPath = $projectDir . '/var/uploads';
        $filePath = $uploadPath . '/' . $filename;
        
        if (!file_exists($filePath)) {
            return new Response('<p class="text-red-500">File not found</p>');
        }
        
        $mimeType = mime_content_type($filePath);
        $fileContents = file_get_contents($filePath);
        
        if ($fileContents === false) {
            return new Response('<p class="text-red-500">File could not be read</p>');
        }
        
        $mimeTypeString = $mimeType ?: '';
        
        if ($mimeTypeString === 'text/markdown' || str_ends_with($filename, '.md')) {
            // Basic markdown rendering (you may want to use a proper markdown parser)
            $content = htmlspecialchars($fileContents);
            $content = '<pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded border">' . $content . '</pre>';
        } elseif (str_starts_with($mimeTypeString, 'text/')) {
            $content = htmlspecialchars($fileContents);
            $content = '<pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded border">' . $content . '</pre>';
        } else {
            $content = '<p class="text-gray-500">Preview not available for this file type</p>';
        }
        
        return new Response($content);
    }
    
    private function validateFile(UploadedFile $file): array
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'error' => 'File size exceeds 10MB limit'
            ];
        }
        
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed'
            ];
        }
        
        return ['valid' => true];
    }
    
}