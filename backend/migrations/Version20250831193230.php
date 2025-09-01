<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tag System Implementation - Core tables for flexible tagging.
 */
final class Version20250831193230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tag system tables for flexible tagging of tasks, projects, and files';
    }

    public function up(Schema $schema): void
    {
        // Ensure pgcrypto extension exists for gen_random_uuid()
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        // Create tags table
        $this->addSql('CREATE TABLE tags (
            id UUID NOT NULL DEFAULT gen_random_uuid(),
            workspace_id INTEGER DEFAULT NULL,
            name VARCHAR(50) NOT NULL,
            color VARCHAR(7) DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            parent_id UUID DEFAULT NULL,
            usage_count INTEGER DEFAULT 0 NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by INTEGER DEFAULT NULL,
            PRIMARY KEY(id)
        )');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC942982D40A1F FOREIGN KEY (workspace_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC9429727ACA70 FOREIGN KEY (parent_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC9429DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add unique constraint on workspace_id and name
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TAGS_WORKSPACE_NAME ON tags (workspace_id, name)');

        // Add indexes for performance
        $this->addSql('CREATE INDEX IDX_TAGS_WORKSPACE ON tags (workspace_id)');
        $this->addSql('CREATE INDEX IDX_TAGS_PARENT ON tags (parent_id)');
        $this->addSql('CREATE INDEX IDX_TAGS_USAGE_COUNT ON tags (usage_count)');
        $this->addSql('CREATE INDEX IDX_TAGS_CREATED_AT ON tags (created_at)');

        // Create tag hierarchies table for managing parent-child relationships
        $this->addSql('CREATE TABLE tag_hierarchies (
            id UUID NOT NULL DEFAULT gen_random_uuid(),
            parent_tag_id UUID NOT NULL,
            child_tag_id UUID NOT NULL,
            depth INTEGER NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // Add foreign key constraints for tag hierarchies
        $this->addSql('ALTER TABLE tag_hierarchies ADD CONSTRAINT FK_TAG_HIER_PARENT FOREIGN KEY (parent_tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_hierarchies ADD CONSTRAINT FK_TAG_HIER_CHILD FOREIGN KEY (child_tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add unique constraint to prevent duplicate hierarchies
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TAG_HIER_PARENT_CHILD ON tag_hierarchies (parent_tag_id, child_tag_id)');

        // Create task_tags junction table
        $this->addSql('CREATE TABLE task_tags (
            task_id INTEGER NOT NULL,
            tag_id UUID NOT NULL,
            applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            applied_by INTEGER DEFAULT NULL,
            PRIMARY KEY(task_id, tag_id)
        )');

        // Add foreign key constraints for task_tags
        $this->addSql('ALTER TABLE task_tags ADD CONSTRAINT FK_TASK_TAGS_TASK FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task_tags ADD CONSTRAINT FK_TASK_TAGS_TAG FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task_tags ADD CONSTRAINT FK_TASK_TAGS_USER FOREIGN KEY (applied_by) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes for task_tags
        $this->addSql('CREATE INDEX IDX_TASK_TAGS_TASK ON task_tags (task_id)');
        $this->addSql('CREATE INDEX IDX_TASK_TAGS_TAG ON task_tags (tag_id)');
        $this->addSql('CREATE INDEX IDX_TASK_TAGS_APPLIED_AT ON task_tags (applied_at)');

        // Create project_tags junction table
        $this->addSql('CREATE TABLE project_tags (
            project_id INTEGER NOT NULL,
            tag_id UUID NOT NULL,
            applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            applied_by INTEGER DEFAULT NULL,
            PRIMARY KEY(project_id, tag_id)
        )');

        // Add foreign key constraints for project_tags
        $this->addSql('ALTER TABLE project_tags ADD CONSTRAINT FK_PROJECT_TAGS_PROJECT FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_tags ADD CONSTRAINT FK_PROJECT_TAGS_TAG FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_tags ADD CONSTRAINT FK_PROJECT_TAGS_USER FOREIGN KEY (applied_by) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes for project_tags
        $this->addSql('CREATE INDEX IDX_PROJECT_TAGS_PROJECT ON project_tags (project_id)');
        $this->addSql('CREATE INDEX IDX_PROJECT_TAGS_TAG ON project_tags (tag_id)');

        // Create file_tags junction table
        $this->addSql('CREATE TABLE file_tags (
            file_id INTEGER NOT NULL,
            tag_id UUID NOT NULL,
            applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            applied_by INTEGER DEFAULT NULL,
            PRIMARY KEY(file_id, tag_id)
        )');

        // Add foreign key constraints for file_tags
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_FILE_TAGS_FILE FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_FILE_TAGS_TAG FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_FILE_TAGS_USER FOREIGN KEY (applied_by) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes for file_tags
        $this->addSql('CREATE INDEX IDX_FILE_TAGS_FILE ON file_tags (file_id)');
        $this->addSql('CREATE INDEX IDX_FILE_TAGS_TAG ON file_tags (tag_id)');

        // Create tag_suggestions table for tracking auto-suggestions
        $this->addSql('CREATE TABLE tag_suggestions (
            id UUID NOT NULL DEFAULT gen_random_uuid(),
            workspace_id INTEGER NOT NULL,
            entity_type VARCHAR(20) NOT NULL,
            entity_id INTEGER NOT NULL,
            suggested_tag_id UUID NOT NULL,
            confidence_score DECIMAL(3,2) DEFAULT NULL,
            reason VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // Add foreign key constraints for tag_suggestions
        $this->addSql('ALTER TABLE tag_suggestions ADD CONSTRAINT FK_TAG_SUGG_WORKSPACE FOREIGN KEY (workspace_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_suggestions ADD CONSTRAINT FK_TAG_SUGG_TAG FOREIGN KEY (suggested_tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes for tag_suggestions
        $this->addSql('CREATE INDEX IDX_TAG_SUGG_ENTITY ON tag_suggestions (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_TAG_SUGG_WORKSPACE ON tag_suggestions (workspace_id)');
        $this->addSql('CREATE INDEX IDX_TAG_SUGG_CONFIDENCE ON tag_suggestions (confidence_score DESC)');

        // Create tag_aliases table for synonyms
        $this->addSql('CREATE TABLE tag_aliases (
            id UUID NOT NULL DEFAULT gen_random_uuid(),
            tag_id UUID NOT NULL,
            alias VARCHAR(50) NOT NULL,
            workspace_id INTEGER NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // Add foreign key constraints for tag_aliases
        $this->addSql('ALTER TABLE tag_aliases ADD CONSTRAINT FK_TAG_ALIAS_TAG FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_aliases ADD CONSTRAINT FK_TAG_ALIAS_WORKSPACE FOREIGN KEY (workspace_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add unique constraint for alias per workspace
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TAG_ALIAS_WORKSPACE_ALIAS ON tag_aliases (workspace_id, alias)');

        // Add indexes for tag_aliases
        $this->addSql('CREATE INDEX IDX_TAG_ALIAS_WORKSPACE ON tag_aliases (workspace_id)');
        $this->addSql('CREATE INDEX IDX_TAG_ALIAS_TAG ON tag_aliases (tag_id)');

        // Add trigger to update usage_count
        $this->addSql('CREATE OR REPLACE FUNCTION update_tag_usage_count() RETURNS TRIGGER AS $$
        BEGIN
            IF TG_OP = \'INSERT\' THEN
                UPDATE tags SET usage_count = usage_count + 1 WHERE id = NEW.tag_id;
            ELSIF TG_OP = \'DELETE\' THEN
                UPDATE tags SET usage_count = GREATEST(usage_count - 1, 0) WHERE id = OLD.tag_id;
            ELSIF TG_OP = \'UPDATE\' THEN
                IF NEW.tag_id IS DISTINCT FROM OLD.tag_id THEN
                    UPDATE tags SET usage_count = GREATEST(usage_count - 1, 0) WHERE id = OLD.tag_id;
                    UPDATE tags SET usage_count = usage_count + 1 WHERE id = NEW.tag_id;
                END IF;
            END IF;
            RETURN NULL;
        END;
        $$ LANGUAGE plpgsql');

        // Create triggers for each junction table
        $this->addSql('CREATE TRIGGER update_tag_usage_task_tags
            AFTER INSERT OR DELETE OR UPDATE OF tag_id ON task_tags
            FOR EACH ROW EXECUTE FUNCTION update_tag_usage_count()');

        $this->addSql('CREATE TRIGGER update_tag_usage_project_tags
            AFTER INSERT OR DELETE OR UPDATE OF tag_id ON project_tags
            FOR EACH ROW EXECUTE FUNCTION update_tag_usage_count()');

        $this->addSql('CREATE TRIGGER update_tag_usage_file_tags
            AFTER INSERT OR DELETE OR UPDATE OF tag_id ON file_tags
            FOR EACH ROW EXECUTE FUNCTION update_tag_usage_count()');
    }

    public function down(Schema $schema): void
    {
        // Drop triggers
        $this->addSql('DROP TRIGGER IF EXISTS update_tag_usage_task_tags ON task_tags');
        $this->addSql('DROP TRIGGER IF EXISTS update_tag_usage_project_tags ON project_tags');
        $this->addSql('DROP TRIGGER IF EXISTS update_tag_usage_file_tags ON file_tags');
        $this->addSql('DROP FUNCTION IF EXISTS update_tag_usage_count()');

        // Drop tables in reverse order to respect foreign key constraints
        $this->addSql('DROP TABLE IF EXISTS tag_aliases');
        $this->addSql('DROP TABLE IF EXISTS tag_suggestions');
        $this->addSql('DROP TABLE IF EXISTS file_tags');
        $this->addSql('DROP TABLE IF EXISTS project_tags');
        $this->addSql('DROP TABLE IF EXISTS task_tags');
        $this->addSql('DROP TABLE IF EXISTS tag_hierarchies');
        $this->addSql('DROP TABLE IF EXISTS tags');
    }
}
