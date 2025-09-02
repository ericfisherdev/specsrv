# SpecSrv API Documentation

This document provides comprehensive documentation for the SpecSrv REST API endpoints. The API follows RESTful conventions and returns JSON responses.

## Base URL

```
Development: http://localhost:8080/api/v1
Production:  https://your-domain.com/api/v1
```

## Authentication

The API uses JWT (JSON Web Token) authentication. Include the token in the Authorization header:

```http
Authorization: Bearer <your-jwt-token>
```

### Token Management

- Tokens expire after a configurable duration (default: 1 hour)
- Use the refresh endpoint to obtain new tokens
- Tokens are stateless (server-side session management not required)

## Response Format

All API responses follow a consistent structure:

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { /* response data */ },
  "pagination": { /* pagination info for paginated responses */ }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Human-readable error message",
  "error_code": "MACHINE_READABLE_CODE",
  "errors": { /* validation errors, if applicable */ }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Results retrieved successfully",
  "data": [ /* array of items */ ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `204` - No Content (successful deletion)
- `400` - Bad Request (validation errors, invalid input)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (access denied)
- `404` - Not Found
- `409` - Conflict (resource already exists)
- `500` - Internal Server Error

## Endpoints

### Authentication Endpoints

#### POST /auth/login
Authenticate user and receive JWT token.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "created_at": "2023-01-01T00:00:00Z"
    }
  }
}
```

#### POST /auth/register
Register a new user account.

**Request:**
```json
{
  "email": "newuser@example.com",
  "password": "password",
  "name": "New User" // optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
      "id": 2,
      "email": "newuser@example.com",
      "name": "New User",
      "created_at": "2023-01-01T12:00:00Z"
    }
  }
}
```

#### POST /auth/refresh
Refresh JWT token using existing valid token.

**Headers:**
```http
Authorization: Bearer <current-token>
```

**Response:**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "created_at": "2023-01-01T00:00:00Z"
    }
  }
}
```

#### POST /auth/logout
Logout user (client-side token removal).

**Response:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

#### GET /auth/me
Get current authenticated user information.

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "created_at": "2023-01-01T00:00:00Z"
    }
  }
}
```

### Project Endpoints

#### GET /projects
List all projects for the authenticated user.

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "My Project",
      "description": "Project description",
      "github_repo": "https://github.com/user/repo",
      "created_at": "2023-01-01T00:00:00Z",
      "updated_at": "2023-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 5,
    "total_pages": 1
  }
}
```

#### POST /projects
Create a new project.

**Request:**
```json
{
  "title": "New Project",
  "description": "Project description", // optional
  "github_repo": "https://github.com/user/repo" // optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "Project created successfully",
  "data": {
    "id": 2,
    "title": "New Project",
    "description": "Project description",
    "github_repo": "https://github.com/user/repo",
    "created_at": "2023-01-01T12:00:00Z",
    "updated_at": "2023-01-01T12:00:00Z"
  }
}
```

#### GET /projects/{id}
Get a specific project by ID.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "My Project",
    "description": "Project description",
    "github_repo": "https://github.com/user/repo",
    "created_at": "2023-01-01T00:00:00Z",
    "updated_at": "2023-01-01T00:00:00Z"
  }
}
```

#### PUT /projects/{id}
Update a project.

**Request:**
```json
{
  "title": "Updated Project",
  "description": "Updated description",
  "github_repo": "https://github.com/user/updated-repo"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Project updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Project",
    "description": "Updated description",
    "github_repo": "https://github.com/user/updated-repo",
    "created_at": "2023-01-01T00:00:00Z",
    "updated_at": "2023-01-01T13:00:00Z"
  }
}
```

#### DELETE /projects/{id}
Delete a project.

**Response:**
```json
{
  "success": true,
  "message": "Project deleted successfully"
}
```

#### GET /projects/{id}/tasks
List tasks for a specific project.

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `status` - Filter by task status (todo, backlog, in_progress, review, completed)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Task title",
      "description": "Task description",
      "status": "todo",
      "priority": "medium",
      "project_id": 1,
      "created_at": "2023-01-01T00:00:00Z",
      "updated_at": "2023-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 10,
    "total_pages": 1
  }
}
```

#### GET /projects/{id}/files
List files attached to a project.

**Response:**
```json
{
  "success": true,
  "data": {
    "files": [
      {
        "id": 1,
        "filename": "document.pdf",
        "path": "/uploads/projects/1/document.pdf",
        "type": "application/pdf",
        "size": 1024000,
        "entity_type": "project",
        "entity_id": 1,
        "created_at": "2023-01-01T00:00:00Z"
      }
    ],
    "total": 1
  }
}
```

#### GET /projects/{id}/commits
List commits associated with project tasks.

