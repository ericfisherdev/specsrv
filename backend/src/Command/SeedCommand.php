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
    name: 'app:seed',
    description: 'Load development test data into the database'
)]
class SeedCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append data instead of purging first')
            ->setHelp('This command loads test data fixtures into the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $append = $input->getOption('append');

        $io->title('Loading SpecSrv Test Data');

        try {
            $arguments = ['--no-interaction' => true];
            
            if ($append) {
                $arguments['--append'] = true;
                $io->note('Appending data to existing database...');
            } else {
                $io->note('Purging existing data and loading fresh fixtures...');
            }

            $io->section('Loading fixtures...');
            $this->runCommand('doctrine:fixtures:load', $arguments, $output);

            $io->success('Test data loaded successfully!');
            $io->newLine();
            
            $io->definitionList(
                ['Users Created' => '6 (admin, test user, + 4 dev users)'],
                ['Projects Created' => '6 (demo, test, + 4 additional)'],
                ['Tasks Created' => '15+ (across all projects)'],
                ['API Keys Created' => '5 (for testing API endpoints)']
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Seeding failed: ' . $e->getMessage());
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