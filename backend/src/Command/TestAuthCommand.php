<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[AsCommand(
    name: 'app:test-auth',
    description: 'Test authentication system'
)]
class TestAuthCommand extends Command
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = 'esfisher@gmail.com';
        $password = 'test1234';

        $io->title('Testing Authentication System');

        try {
            // Test user loading
            $io->section('1. Testing User Provider');
            $user = $this->userProvider->loadUserByIdentifier($email);

            $io->success("User found: {$user->getUserIdentifier()}");
            $io->writeln("Email: {$user->getUserIdentifier()}");
            $io->writeln('Roles: '.implode(', ', $user->getRoles()));

            // Test password verification
            $io->section('2. Testing Password Verification');
            if (! $user instanceof PasswordAuthenticatedUserInterface) {
                $io->error('User does not implement PasswordAuthenticatedUserInterface');

                return Command::FAILURE;
            }
            $isValid = $this->passwordHasher->isPasswordValid($user, $password);

            if ($isValid) {
                $io->success('Password is valid');
            } else {
                $io->error('Password is invalid');

                return Command::FAILURE;
            }

            $io->success('All authentication tests passed!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Authentication test failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
