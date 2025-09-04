<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ApiKeyRepository;
use App\Repository\UserRepository;
use App\Service\ApiKeyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-api',
    description: 'Debug API endpoints and show available keys and routes'
)]
class DebugApiCommand extends Command
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly UserRepository $userRepository,
        private readonly ApiKeyService $apiKeyService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('generate-key', null, InputOption::VALUE_OPTIONAL, 'Generate API key for user email')
            ->setHelp('This command helps debug API functionality and shows available keys.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $generateKey = $input->getOption('generate-key');

        $io->title('SpecSrv API Debug Information');

        if ($generateKey) {
            $user = $this->userRepository->findOneBy(['email' => $generateKey]);

            if (! $user) {
                $io->error("User with email '{$generateKey}' not found.");

                return Command::FAILURE;
            }

            $keyData = $this->apiKeyService->generateApiKey($user, 'Debug API Key');

            $io->success("API key generated for {$generateKey}");
            $io->definitionList(['API Key' => $keyData['api_key']]);

            return Command::SUCCESS;
        }

        // Show existing API keys
        $io->section('Available API Keys');
        $apiKeys = $this->apiKeyRepository->findAll();

        if (empty($apiKeys)) {
            $io->warning('No API keys found. Use fixtures or generate one with --generate-key option.');
        } else {
            $tableData = [];
            foreach ($apiKeys as $apiKey) {
                $tableData[] = [
                    $apiKey->getId(),
                    $apiKey->getName(),
                    $apiKey->getUser()->getEmail(),
                    $apiKey->isActive() ? '✓' : '✗',
                    $apiKey->getCreatedAt()->format('Y-m-d H:i:s'),
                    $apiKey->getLastUsedAt()?->format('Y-m-d H:i:s') ?? 'Never',
                ];
            }

            $io->table(['ID', 'Name', 'User', 'Active', 'Created', 'Last Used'], $tableData);
        }

        // Show test API keys from fixtures
        $io->section('Test API Keys from Fixtures');
        $io->definitionList(
            ['Admin Test Key' => 'sk_admin_test_key_12345678901234567890123456789012'],
            ['User Test Key' => 'sk_test_user_key_12345678901234567890123456789012'],
            ['Dev1 Key' => 'sk_dev1_key_12345678901234567890123456789012'],
            ['Dev2 Key' => 'sk_dev2_key_12345678901234567890123456789012'],
            ['Dev3 Key' => 'sk_dev3_key_12345678901234567890123456789012']
        );

        // Show API endpoints
        $io->section('Available API Endpoints');
        $io->definitionList(
            ['Authentication' => 'POST /api/login'],
            ['Projects' => 'GET/POST/PUT/DELETE /api/v1/projects'],
            ['Tasks' => 'GET/POST/PUT/DELETE /api/v1/tasks'],
            ['Files' => 'GET/POST/DELETE /api/files'],
            ['User Profile' => 'GET/PUT /api/profile'],
            ['API Keys Management' => 'GET/POST/DELETE /api/keys']
        );

        $io->section('Usage Examples');
        $io->text([
            'curl -H "X-API-Key: sk_admin_test_key_12345678901234567890123456789012" http://localhost:8000/api/v1/projects',
            'curl -H "X-API-Key: sk_test_user_key_12345678901234567890123456789012" http://localhost:8000/api/v1/tasks',
            'curl -X POST -H "Content-Type: application/json" -H "X-API-Key: YOUR_KEY" -d \'{"title":"Test Project","description":"A test"}\' http://localhost:8000/api/v1/projects',
        ]);

        $io->note([
            'Web Profiler available at: /_profiler',
            'Debug toolbar enabled in development mode',
            'Use --generate-key=user@email.com to create a new API key',
        ]);

        return Command::SUCCESS;
    }
}
