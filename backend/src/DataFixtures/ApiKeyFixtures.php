<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ApiKey;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ApiKeyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // API key for admin user
        $adminApiKey = new ApiKey();
        $adminApiKey
            ->setKeyHash(hash('sha256', 'sk_admin_test_key_12345678901234567890123456789012'))
            ->setName('Admin Development Key')
            ->setUser($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        
        $manager->persist($adminApiKey);
        $this->addReference('admin-api-key', $adminApiKey);

        // API key for test user
        $testApiKey = new ApiKey();
        $testApiKey
            ->setKeyHash(hash('sha256', 'sk_test_user_key_12345678901234567890123456789012'))
            ->setName('Test User API Key')
            ->setUser($this->getReference(UserFixtures::TEST_USER_REFERENCE, User::class));
        
        $manager->persist($testApiKey);
        $this->addReference('test-api-key', $testApiKey);

        // Additional API keys for development users
        for ($i = 1; $i <= 3; $i++) {
            $apiKey = new ApiKey();
            $apiKey
                ->setKeyHash(hash('sha256', "sk_dev{$i}_key_12345678901234567890123456789012"))
                ->setName("Development Key {$i}")
                ->setUser($this->getReference("user-{$i}", User::class));
            
            $manager->persist($apiKey);
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