**Response:**
```json
{
  "success": true,
  "data": {
    "commits": [
      {
        "commit_hash": "abc123def456",
        "task_id": 1,
        "task_title": "Feature implementation",
        "created_at": "2023-01-01T00:00:00Z",
        "pr_reference": "123"
      }
    ],
    "total": 1
  }
}
```

### Task Endpoints

#### GET /tasks
List all tasks for the authenticated user.

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `status` - Filter by status
- `search` - Search in title and description

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Task title",
      "description": "Task description",
      "status": "todo",
      "priority": "medium",
      "project": {
        "id": 1,
        "title": "Project Title"
      },
      "created_at": "2023-01-01T00:00:00Z",
      "updated_at": "2023-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 50,
    "total_pages": 3
  }
}
```

#### POST /tasks
Create a new task.

**Request:**
```json
{
  "project_id": 1,
  "title": "New Task",
  "description": "Task description", // optional
  "status": "todo" // optional, defaults to "todo"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 2,
    "title": "New Task",
    "description": "Task description",
    "status": "todo",
    "priority": "medium",
    "project_id": 1,
    "created_at": "2023-01-01T12:00:00Z",
    "updated_at": "2023-01-01T12:00:00Z"
  }
}
```

#### GET /tasks/{id}
Get a specific task by ID.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Task title",
    "description": "Task description",
    "status": "in_progress",
    "priority": "high",
    "project": {
      "id": 1,
      "title": "Project Title"
    },
    "created_at": "2023-01-01T00:00:00Z",
    "updated_at": "2023-01-01T10:00:00Z"
  }
}
```

#### PUT /tasks/{id}
Update a task.

**Request:**
```json
{
  "title": "Updated Task Title",
  "description": "Updated description",
  "status": "in_progress"
}
```

#### PATCH /tasks/{id}/status
Update only the task status.

**Request:**
```json
{
  "status": "completed"
}
```

**Available Statuses:**
- `todo`
- `backlog`
- `in_progress`
- `review`
- `completed`

#### DELETE /tasks/{id}
Delete a task.

**Response:**
```json
{
  "success": true,
  "message": "Task deleted successfully"
}
```

#### GET /tasks/{id}/files
List files attached to a task.

#### POST /tasks/{id}/git-links
Create a git link for a task.

**Request:**
```json
{
  "commit_hash": "abc123def456", // optional, but required if no pr_reference
  "pr_reference": "123" // optional, but required if no commit_hash
}
```

#### GET /tasks/{id}/git-links
List git links for a task.

#### DELETE /tasks/{taskId}/git-links/{linkId}
Delete a git link.

### File Endpoints

#### POST /files
Upload a file.

**Request (Form Data):**
- `file` - File to upload
- `entity_type` - Entity type (project, task)
- `entity_id` - Entity ID
- `name` - Custom filename (optional)

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "file": {
      "id": 1,
      "filename": "document.pdf",
      "path": "/uploads/projects/1/document.pdf",
      "type": "application/pdf",
      "size": 1024000,
      "entity_type": "project",
      "entity_id": 1,
      "created_at": "2023-01-01 12:00:00"
    }
  }
}
```

#### GET /files/{id}
Get file information.

#### GET /files/{id}/download
Download a file.

**Response:** Binary file content with appropriate headers.

#### DELETE /files/{id}
Delete a file.

#### GET /files/limits
Get file upload limits and restrictions.

**Response:**
```json
{
  "success": true,
  "data": {
    "limits": {
      "max_file_size": 10485760, // 10MB in bytes
      "allowed_types": ["pdf", "doc", "docx", "txt", "jpg", "png"],
      "max_files_per_entity": 50
    }
  }
}
```

#### GET /files/entity/{entityType}/{entityId}
List files for a specific entity.

### Dashboard Endpoints

#### GET /dashboard/stats
Get dashboard statistics for the authenticated user.

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe"
    },
    "projects": {
      "total": 5,
      "recent": [
        {
          "id": 1,
          "title": "Recent Project",
          "description": "Description",
          "created_at": "2023-01-01T00:00:00Z"
        }
      ]
    },
    "tasks": {
      "stats": {
        "total": 25,
        "todo": 8,
        "in_progress": 5,
        "completed": 12
      },
      "recent": [
        {
          "id": 1,
          "title": "Recent Task",
          "status": "in_progress",
          "project": {
            "id": 1,
            "title": "Project Title"
          }
        }
      ]
    }
  }
}
```

### Search Endpoints

#### GET /search/suggestions
Get search suggestions based on query.

**Query Parameters:**
- `q` - Search query (minimum 2 characters)

