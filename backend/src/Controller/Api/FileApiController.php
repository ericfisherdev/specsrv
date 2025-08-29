<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\FileRepository;
use App\Service\FileUploadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/files', name: 'api_files_')]
class FileApiController extends BaseApiController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly FileRepository $fileRepository
    ) {
    }

    #[Route('', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');
        $entityType = (string) $request->request->get('entity_type');
        $entityId = (int) $request->request->get('entity_id');
        $customName = $request->request->get('name');
        $customName = is_string($customName) ? $customName : null;

        if (! $uploadedFile) {
            return $this->errorResponse('No file provided', 'MISSING_FILE', null, 400);
        }

        if (! $entityType || ! $entityId) {
            return $this->errorResponse('entity_type and entity_id are required', 'MISSING_PARAMS', null, 400);
        }

        try {
            $file = $this->fileUploadService->uploadFile(
                $uploadedFile,
                $entityType,
                $entityId,
                $customName
            );

            return $this->successResponse([
                'file' => [
                    'id' => $file->getId(),
                    'filename' => $file->getFilename(),
                    'path' => $file->getPath(),
                    'type' => $file->getType(),
                    'size' => $this->fileUploadService->getFileSize($file),
                    'entity_type' => $file->getEntityType(),
                    'entity_id' => $file->getEntityId(),
                    'created_at' => $file->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ],
            ], 'File uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'UPLOAD_ERROR', null, 400);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $file = $this->fileRepository->find($id);

        if (! $file) {
            return $this->errorResponse('File not found', 'FILE_NOT_FOUND', null, 404);
        }

        return $this->successResponse([
            'file' => [
                'id' => $file->getId(),
                'filename' => $file->getFilename(),
                'path' => $file->getPath(),
                'type' => $file->getType(),
                'size' => $this->fileUploadService->fileExists($file)
                    ? $this->fileUploadService->getFileSize($file)
                    : null,
                'entity_type' => $file->getEntityType(),
                'entity_id' => $file->getEntityId(),
                'created_at' => $file->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
            ],
        ]);
    }

    #[Route('/{id}/download', name: 'download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(int $id): Response
    {
        $file = $this->fileRepository->find($id);

        if (! $file || ! $this->fileUploadService->fileExists($file)) {
            return $this->errorResponse('File not found', 'FILE_NOT_FOUND', null, 404);
        }

        try {
            $content = $this->fileUploadService->getFileContent($file);

            $response = new Response($content);
            $response->headers->set('Content-Type', $file->getType());
            $response->headers->set(
                'Content-Disposition',
                ResponseHeaderBag::DISPOSITION_ATTACHMENT.'; filename="'.$file->getFilename().'"'
            );

            return $response;
        } catch (\Exception $e) {
            return $this->errorResponse('Error reading file: '.$e->getMessage(), 'FILE_READ_ERROR', null, 500);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $file = $this->fileRepository->find($id);

        if (! $file) {
            return $this->errorResponse('File not found', 'FILE_NOT_FOUND', null, 404);
        }

        try {
            $this->fileUploadService->deleteFile($file);

            return $this->successResponse(['message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting file: '.$e->getMessage(), 'FILE_DELETE_ERROR', null, 500);
        }
    }

    #[Route('/limits', name: 'limits', methods: ['GET'])]
    public function limits(): JsonResponse
    {
        return $this->successResponse([
            'limits' => $this->fileUploadService->getUploadLimits(),
        ]);
    }

    #[Route('/entity/{entityType}/{entityId}', name: 'list_by_entity', methods: ['GET'])]
    public function listByEntity(string $entityType, int $entityId): JsonResponse
    {
        $files = $this->fileRepository->findBy([
            'entityType' => $entityType,
            'entityId' => $entityId,
        ], ['createdAt' => 'DESC']);

        $filesData = array_map(function ($file) {
            return [
                'id' => $file->getId(),
                'filename' => $file->getFilename(),
                'path' => $file->getPath(),
                'type' => $file->getType(),
                'size' => $this->fileUploadService->fileExists($file)
                    ? $this->fileUploadService->getFileSize($file)
                    : null,
                'entity_type' => $file->getEntityType(),
                'entity_id' => $file->getEntityId(),
                'created_at' => $file->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
            ];
        }, $files);

        return $this->successResponse([
            'files' => $filesData,
            'total' => count($filesData),
        ]);
    }
}
