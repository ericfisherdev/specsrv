<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEMO_PROJECT_REFERENCE = 'demo-project';
    public const TEST_PROJECT_REFERENCE = 'test-project';

    public function load(ObjectManager $manager): void
    {
        // Demo project for admin user
        $demoProject = new Project();
        $demoProject->setTitle('SpecSrv Demo Project');
        $demoProject->setDescription('A demonstration project showcasing the capabilities of SpecSrv task management system.');
        $demoProject->setGithubRepo('https://github.com/demo/specsrv-demo');
        $demoProject->setUser($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $manager->persist($demoProject);
        $this->addReference(self::DEMO_PROJECT_REFERENCE, $demoProject);

        // Test project for test user
        $testProject = new Project();
        $testProject->setTitle('Test Application');
        $testProject->setDescription('A test application project for development and testing purposes.');
        $testProject->setGithubRepo('https://github.com/test/test-app');
        $testProject->setUser($this->getReference(UserFixtures::TEST_USER_REFERENCE, User::class));
        $manager->persist($testProject);
        $this->addReference(self::TEST_PROJECT_REFERENCE, $testProject);

        // Additional projects for other users
        $projects = [
            [
                'title' => 'E-commerce Platform',
                'description' => 'Building a modern e-commerce platform with microservices architecture.',
                'github' => 'https://github.com/user1/ecommerce',
                'user' => 'user-1',
            ],
            [
                'title' => 'API Gateway',
                'description' => 'Developing a scalable API gateway for enterprise applications.',
                'github' => 'https://github.com/user2/api-gateway',
                'user' => 'user-2',
            ],
            [
                'title' => 'Mobile App Backend',
                'description' => 'REST API backend for a mobile application with real-time features.',
                'github' => 'https://github.com/user3/mobile-backend',
                'user' => 'user-3',
            ],
        ];

        foreach ($projects as $index => $projectData) {
            $project = new Project();
            $project->setTitle($projectData['title']);
            $project->setDescription($projectData['description']);
            $project->setGithubRepo($projectData['github']);
            $project->setUser($this->getReference($projectData['user'], User::class));
            $manager->persist($project);
            $this->addReference("project-{$index}", $project);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
