<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FileRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\FileUploadService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/files')]
class FileController extends AbstractController
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private FileRepository $fileRepository,
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository,
        private LoggerInterface $logger
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
        'text/xml',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    #[Route('/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $files = $request->files->get('files', []);
        $projectId = $request->request->get('project_id');
        $taskId = $request->request->get('task_id');

        if (! is_array($files)) {
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
                'errors' => [['filename' => 'Upload', 'error' => 'No task or project ID provided']],
            ], 400);
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            // Validate file
            $validation = $this->validateFile($file);
            if (! $validation['valid']) {
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $validation['error'],
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
                    'filename' => basename($uploadedFile->getPath() ?? ''),
                    'size' => $this->fileUploadService->getFileSize($uploadedFile),
                    'mime_type' => $uploadedFile->getType(),
                ];

            } catch (\Exception $e) {
                $this->logger->error('File upload failed', [
                    'filename' => $file->getClientOriginalName(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => 'Failed to upload file.',
                ];
            }
        }

        return new JsonResponse([
            'success' => count($uploadedFiles) > 0,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors,
        ]);
    }

    #[Route('/download/{id}', name: 'app_file_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(int $id): Response
    {
        $file = $this->fileRepository->find($id);

        if (! $file) {
            throw $this->createNotFoundException('File not found');
        }

        $this->checkFileAccess($file, $this->getUser());

        if (! $this->fileUploadService->fileExists($file)) {
            throw $this->createNotFoundException('File not found on disk');
        }

        $fileContent = $this->fileUploadService->getFileContent($file);
        $fileSize = $this->fileUploadService->getFileSize($file);

        $response = new Response();
        $response->setContent($fileContent);
        $response->headers->set('Content-Type', $file->getType() ?: 'application/octet-stream');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $file->getFilename() ?? 'download',
            preg_replace('/[^\x20-\x7E]/', '', $file->getFilename() ?? 'download')
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', (string) $fileSize);

        return $response;
    }

    #[Route('/download-legacy/{filename}', name: 'app_file_download_legacy', methods: ['GET'])]
    public function downloadLegacy(string $filename): Response
    {
        // Validate filename to prevent path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw $this->createNotFoundException('Invalid filename');
        }

        $safeFilename = basename($filename);
        if ($safeFilename !== $filename || empty($safeFilename)) {
            throw $this->createNotFoundException('Invalid filename');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        if (! is_string($projectDir)) {
            throw new \RuntimeException('Project directory parameter must be a string');
        }
        
        $uploadPath = realpath($projectDir.'/var/uploads');
        if (false === $uploadPath) {
            throw $this->createNotFoundException('Upload directory not found');
        }
        
        $filePath = $uploadPath.DIRECTORY_SEPARATOR.$safeFilename;
        $realFilePath = realpath($filePath);
        
        if (false === $realFilePath || ! str_starts_with($realFilePath, $uploadPath.DIRECTORY_SEPARATOR)) {
            throw $this->createNotFoundException('File not found');
        }

        if (! file_exists($realFilePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $fileContents = file_get_contents($realFilePath);
        if (false === $fileContents) {
            throw $this->createNotFoundException('File could not be read');
        }

        $response = new Response();
        $response->setContent($fileContents);
        $response->headers->set('Content-Type', mime_content_type($realFilePath) ?: 'application/octet-stream');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $safeFilename,
            preg_replace('/[^\x20-\x7E]/', '', $safeFilename)
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/delete/{id}', name: 'app_file_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $file = $this->fileRepository->find($id);

        if (! $file) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }

        $this->checkFileAccess($file, $this->getUser());

        try {
            $this->fileUploadService->deleteFile($file);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('File deletion failed', [
                'file_id' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse(['success' => false, 'error' => 'Failed to delete file'], 500);
        }
    }

    #[Route('/delete-legacy/{filename}', name: 'app_file_delete_legacy', methods: ['DELETE'])]
    public function deleteLegacy(string $filename): JsonResponse
    {
        // Validate filename to prevent path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid filename'], 400);
        }

        $safeFilename = basename($filename);
        if ($safeFilename !== $filename || empty($safeFilename)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid filename'], 400);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        if (! is_string($projectDir)) {
            throw new \RuntimeException('Project directory parameter must be a string');
        }
        
        $uploadPath = realpath($projectDir.'/var/uploads');
        if (false === $uploadPath) {
            return new JsonResponse(['success' => false, 'error' => 'Upload directory not found'], 500);
        }
        
        $filePath = $uploadPath.DIRECTORY_SEPARATOR.$safeFilename;
        $realFilePath = realpath($filePath);
        
        if (false === $realFilePath || ! str_starts_with($realFilePath, $uploadPath.DIRECTORY_SEPARATOR)) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }

        if (! file_exists($realFilePath)) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }

        if (unlink($realFilePath)) {
            return new JsonResponse(['success' => true]);
        }

        $this->logger->error('Failed to delete legacy file', [
            'filename' => $safeFilename,
            'path' => $realFilePath
        ]);

        return new JsonResponse(['success' => false, 'error' => 'Failed to delete file'], 500);
    }

    #[Route('/preview/{filename}', name: 'app_file_preview', methods: ['GET'])]
    public function preview(string $filename): Response
    {
        // Validate filename to prevent path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return new Response('<p class="text-red-500">Invalid filename</p>');
        }

        $safeFilename = basename($filename);
        if ($safeFilename !== $filename || empty($safeFilename)) {
            return new Response('<p class="text-red-500">Invalid filename</p>');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        if (! is_string($projectDir)) {
            throw new \RuntimeException('Project directory parameter must be a string');
        }
        
        $uploadPath = realpath($projectDir.'/var/uploads');
        if (false === $uploadPath) {
            return new Response('<p class="text-red-500">Upload directory not found</p>');
        }
        
        $filePath = $uploadPath.DIRECTORY_SEPARATOR.$safeFilename;
        $realFilePath = realpath($filePath);
        
        if (false === $realFilePath || ! str_starts_with($realFilePath, $uploadPath.DIRECTORY_SEPARATOR)) {
            return new Response('<p class="text-red-500">File not found</p>');
        }

        if (! file_exists($realFilePath)) {
            return new Response('<p class="text-red-500">File not found</p>');
        }

        $mimeType = mime_content_type($realFilePath);
        $fileContents = file_get_contents($realFilePath);

        if (false === $fileContents) {
            return new Response('<p class="text-red-500">File could not be read</p>');
        }

        $mimeTypeString = $mimeType ?: '';

        if ('text/markdown' === $mimeTypeString || str_ends_with($safeFilename, '.md')) {
            // Basic markdown rendering (you may want to use a proper markdown parser)
            $content = htmlspecialchars($fileContents);
            $content = '<pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded border">'.$content.'</pre>';
        } elseif (str_starts_with($mimeTypeString, 'text/')) {
            $content = htmlspecialchars($fileContents);
            $content = '<pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded border">'.$content.'</pre>';
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
                'error' => 'File size exceeds 10MB limit',
            ];
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed',
            ];
        }

        return ['valid' => true];
    }

    private function checkFileAccess($file, $user): void
    {
        if (! $user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $entityType = $file->getEntityType();
        $entityId = $file->getEntityId();

        if ('project' === $entityType) {
            $project = $this->projectRepository->find($entityId);
            if (! $project || $project->getUser() !== $user) {
                throw new AccessDeniedHttpException('Access denied to this file');
            }
        } elseif ('task' === $entityType) {
            $task = $this->taskRepository->find($entityId);
            if (! $task || $task->getProject()->getUser() !== $user) {
                throw new AccessDeniedHttpException('Access denied to this file');
            }
        } else {
            throw new AccessDeniedHttpException('Invalid file entity type');
        }
    }
}