**Response:**
```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 1,
        "title": "Task title",
        "project_title": "Project Title",
        "priority": "medium",
        "status": "todo",
        "type": "task"
      }
    ],
    "projects": [
      {
        "id": 1,
        "title": "Project title",
        "description": "Project description...",
        "type": "project"
      }
    ]
  }
}
```

#### POST /search
Perform advanced search with filters.

**Request:**
```json
{
  "query": "search terms",
  "project_id": 1, // optional
  "status": "todo", // optional
  "priority": "high", // optional
  "date_range": { // optional
    "start": "2023-01-01",
    "end": "2023-12-31"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 1,
        "title": "Task title",
        "description": "Task description",
        "status": "todo",
        "priority": "high",
        "project": {
          "id": 1,
          "title": "Project Title"
        },
        "created_at": "2023-01-01T00:00:00Z"
      }
    ],
    "count": 1,
    "criteria": {
      "query": "search terms",
      "project_id": 1,
      "status": "todo",
      "priority": "high",
      "date_range": null
    }
  }
}
```

## Error Codes

Common error codes returned by the API:

### Authentication Errors
- `MISSING_CREDENTIALS` - Email and/or password not provided
- `INVALID_CREDENTIALS` - Invalid email or password
- `AUTH_REQUIRED` - Authentication token required
- `TOKEN_EXPIRED` - JWT token has expired
- `INVALID_TOKEN` - Invalid JWT token format

### Validation Errors
- `INVALID_JSON` - Request body is not valid JSON
- `MISSING_PARAMS` - Required parameters missing
- `INVALID_STATUS` - Invalid task status provided
- `INVALID_COMMIT_HASH` - Invalid git commit hash format
- `INVALID_PR_REFERENCE` - Invalid pull request reference format

### Resource Errors
- `PROJECT_NOT_FOUND` - Project does not exist or not accessible
- `TASK_NOT_FOUND` - Task does not exist or not accessible
- `FILE_NOT_FOUND` - File does not exist or not accessible
- `USER_NOT_FOUND` - User does not exist
- `ACCESS_DENIED` - User does not have permission to access resource

### System Errors
- `UPLOAD_ERROR` - File upload failed
- `FILE_READ_ERROR` - Error reading file content
- `FILE_DELETE_ERROR` - Error deleting file
- `DATABASE_ERROR` - Database operation failed
- `INTERNAL_ERROR` - Unexpected server error

## Rate Limiting

The API implements rate limiting to ensure fair usage:

- **Standard endpoints**: 100 requests per minute per IP
- **File upload endpoints**: 10 requests per minute per IP
- **Authentication endpoints**: 5 requests per minute per IP

Rate limit headers are included in responses:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

## Pagination

List endpoints support pagination with these parameters:

- `page` - Page number (starts at 1)
- `per_page` - Items per page (max: 100, default: 20)

Pagination info is returned in the response:
```json
{
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

## CORS Configuration

The API supports Cross-Origin Resource Sharing (CORS) with the following configuration:

- **Allowed Origins**: Configured per environment
- **Allowed Methods**: GET, POST, PUT, PATCH, DELETE, OPTIONS
- **Allowed Headers**: Authorization, Content-Type, Accept
- **Exposed Headers**: X-RateLimit-*
- **Credentials**: Supported

## API Versioning

The current API version is `v1`. Future versions will be available at:
- `/api/v2/...`
- `/api/v3/...`

Version 1 will be maintained for backward compatibility.

## WebSocket Integration

Real-time updates are available via WebSocket connections at:
```
ws://localhost:8080/ws/updates
wss://your-domain.com/ws/updates
```

Authenticate WebSocket connections by sending the JWT token in the initial connection message.

## Examples

### JavaScript Frontend Integration

```javascript
// ApiService usage examples
const apiService = new ApiService('http://localhost:8080/api/v1');

// Login
const { token } = await apiService.post('/auth/login', {
  email: 'user@example.com',
  password: 'password'
});

// Set token for subsequent requests
apiService.setAuthToken(token);

// Get projects
const projects = await apiService.get('/projects');

// Create task
const newTask = await apiService.post('/tasks', {
  project_id: 1,
  title: 'New Task',
  description: 'Task description'
});

// Upload file
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('entity_type', 'project');
formData.append('entity_id', 1);

const uploadResult = await apiService.post('/files', formData, {
  'Content-Type': 'multipart/form-data'
});
```

### CLI Integration

```bash
# Configure CLI to use API
specsrv config set api.base_url http://localhost:8080/api/v1
specsrv config set api.auth_method jwt

# Login
specsrv auth login --email user@example.com

# List projects
specsrv projects list

# Create task
specsrv tasks create --project-id 1 --title "New Task"
```

This API documentation provides comprehensive coverage of all available endpoints, request/response formats, error handling, and integration examples for the SpecSrv application.