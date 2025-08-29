# Files API

The Files API allows you to manage file uploads associated with projects and tasks.

## Endpoints

### Upload File

Upload a file and associate it with a project or task.

**Request:**
```http
POST /api/files
Content-Type: multipart/form-data
X-API-KEY: your-api-key

Form Data:
- file: [binary file data]
- entity_type: "project" or "task"
- entity_id: 1
- name: "custom-filename" (optional)
```

**Parameters:**
- `file` (required): The file to upload
- `entity_type` (required): Either "project" or "task"
- `entity_id` (required): ID of the project or task
- `name` (optional): Custom name for the file

**Response:**
```json
{
    "success": true,
    "data": {
        "file": {
            "id": 1,
            "filename": "document.pdf",
            "path": "project/1/document-abc123.pdf",
            "type": "application/pdf",
            "size": 1024000,
            "entity_type": "project",
            "entity_id": 1,
            "created_at": "2025-01-01 12:00:00"
        }
    }
}
```

### Get File Metadata

Get metadata about a specific file.

**Request:**
```http
GET /api/files/1
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "file": {
            "id": 1,
            "filename": "document.pdf",
            "path": "project/1/document-abc123.pdf", 
            "type": "application/pdf",
            "size": 1024000,
            "entity_type": "project",
            "entity_id": 1,
            "created_at": "2025-01-01 12:00:00"
        }
    }
}
```

### Download File

Download a file.

**Request:**
```http
GET /api/files/1/download
X-API-KEY: your-api-key
```

**Response:**
- Binary file content with appropriate headers
- `Content-Type`: File MIME type
- `Content-Disposition`: attachment; filename="original-filename.ext"

### Delete File

Delete a file from storage and database.

**Request:**
```http
DELETE /api/files/1
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "File deleted successfully"
    }
}
```

### List Files by Entity

Get all files associated with a specific project or task.

**Request:**
```http
GET /api/files/entity/project/1
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "files": [
            {
                "id": 1,
                "filename": "document.pdf",
                "path": "project/1/document-abc123.pdf",
                "type": "application/pdf", 
                "size": 1024000,
                "entity_type": "project",
                "entity_id": 1,
                "created_at": "2025-01-01 12:00:00"
            }
        ],
        "total": 1
    }
}
```

### Get Upload Limits

Get file upload configuration and limits.

**Request:**
```http
GET /api/files/limits
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "limits": {
            "maxUploadSize": 52428800,
            "allowedFileTypes": [
                "txt", "md", "json", "yaml", "yml", "xml", 
                "pdf", "doc", "docx", "png", "jpg", "jpeg", "gif", "svg"
            ],
            "uploadsDirectory": "/app/var/uploads"
        }
    }
}
```

## File Organization

Files are organized in the following directory structure:
```
var/uploads/
├── project/
│   ├── 1/
│   │   ├── file1-abc123.pdf
│   │   └── file2-def456.txt
│   └── 2/
└── task/
    └── 5/
        └── task-file-xyz789.jpg
```

## Supported File Types

By default, the following file types are supported:
- **Text**: txt, md, json, yaml, yml, xml
- **Documents**: pdf, doc, docx
- **Images**: png, jpg, jpeg, gif, svg

## File Size Limits

- **Default**: 50MB (52,428,800 bytes)
- **Test Environment**: 10MB
- **Can be configured** via environment variables

## Error Responses

### File Too Large
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "File size (52428801 bytes) exceeds maximum allowed size (52428800 bytes)"
    }
}
```

### Invalid File Type
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR", 
        "message": "File type \"exe\" is not allowed. Allowed types: txt, md, json, yaml, yml, xml, pdf, doc, docx, png, jpg, jpeg, gif, svg"
    }
}
```

### File Not Found
```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "File not found"
    }
}
```

### Missing Required Fields
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "entity_type and entity_id are required"
    }
}
```