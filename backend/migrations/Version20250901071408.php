<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250901071408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add learning engine tables for knowledge patterns and agent interactions';
    }

    public function up(Schema $schema): void
    {
        // Create knowledge_patterns table
        $this->addSql('CREATE TABLE knowledge_patterns (
            id SERIAL NOT NULL, 
            pattern_name VARCHAR(100) NOT NULL, 
            pattern_type VARCHAR(50) NOT NULL, 
            context_signature JSON NOT NULL, 
            solution_template JSON NOT NULL, 
            description TEXT NOT NULL, 
            confidence_score DOUBLE PRECISION NOT NULL, 
            usage_count INT DEFAULT 0 NOT NULL, 
            last_successful_use TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            prerequisites JSON NOT NULL, 
            tags JSON NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY(id)
        )');

        // Add indexes for knowledge_patterns
        $this->addSql('CREATE INDEX idx_pattern_type ON knowledge_patterns (pattern_type)');
        $this->addSql('CREATE INDEX idx_confidence_score ON knowledge_patterns (confidence_score)');
        $this->addSql('CREATE INDEX idx_usage_count ON knowledge_patterns (usage_count)');
        $this->addSql('CREATE INDEX idx_last_success ON knowledge_patterns (last_successful_use)');

        // Create agent_interactions table
        $this->addSql('CREATE TABLE agent_interactions (
            id SERIAL NOT NULL, 
            task_id INT NOT NULL, 
            pattern_id INT DEFAULT NULL, 
            agent_type VARCHAR(50) NOT NULL, 
            input_context JSON NOT NULL, 
            execution_steps JSON NOT NULL, 
            output_result JSON NOT NULL, 
            success_score DOUBLE PRECISION NOT NULL, 
            pattern_hash VARCHAR(64) NOT NULL, 
            error_log JSON DEFAULT NULL, 
            execution_time_ms INT NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY(id)
        )');

        // Add indexes for agent_interactions
        $this->addSql('CREATE INDEX idx_agent_task ON agent_interactions (task_id)');
        $this->addSql('CREATE INDEX idx_agent_type ON agent_interactions (agent_type)');
        $this->addSql('CREATE INDEX idx_success_score ON agent_interactions (success_score)');
        $this->addSql('CREATE INDEX idx_created_at ON agent_interactions (created_at)');
        $this->addSql('CREATE INDEX idx_pattern_hash ON agent_interactions (pattern_hash)');
        $this->addSql('CREATE INDEX idx_agent_pattern ON agent_interactions (pattern_id)');

        // Create pattern_variations table
        $this->addSql('CREATE TABLE pattern_variations (
            id SERIAL NOT NULL, 
            base_pattern_id INT NOT NULL, 
            context_differences JSON NOT NULL, 
            adaptations JSON NOT NULL, 
            success_rate DOUBLE PRECISION NOT NULL, 
            usage_count INT DEFAULT 0 NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY(id)
        )');

        // Add indexes for pattern_variations
        $this->addSql('CREATE INDEX idx_variation_pattern ON pattern_variations (base_pattern_id)');
        $this->addSql('CREATE INDEX idx_variation_success_rate ON pattern_variations (success_rate)');
        $this->addSql('CREATE INDEX idx_variation_usage_count ON pattern_variations (usage_count)');
        $this->addSql('CREATE INDEX idx_variation_created_at ON pattern_variations (created_at)');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE agent_interactions ADD CONSTRAINT FK_agent_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE agent_interactions ADD CONSTRAINT FK_agent_pattern FOREIGN KEY (pattern_id) REFERENCES knowledge_patterns (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pattern_variations ADD CONSTRAINT FK_variation_pattern FOREIGN KEY (base_pattern_id) REFERENCES knowledge_patterns (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraints
        $this->addSql('ALTER TABLE agent_interactions DROP CONSTRAINT FK_agent_task');
        $this->addSql('ALTER TABLE agent_interactions DROP CONSTRAINT FK_agent_pattern');
        $this->addSql('ALTER TABLE pattern_variations DROP CONSTRAINT FK_variation_pattern');

        // Drop tables
        $this->addSql('DROP TABLE agent_interactions');
        $this->addSql('DROP TABLE pattern_variations');
        $this->addSql('DROP TABLE knowledge_patterns');
    }
}
