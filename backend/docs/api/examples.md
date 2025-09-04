# API Usage Examples

This document provides practical examples of using the SpecSrv API in various programming languages.

## Authentication

All API requests require an API key in the header:

```bash
X-API-KEY: your-api-key-here
```

## cURL Examples

### Create a Project

```bash
curl -X POST http://localhost:8080/api/v1/projects \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your-api-key" \
  -d '{
    "title": "My New Project",
    "description": "A sample project created via API",
    "github_repo": "username/my-repo"
  }'
```

### List Projects

```bash
curl -X GET http://localhost:8080/api/v1/projects \
  -H "X-API-KEY: your-api-key"
```

### Upload a File

```bash
curl -X POST http://localhost:8080/api/files \
  -H "X-API-KEY: your-api-key" \
  -F "file=@/path/to/document.pdf" \
  -F "entity_type=project" \
  -F "entity_id=1" \
  -F "name=project-document"
```

### Download a File

```bash
curl -X GET http://localhost:8080/api/files/1/download \
  -H "X-API-KEY: your-api-key" \
  -o downloaded-file.pdf
```

## JavaScript/Node.js Examples

### Using Fetch API

```javascript
const API_BASE = 'http://localhost:8080/api';
const API_KEY = 'your-api-key';

// Create a project
async function createProject(title, description, githubRepo) {
    const response = await fetch(`${API_BASE}/v1/projects`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-KEY': API_KEY
        },
        body: JSON.stringify({
            title,
            description,
            github_repo: githubRepo
        })
    });
    
    const data = await response.json();
    if (data.success) {
        console.log('Project created:', data.data.project);
        return data.data.project;
    } else {
        console.error('Error:', data.error);
        throw new Error(data.error.message);
    }
}

// List projects
async function listProjects() {
    const response = await fetch(`${API_BASE}/v1/projects`, {
        headers: {
            'X-API-KEY': API_KEY
        }
    });
    
    const data = await response.json();
    if (data.success) {
        return data.data.projects;
    } else {
        throw new Error(data.error.message);
    }
}

// Upload a file
async function uploadFile(file, entityType, entityId, customName = null) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('entity_type', entityType);
    formData.append('entity_id', entityId.toString());
    
    if (customName) {
        formData.append('name', customName);
    }
    
    const response = await fetch(`${API_BASE}/files`, {
        method: 'POST',
        headers: {
            'X-API-KEY': API_KEY
        },
        body: formData
    });
    
    const data = await response.json();
    if (data.success) {
        return data.data.file;
    } else {
        throw new Error(data.error.message);
    }
}

// Example usage
async function example() {
    try {
        // Create a project
        const project = await createProject(
            'API Test Project',
            'Created via JavaScript API',
            'user/test-repo'
        );
        
        // List all projects
        const projects = await listProjects();
        console.log('All projects:', projects);
        
        // Upload a file (assuming you have a file input element)
        const fileInput = document.getElementById('fileInput');
        if (fileInput.files.length > 0) {
            const uploadedFile = await uploadFile(
                fileInput.files[0],
                'project',
                project.id,
                'api-uploaded-file'
            );
            console.log('File uploaded:', uploadedFile);
        }
        
    } catch (error) {
        console.error('API Error:', error.message);
    }
}
```

## Python Examples

### Using requests library

