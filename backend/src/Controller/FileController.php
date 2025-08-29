<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/files')]
class FileController extends AbstractController
{
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
                $filename = $this->generateUniqueFilename($file);
                $projectDir = $this->getParameter('kernel.project_dir');
                if (!is_string($projectDir)) {
                    throw new \RuntimeException('Project directory parameter must be a string');
                }
                $uploadPath = $projectDir . '/var/uploads';
                
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                $file->move($uploadPath, $filename);
                
                $uploadedFiles[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ];
                
            } catch (FileException $e) {
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => 'Failed to upload file'
                ];
            }
        }
        
        return new JsonResponse([
            'success' => count($uploadedFiles) > 0,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors
        ]);
    }
    
    #[Route('/download/{filename}', name: 'app_file_download', methods: ['GET'])]
    public function download(string $filename): Response
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
    
    #[Route('/delete/{filename}', name: 'app_file_delete', methods: ['DELETE'])]
    public function delete(string $filename): JsonResponse
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
    
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        return uniqid($basename . '_', true) . '.' . $extension;
    }
}