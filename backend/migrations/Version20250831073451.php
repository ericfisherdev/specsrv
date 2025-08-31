<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831073451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing indexes for Task entity as defined in annotations';
    }

    public function up(Schema $schema): void
    {
        // Add missing indexes for Task entity (removed CONCURRENTLY for migration compatibility)
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_task_project ON tasks (project_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_task_status ON tasks (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_task_priority ON tasks (priority)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_task_project_status ON tasks (project_id, status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_task_created_at ON tasks (created_at)');
    }

    public function down(Schema $schema): void
    {
        // Remove indexes added in up()
        $this->addSql('DROP INDEX IF EXISTS idx_task_project');
        $this->addSql('DROP INDEX IF EXISTS idx_task_status');
        $this->addSql('DROP INDEX IF EXISTS idx_task_priority');
        $this->addSql('DROP INDEX IF EXISTS idx_task_project_status');
        $this->addSql('DROP INDEX IF EXISTS idx_task_created_at');
    }
}
