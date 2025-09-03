# SpecSrv API Documentation

SpecSrv provides a RESTful API for managing projects, tasks, files, and user accounts. This documentation covers all available endpoints, authentication methods, and usage examples.

## Base URL

- **Development**: `http://localhost:8080/api`
- **Production**: `https://your-domain.com/api`

## Authentication

The API uses API key authentication. Include your API key in the request header:

```
X-API-KEY: your-api-key-here
```

### Getting an API Key

API keys can be obtained by logging in through the web interface and creating a new key in your account settings.

## Response Format

All API responses follow a consistent JSON format:

### Success Response
```json
{
    "success": true,
    "data": {
        // Response data here
    }
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message"
    }
}
```

## Rate Limiting

The API implements rate limiting to ensure fair usage:

- **Standard API**: 1000 requests per hour per API key
- **Authentication endpoints**: 5 requests per minute per IP
- **File uploads**: 50 requests per hour per API key

Rate limit information is included in response headers:
- `X-RateLimit-Limit`: Request limit per window
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when the rate limit resets (Unix timestamp)

## Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `UNAUTHORIZED` | Invalid or missing API key |
| `FORBIDDEN` | Access denied for this resource |
| `NOT_FOUND` | Resource not found |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `INTERNAL_SERVER_ERROR` | Server error |

## API Endpoints

### Authentication

#### POST /api/login
Login with email and password to obtain session.

#### GET /api/user  
Get current authenticated user information.

#### GET /api/csrf-token
Get CSRF token for form submissions.

### Projects

#### GET /api/v1/projects
List all projects for the authenticated user.

#### POST /api/v1/projects
Create a new project.

#### GET /api/v1/projects/{id}
Get details of a specific project.

#### PUT /api/v1/projects/{id}
Update a specific project.

#### DELETE /api/v1/projects/{id}
Delete a specific project.

### Tasks

Tasks are managed as part of projects. See [Tasks API Documentation](./tasks.md) for details.

### Files

#### POST /api/files
Upload a file associated with a project or task.

#### GET /api/files/{id}
Get file metadata.

#### GET /api/files/{id}/download
Download a file.

#### DELETE /api/files/{id}
Delete a file.

#### GET /api/files/entity/{entityType}/{entityId}
List all files for a specific entity (project or task).

#### GET /api/files/limits
Get file upload limits and allowed types.

## SDKs and Libraries

- [JavaScript/TypeScript SDK](./sdks/javascript.md)
- [PHP SDK](./sdks/php.md)
- [Python SDK](./sdks/python.md)

## Examples

See the [Examples](./examples/) directory for code samples in various languages.

## Changelog

- **v1.0.0**: Initial API release
- **v1.1.0**: Added file upload support
- **v1.2.0**: Added rate limiting and enhanced error handling

## Support

For API support, please:
1. Check this documentation
2. Review the [FAQ](./faq.md)
3. Open an issue on GitHub
4. Contact support at api-support@yourdomain.com