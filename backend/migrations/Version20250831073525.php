<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831073525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Implement case-insensitive email uniqueness constraint';
    }

    public function up(Schema $schema): void
    {
        // Handle existing duplicate emails by keeping the first occurrence (lowest ID)
        $this->addSql("
            DELETE FROM users u1 
            USING users u2 
            WHERE LOWER(u1.email) = LOWER(u2.email) 
            AND u1.id > u2.id
        ");
        
        // Drop existing unique constraint/index on email
        $this->addSql('DROP INDEX IF EXISTS uniq_users_email');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT IF EXISTS uniq_users_email');
        
        // Create case-insensitive unique index on LOWER(email)
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email_ci ON users (LOWER(email))');
    }

    public function down(Schema $schema): void
    {
        // Drop case-insensitive index
        $this->addSql('DROP INDEX IF EXISTS uniq_users_email_ci');
        
        // Recreate original case-sensitive unique constraint
        $this->addSql('ALTER TABLE users ADD CONSTRAINT uniq_users_email UNIQUE (email)');
    }
}
