# SpecSrv API Structure

## API Base URL
- Development: `http://localhost/api`
- Production: `https://your-domain.com/api`

## Authentication

### Session-based Authentication (Web UI)
- `POST /login` - User login (form-based)
- `POST /logout` - User logout
- `GET /api/csrf-token` - Get CSRF token for forms

### API Key Authentication (API clients)
- `POST /api/auth/login` - API login with email/password to get API key
- `DELETE /api/auth/logout` - Revoke current API key
- Header: `Authorization: Bearer <api_key>`

## API Endpoints Structure

### User Management
- `GET /api/user` - Get current user info
- `PUT /api/user` - Update current user info
- `PATCH /api/user/password` - Change password

### Project Management
- `GET /api/projects` - List all projects for current user
- `POST /api/projects` - Create new project
- `GET /api/projects/{id}` - Get specific project
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project

### Task Management
- `GET /api/projects/{project_id}/tasks` - List tasks for project
- `POST /api/projects/{project_id}/tasks` - Create new task
- `GET /api/tasks/{id}` - Get specific task
- `PUT /api/tasks/{id}` - Update task
- `PATCH /api/tasks/{id}/status` - Update task status only
- `DELETE /api/tasks/{id}` - Delete task

### File Management
- `GET /api/tasks/{task_id}/files` - List files for task
- `POST /api/tasks/{task_id}/files` - Upload file to task
- `GET /api/files/{id}` - Download file
- `DELETE /api/files/{id}` - Delete file

### Git Integration
- `GET /api/tasks/{task_id}/git-links` - List git links for task
- `POST /api/tasks/{task_id}/git-links` - Add git link to task
- `DELETE /api/git-links/{id}` - Remove git link

## Standard Response Format

### Success Response
```json
{
    "success": true,
    "data": {
        // Response data here
    },
    "message": "Optional success message"
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human readable error message",
        "details": {
            // Additional error details
        }
    }
}
```

### Pagination
```json
{
    "success": true,
    "data": {
        "items": [],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total_items": 100,
            "total_pages": 5
        }
    }
}
```

## HTTP Status Codes
- `200 OK` - Success
- `201 Created` - Resource created
- `204 No Content` - Success with no content
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `409 Conflict` - Resource conflict
- `422 Unprocessable Entity` - Validation errors
- `500 Internal Server Error` - Server error

## Request/Response Examples

### Create Project
```http
POST /api/projects
Content-Type: application/json
Authorization: Bearer <api_key>

{
    "title": "My New Project",
    "description": "Project description",
    "github_repo": "https://github.com/user/repo"
}
```

Response:
```json
{
    "success": true,
    "data": {
        "id": 123,
        "title": "My New Project",
        "description": "Project description",
        "github_repo": "https://github.com/user/repo",
        "created_at": "2024-01-01T12:00:00Z",
        "updated_at": "2024-01-01T12:00:00Z"
    },
    "message": "Project created successfully"
}
```

### List Tasks
```http
GET /api/projects/123/tasks?page=1&per_page=10&status=todo
Authorization: Bearer <api_key>
```

Response:
```json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 456,
                "title": "Task title",
                "description": "Task description",
                "status": "todo",
                "project_id": 123,
                "created_at": "2024-01-01T12:00:00Z",
                "updated_at": "2024-01-01T12:00:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 10,
            "total_items": 1,
            "total_pages": 1
        }
    }
}
```

## Rate Limiting
- 1000 requests per hour for authenticated users
- 100 requests per hour for unauthenticated requests
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

## Versioning
- API version included in URL: `/api/v1/projects`
- Current version: v1
- Backward compatibility maintained for one major version