```python
import requests
import json
from pathlib import Path

API_BASE = 'http://localhost:8080/api'
API_KEY = 'your-api-key'

class SpecSrvAPI:
    def __init__(self, base_url, api_key):
        self.base_url = base_url
        self.headers = {'X-API-KEY': api_key}
    
    def create_project(self, title, description=None, github_repo=None):
        """Create a new project"""
        data = {'title': title}
        if description:
            data['description'] = description
        if github_repo:
            data['github_repo'] = github_repo
        
        response = requests.post(
            f'{self.base_url}/v1/projects',
            headers={**self.headers, 'Content-Type': 'application/json'},
            json=data
        )
        
        result = response.json()
        if result['success']:
            return result['data']['project']
        else:
            raise Exception(result['error']['message'])
    
    def list_projects(self):
        """List all projects"""
        response = requests.get(
            f'{self.base_url}/v1/projects',
            headers=self.headers
        )
        
        result = response.json()
        if result['success']:
            return result['data']['projects']
        else:
            raise Exception(result['error']['message'])
    
    def upload_file(self, file_path, entity_type, entity_id, custom_name=None):
        """Upload a file"""
        file_path = Path(file_path)
        
        with open(file_path, 'rb') as file:
            files = {'file': (file_path.name, file)}
            data = {
                'entity_type': entity_type,
                'entity_id': str(entity_id)
            }
            
            if custom_name:
                data['name'] = custom_name
            
            response = requests.post(
                f'{self.base_url}/files',
                headers=self.headers,
                files=files,
                data=data
            )
        
        result = response.json()
        if result['success']:
            return result['data']['file']
        else:
            raise Exception(result['error']['message'])
    
    def download_file(self, file_id, save_path):
        """Download a file"""
        response = requests.get(
            f'{self.base_url}/files/{file_id}/download',
            headers=self.headers,
            stream=True
        )
        
        if response.status_code == 200:
            with open(save_path, 'wb') as file:
                for chunk in response.iter_content(chunk_size=8192):
                    file.write(chunk)
            return save_path
        else:
            result = response.json()
            raise Exception(result['error']['message'])

# Example usage
def main():
    api = SpecSrvAPI(API_BASE, API_KEY)
    
    try:
        # Create a project
        project = api.create_project(
            title='Python API Test',
            description='Created using Python requests',
            github_repo='user/python-test'
        )
        print(f'Created project: {project["title"]} (ID: {project["id"]})')
        
        # List all projects
        projects = api.list_projects()
        print(f'Total projects: {len(projects)}')
        
        # Upload a file
        uploaded_file = api.upload_file(
            file_path='./example.txt',
            entity_type='project',
            entity_id=project['id'],
            custom_name='python-upload'
        )
        print(f'Uploaded file: {uploaded_file["filename"]} (ID: {uploaded_file["id"]})')
        
        # Download the file
        download_path = api.download_file(
            file_id=uploaded_file['id'],
            save_path='./downloaded_example.txt'
        )
        print(f'Downloaded file to: {download_path}')
        
    except Exception as e:
        print(f'Error: {e}')

if __name__ == '__main__':
    main()
```

## PHP Examples

### Using cURL

```php
<?php

class SpecSrvAPI {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($baseUrl, $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }
    
    private function makeRequest($method, $endpoint, $data = null, $isFile = false) {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                ...$isFile ? [] : ['Content-Type: application/json']
            ]
        ]);
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, 
                        $isFile ? $data : json_encode($data)
                    );
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300 && $result['success']) {
            return $result['data'];
        } else {
            throw new Exception($result['error']['message'] ?? 'Unknown error');
        }
    }
    
    public function createProject($title, $description = null, $githubRepo = null) {
        $data = ['title' => $title];
        if ($description) $data['description'] = $description;
        if ($githubRepo) $data['github_repo'] = $githubRepo;
        
        return $this->makeRequest('POST', '/v1/projects', $data);
    }
    
    public function listProjects() {
        return $this->makeRequest('GET', '/v1/projects')['projects'];
    }
    
    public function uploadFile($filePath, $entityType, $entityId, $customName = null) {
        $postData = [
            'file' => new CURLFile($filePath),
            'entity_type' => $entityType,
            'entity_id' => (string)$entityId
        ];
        
        if ($customName) {
            $postData['name'] = $customName;
        }
        
        return $this->makeRequest('POST', '/files', $postData, true);
    }
}

// Example usage
try {
    $api = new SpecSrvAPI('http://localhost:8080/api', 'your-api-key');
    
    // Create a project
    $project = $api->createProject(
        'PHP API Test',
        'Created using PHP cURL',
        'user/php-test'
    );
    echo "Created project: {$project['project']['title']} (ID: {$project['project']['id']})\n";
    
    // List projects
    $projects = $api->listProjects();
    echo "Total projects: " . count($projects) . "\n";
    
    // Upload a file
    $uploadedFile = $api->uploadFile(
        './test.txt',
        'project',
        $project['project']['id'],
        'php-upload'
    );
    echo "Uploaded file: {$uploadedFile['file']['filename']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```

## Error Handling Best Practices

### Handle Rate Limits

```javascript
async function apiRequestWithRetry(url, options, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        const response = await fetch(url, options);
        
        if (response.status === 429) {
            // Rate limited - wait before retrying
            const retryAfter = response.headers.get('Retry-After') || 60;
            console.log(`Rate limited. Waiting ${retryAfter}s before retry...`);
            await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
            continue;
        }
        
        return response;
    }
    
    throw new Error('Max retries exceeded');
}
```

### Validate Response Data

```python
def safe_api_call(api_func, *args, **kwargs):
    """Safely call API function with error handling"""
    try:
        return api_func(*args, **kwargs)
    except requests.exceptions.ConnectionError:
        print("Failed to connect to API server")
        return None
    except requests.exceptions.Timeout:
        print("API request timed out")
        return None
    except json.JSONDecodeError:
        print("Invalid JSON response from API")
        return None
    except Exception as e:
        print(f"API error: {e}")
        return None
```

## SDK Development

For production use, consider creating SDK libraries that wrap these API calls with proper error handling, retry logic, and response validation. The examples above can serve as a foundation for building more robust client libraries.