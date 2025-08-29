<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829072001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_keys (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, key_hash VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_used_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , is_active BOOLEAN NOT NULL, CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9579321F57BFB971 ON api_keys (key_hash)');
        $this->addSql('CREATE INDEX IDX_9579321FA76ED395 ON api_keys (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__files AS SELECT id, task_id, filename, path, type, entity_type, entity_id, created_at FROM files');
        $this->addSql('DROP TABLE files');
        $this->addSql('CREATE TABLE files (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task_id INTEGER DEFAULT NULL, filename VARCHAR(255) NOT NULL, path VARCHAR(500) NOT NULL, type VARCHAR(50) NOT NULL, entity_type VARCHAR(20) NOT NULL, entity_id INTEGER NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_63540598DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO files (id, task_id, filename, path, type, entity_type, entity_id, created_at) SELECT id, task_id, filename, path, type, entity_type, entity_id, created_at FROM __temp__files');
        $this->addSql('DROP TABLE __temp__files');
        $this->addSql('CREATE INDEX IDX_63540598DB60186 ON files (task_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__git_links AS SELECT id, task_id, commit_hash, pr_reference, created_at FROM git_links');
        $this->addSql('DROP TABLE git_links');
        $this->addSql('CREATE TABLE git_links (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task_id INTEGER NOT NULL, commit_hash VARCHAR(255) DEFAULT NULL, pr_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_7D1A45478DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO git_links (id, task_id, commit_hash, pr_reference, created_at) SELECT id, task_id, commit_hash, pr_reference, created_at FROM __temp__git_links');
        $this->addSql('DROP TABLE __temp__git_links');
        $this->addSql('CREATE INDEX IDX_E3F05B4C8DB60186 ON git_links (task_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tasks AS SELECT id, project_id, title, description, status, created_at, updated_at FROM tasks');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tasks (id, project_id, title, description, status, created_at, updated_at) SELECT id, project_id, title, description, status, created_at, updated_at FROM __temp__tasks');
        $this->addSql('DROP TABLE __temp__tasks');
        $this->addSql('CREATE INDEX IDX_50586597166D1F9C ON tasks (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, roles, password, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO users (id, email, roles, password, created_at, updated_at) SELECT id, email, roles, password, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE api_keys');
        $this->addSql('CREATE TEMPORARY TABLE __temp__files AS SELECT id, task_id, filename, path, type, entity_type, entity_id, created_at FROM files');
        $this->addSql('DROP TABLE files');
        $this->addSql('CREATE TABLE files (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task_id INTEGER DEFAULT NULL, filename VARCHAR(255) NOT NULL, path VARCHAR(500) NOT NULL, type VARCHAR(50) DEFAULT \'upload\' NOT NULL, entity_type VARCHAR(20) NOT NULL, entity_id INTEGER NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_63540598DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO files (id, task_id, filename, path, type, entity_type, entity_id, created_at) SELECT id, task_id, filename, path, type, entity_type, entity_id, created_at FROM __temp__files');
        $this->addSql('DROP TABLE __temp__files');
        $this->addSql('CREATE INDEX IDX_63540598DB60186 ON files (task_id)');
        $this->addSql('CREATE INDEX IDX_63540596A58936A0 ON files (entity_type, entity_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__git_links AS SELECT id, task_id, commit_hash, pr_reference, created_at FROM git_links');
        $this->addSql('DROP TABLE git_links');
        $this->addSql('CREATE TABLE git_links (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task_id INTEGER NOT NULL, commit_hash VARCHAR(255) DEFAULT NULL, pr_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_E3F05B4C8DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO git_links (id, task_id, commit_hash, pr_reference, created_at) SELECT id, task_id, commit_hash, pr_reference, created_at FROM __temp__git_links');
        $this->addSql('DROP TABLE __temp__git_links');
        $this->addSql('CREATE INDEX IDX_7D1A45478DB60186 ON git_links (task_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tasks AS SELECT id, project_id, title, description, status, created_at, updated_at FROM tasks');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, status VARCHAR(20) DEFAULT \'todo\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tasks (id, project_id, title, description, status, created_at, updated_at) SELECT id, project_id, title, description, status, created_at, updated_at FROM __temp__tasks');
        $this->addSql('DROP TABLE __temp__tasks');
        $this->addSql('CREATE INDEX IDX_50586597166D1F9C ON tasks (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, roles, password, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB DEFAULT \'[]\' NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO users (id, email, roles, password, created_at, updated_at) SELECT id, email, roles, password, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
    }
}
