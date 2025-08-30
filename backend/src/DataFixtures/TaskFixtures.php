<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Tasks for demo project
        $demoTasks = [
            [
                'title' => 'Set up project structure',
                'description' => 'Create the basic project structure with necessary directories and configuration files.',
                'status' => Task::STATUS_COMPLETED,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'title' => 'Implement user authentication',
                'description' => 'Develop a secure user authentication system with registration and login functionality.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_CRITICAL,
            ],
            [
                'title' => 'Design database schema',
                'description' => 'Create the database schema for users, projects, and tasks with proper relationships.',
                'status' => Task::STATUS_COMPLETED,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'title' => 'Create REST API endpoints',
                'description' => 'Implement RESTful API endpoints for CRUD operations on all entities.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'title' => 'Add file upload functionality',
                'description' => 'Implement secure file upload and management features.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_MEDIUM,
            ],
            [
                'title' => 'Add user profile management',
                'description' => 'Allow users to update their profile information and preferences.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_LOW,
            ],
            [
                'title' => 'Implement email notifications',
                'description' => 'Send email notifications for important events.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_MEDIUM,
            ],
            [
                'title' => 'Add data export feature',
                'description' => 'Allow users to export their data in various formats.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_LOW,
            ],
            [
                'title' => 'Security audit',
                'description' => 'Perform comprehensive security audit of the application.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_CRITICAL,
            ],
            [
                'title' => 'Performance optimization',
                'description' => 'Optimize database queries and application performance.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_HIGH,
            ],
        ];

        foreach ($demoTasks as $index => $taskData) {
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description']);
            $task->setStatus($taskData['status']);
            $task->setPriority($taskData['priority']);
            $task->setProject($this->getReference(ProjectFixtures::DEMO_PROJECT_REFERENCE, Project::class));
            $manager->persist($task);
            $this->addReference("demo-task-{$index}", $task);
        }

        // Tasks for test project
        $testTasks = [
            [
                'title' => 'Write unit tests',
                'description' => 'Create comprehensive unit tests for all service classes.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'title' => 'Setup CI/CD pipeline',
                'description' => 'Configure continuous integration and deployment pipeline.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_CRITICAL,
            ],
            [
                'title' => 'Performance optimization',
                'description' => 'Optimize database queries and application performance.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_MEDIUM,
            ],
        ];

        foreach ($testTasks as $index => $taskData) {
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description']);
            $task->setStatus($taskData['status']);
            $task->setPriority($taskData['priority']);
            $task->setProject($this->getReference(ProjectFixtures::TEST_PROJECT_REFERENCE, Project::class));
            $manager->persist($task);
            $this->addReference("test-task-{$index}", $task);
        }

        // Additional tasks for other projects
        $additionalTasks = [
            [
                'project' => 'project-0',
                'title' => 'Implement shopping cart',
                'description' => 'Create shopping cart functionality with session persistence.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'project' => 'project-0',
                'title' => 'Payment integration',
                'description' => 'Integrate payment gateway for order processing.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_CRITICAL,
            ],
            [
                'project' => 'project-1',
                'title' => 'Rate limiting',
                'description' => 'Implement rate limiting for API protection.',
                'status' => Task::STATUS_COMPLETED,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'project' => 'project-1',
                'title' => 'API documentation',
                'description' => 'Create comprehensive API documentation with examples.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_MEDIUM,
            ],
            [
                'project' => 'project-2',
                'title' => 'WebSocket integration',
                'description' => 'Implement real-time features using WebSocket connections.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_HIGH,
            ],
            [
                'project' => 'project-2',
                'title' => 'Push notifications',
                'description' => 'Add push notification support for mobile devices.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_LOW,
            ],
        ];

        foreach ($additionalTasks as $index => $taskData) {
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description']);
            $task->setStatus($taskData['status']);
            $task->setPriority($taskData['priority']);
            $task->setProject($this->getReference($taskData['project'], Project::class));
            $manager->persist($task);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
        ];
    }
}
