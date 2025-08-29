<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dev-setup',
    description: 'Set up development environment with database and test data'
)]
class DevSetupCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset the database before setup')
            ->setHelp('This command sets up the development environment by creating the database and loading fixtures.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reset = $input->getOption('reset');

        $io->title('SpecSrv Development Setup');

        try {
            if ($reset) {
                $io->section('Resetting database...');
                $this->runCommand('doctrine:database:drop', ['--force' => true, '--if-exists' => true], $output);
            }

            $io->section('Creating database...');
            $this->runCommand('doctrine:database:create', ['--if-not-exists' => true], $output);

            $io->section('Running migrations...');
            $this->runCommand('doctrine:migrations:migrate', ['--no-interaction' => true], $output);

            $io->section('Loading development fixtures...');
            $this->runCommand('doctrine:fixtures:load', ['--no-interaction' => true], $output);

            $io->section('Clearing cache...');
            $this->runCommand('cache:clear', [], $output);

            $io->success('Development environment setup completed!');
            $io->newLine();
            
            $io->definitionList(
                ['Admin User' => 'admin@specsrv.dev / admin123'],
                ['Test User' => 'user@specsrv.dev / user123'],
                ['Demo API Key (Admin)' => 'sk_admin_test_key_12345678901234567890123456789012'],
                ['Test API Key' => 'sk_test_user_key_12345678901234567890123456789012']
            );

            $io->note('You can now start the development server with: symfony server:start');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Setup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function runCommand(string $command, array $arguments, OutputInterface $output): void
    {
        $application = $this->getApplication();
        
        $commandInstance = $application->find($command);
        $input = new ArrayInput(array_merge(['command' => $command], $arguments));
        $input->setInteractive(false);
        
        $returnCode = $commandInstance->run($input, $output);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException("Command '{$command}' failed with code {$returnCode}");
        }
    }
}