<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const TEST_USER_REFERENCE = 'test-user';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $adminUser = new User();
        $adminUser->setEmail('admin@specsrv.dev');
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $adminUser->setPassword(
            $this->passwordHasher->hashPassword($adminUser, 'admin123')
        );
        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        // Create test user
        $testUser = new User();
        $testUser->setEmail('user@specsrv.dev');
        $testUser->setRoles(['ROLE_USER']);
        $testUser->setPassword(
            $this->passwordHasher->hashPassword($testUser, 'user123')
        );
        $manager->persist($testUser);
        $this->addReference(self::TEST_USER_REFERENCE, $testUser);

        // Create additional test users
        for ($i = 1; $i <= 3; ++$i) {
            $user = new User();
            $user->setEmail("user{$i}@specsrv.dev");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, 'password123')
            );
            $manager->persist($user);
            $this->addReference("user-{$i}", $user);
        }

        $manager->flush();
    }
}
