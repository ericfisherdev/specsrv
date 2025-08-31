<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * PostgreSQL Migration: Complete schema migration from SQLite to PostgreSQL
 * This migration recreates the entire specsrv database schema optimized for PostgreSQL.
 */
final class Version20250831054914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Complete PostgreSQL schema migration: users, projects, tasks, files, git_links, api_keys';
    }

    public function up(Schema $schema): void
    {
        // Enable UUID extension for future use
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "pg_trgm"');

        // Users table - Core user management
        $this->addSql('CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(180) NOT NULL UNIQUE,
            roles JSONB NOT NULL DEFAULT \'[]\',
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // Create unique index on email
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        // Create GIN index for roles JSON queries
        $this->addSql('CREATE INDEX IDX_1483A5E9ROLES_GIN ON users USING GIN (roles)');

        // Projects table - Project management
        $this->addSql('CREATE TABLE projects (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            github_repo VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT FK_5C93B3A4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )');

        // Indexes for projects
        $this->addSql('CREATE INDEX IDX_5C93B3A4A76ED395 ON projects (user_id)');
        $this->addSql('CREATE INDEX IDX_5C93B3A4_CREATED_AT ON projects (created_at DESC)');

        // Full-text search index for project titles and descriptions
        $this->addSql('CREATE INDEX IDX_5C93B3A4_FTS ON projects USING GIN (
            to_tsvector(\'english\', COALESCE(title, \'\') || \' \' || COALESCE(description, \'\'))
        )');

        // Tasks table - Task management with enhanced status system
        $this->addSql('CREATE TABLE tasks (
            id SERIAL PRIMARY KEY,
            project_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'todo\',
            priority VARCHAR(20) NOT NULL DEFAULT \'medium\',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        )');

        // Add CHECK constraints for task status and priority
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT CHK_TASK_STATUS 
            CHECK (status IN (\'backlog\', \'todo\', \'in_progress\', \'review\', \'completed\', \'obsolete\'))');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT CHK_TASK_PRIORITY 
            CHECK (priority IN (\'low\', \'medium\', \'high\', \'critical\'))');

        // Indexes for tasks
        $this->addSql('CREATE INDEX IDX_50586597166D1F9C ON tasks (project_id)');
        $this->addSql('CREATE INDEX IDX_50586597_STATUS ON tasks (status)');
        $this->addSql('CREATE INDEX IDX_50586597_PRIORITY ON tasks (priority)');
        $this->addSql('CREATE INDEX IDX_50586597_CREATED_AT ON tasks (created_at DESC)');
        $this->addSql('CREATE INDEX IDX_50586597_UPDATED_AT ON tasks (updated_at DESC)');

        // Composite index for common queries
        $this->addSql('CREATE INDEX IDX_50586597_PROJECT_STATUS ON tasks (project_id, status)');

        // Full-text search index for task titles and descriptions
        $this->addSql('CREATE INDEX IDX_50586597_FTS ON tasks USING GIN (
            to_tsvector(\'english\', COALESCE(title, \'\') || \' \' || COALESCE(description, \'\'))
        )');

        // Files table - File attachments and uploads
        $this->addSql('CREATE TABLE files (
            id SERIAL PRIMARY KEY,
            task_id INTEGER DEFAULT NULL,
            filename VARCHAR(255) NOT NULL,
            path VARCHAR(500) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT \'upload\',
            entity_type VARCHAR(20) NOT NULL,
            entity_id INTEGER NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT FK_63540598DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE SET NULL
        )');

        // Indexes for files
        $this->addSql('CREATE INDEX IDX_63540598DB60186 ON files (task_id)');
        $this->addSql('CREATE INDEX IDX_63540596A58936A0 ON files (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_6354059_CREATED_AT ON files (created_at DESC)');

        // Git Links table - Git integration
        $this->addSql('CREATE TABLE git_links (
            id SERIAL PRIMARY KEY,
            task_id INTEGER NOT NULL,
            commit_hash VARCHAR(255) DEFAULT NULL,
            pr_reference VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT FK_7D1A45478DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE
        )');

        // Indexes for git_links
        $this->addSql('CREATE INDEX IDX_7D1A45478DB60186 ON git_links (task_id)');
        $this->addSql('CREATE INDEX IDX_7D1A4547_COMMIT ON git_links (commit_hash)');
        $this->addSql('CREATE INDEX IDX_7D1A4547_PR ON git_links (pr_reference)');

        // API Keys table - API authentication
        $this->addSql('CREATE TABLE api_keys (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            key_hash VARCHAR(64) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP DEFAULT NULL,
            is_active BOOLEAN NOT NULL DEFAULT true,
            CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )');

        // Indexes for api_keys
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9579321F57BFB971 ON api_keys (key_hash)');
        $this->addSql('CREATE INDEX IDX_9579321FA76ED395 ON api_keys (user_id)');
        $this->addSql('CREATE INDEX IDX_9579321F_ACTIVE ON api_keys (is_active) WHERE is_active = true');

        // Create function for automatic updated_at timestamp
        $this->addSql('CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ language \'plpgsql\'');

        // Add triggers for automatic updated_at timestamps
        $this->addSql('CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()');
        $this->addSql('CREATE TRIGGER update_projects_updated_at BEFORE UPDATE ON projects
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()');
        $this->addSql('CREATE TRIGGER update_tasks_updated_at BEFORE UPDATE ON tasks
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()');
    }

    public function down(Schema $schema): void
    {
        // Drop triggers first
        $this->addSql('DROP TRIGGER IF EXISTS update_users_updated_at ON users');
        $this->addSql('DROP TRIGGER IF EXISTS update_projects_updated_at ON projects');
        $this->addSql('DROP TRIGGER IF EXISTS update_tasks_updated_at ON tasks');

        // Drop function
        $this->addSql('DROP FUNCTION IF EXISTS update_updated_at_column()');

        // Drop tables in reverse order due to foreign key constraints
        $this->addSql('DROP TABLE IF EXISTS api_keys');
        $this->addSql('DROP TABLE IF EXISTS git_links');
        $this->addSql('DROP TABLE IF EXISTS files');
        $this->addSql('DROP TABLE IF EXISTS tasks');
        $this->addSql('DROP TABLE IF EXISTS projects');
        $this->addSql('DROP TABLE IF EXISTS users');

        // Drop extensions (be careful in production - other apps might use them)
        // $this->addSql('DROP EXTENSION IF EXISTS "pg_trgm"');
        // $this->addSql('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
}
