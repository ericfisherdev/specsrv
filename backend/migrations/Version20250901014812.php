<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Change tags parent_id foreign key from CASCADE to SET NULL.
 */
final class Version20250901014812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change tags parent_id foreign key from ON DELETE CASCADE to ON DELETE SET NULL';
    }

    public function up(Schema $schema): void
    {
        // Drop the existing foreign key constraint
        $this->addSql('ALTER TABLE tags DROP CONSTRAINT FK_6FBC9429727ACA70');

        // Add new foreign key constraint with SET NULL
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC9429727ACA70 FOREIGN KEY (parent_id) REFERENCES tags (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // Drop the SET NULL foreign key constraint
        $this->addSql('ALTER TABLE tags DROP CONSTRAINT FK_6FBC9429727ACA70');

        // Restore the original CASCADE constraint
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC9429727ACA70 FOREIGN KEY (parent_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
