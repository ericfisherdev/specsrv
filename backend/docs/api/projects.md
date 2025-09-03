# Projects API

The Projects API allows you to manage project resources.

## Endpoints

### List Projects

Get a list of all projects for the authenticated user.

**Request:**
```http
GET /api/v1/projects
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "projects": [
            {
                "id": 1,
                "title": "Sample Project",
                "description": "A sample project for demonstration",
                "github_repo": "user/sample-repo",
                "created_at": "2025-01-01 12:00:00",
                "updated_at": "2025-01-01 12:00:00"
            }
        ],
        "total": 1
    }
}
```

### Create Project

Create a new project.

**Request:**
```http
POST /api/v1/projects
Content-Type: application/json
X-API-KEY: your-api-key

{
    "title": "My New Project",
    "description": "Project description",
    "github_repo": "username/repo-name"
}
```

**Parameters:**
- `title` (required): Project title
- `description` (optional): Project description  
- `github_repo` (optional): GitHub repository in format "username/repo"

**Response:**
```json
{
    "success": true,
    "data": {
        "project": {
            "id": 2,
            "title": "My New Project",
            "description": "Project description",
            "github_repo": "username/repo-name",
            "created_at": "2025-01-01 12:30:00",
            "updated_at": "2025-01-01 12:30:00"
        }
    }
}
```

### Get Project

Get details of a specific project.

**Request:**
```http
GET /api/v1/projects/1
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "project": {
            "id": 1,
            "title": "Sample Project",
            "description": "A sample project for demonstration",
            "github_repo": "user/sample-repo",
            "created_at": "2025-01-01 12:00:00",
            "updated_at": "2025-01-01 12:00:00",
            "tasks_count": 5,
            "files_count": 3
        }
    }
}
```

### Update Project

Update an existing project.

**Request:**
```http
PUT /api/v1/projects/1
Content-Type: application/json
X-API-KEY: your-api-key

{
    "title": "Updated Project Title",
    "description": "Updated description"
}
```

**Parameters:**
- `title` (optional): New project title
- `description` (optional): New project description
- `github_repo` (optional): New GitHub repository

**Response:**
```json
{
    "success": true,
    "data": {
        "project": {
            "id": 1,
            "title": "Updated Project Title",
            "description": "Updated description",
            "github_repo": "user/sample-repo",
            "created_at": "2025-01-01 12:00:00",
            "updated_at": "2025-01-01 13:00:00"
        }
    }
}
```

### Delete Project

Delete a project and all associated tasks and files.

**Request:**
```http
DELETE /api/v1/projects/1
X-API-KEY: your-api-key
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "Project deleted successfully"
    }
}
```

## Error Responses

### Project Not Found
```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Project not found"
    }
}
```

### Validation Error
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Title is required"
    }
}
```

### Access Denied
```json
{
    "success": false,
    "error": {
        "code": "FORBIDDEN",
        "message": "You don't have access to this project"
    }
}
```