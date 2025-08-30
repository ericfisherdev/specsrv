<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250830120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update task status system to 6-status ENUM: backlog, todo, in_progress, review, completed, obsolete';
    }

    public function up(Schema $schema): void
    {
        // Update existing 'cancelled' status to 'obsolete'
        $this->addSql("UPDATE tasks SET status = 'obsolete' WHERE status = 'cancelled'");
    }

    public function down(Schema $schema): void
    {
        // Revert 'obsolete' status back to 'cancelled'
        $this->addSql("UPDATE tasks SET status = 'cancelled' WHERE status = 'obsolete'");
    }
}