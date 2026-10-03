<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003005018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add immutable security audit events with actor, subject and request context.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_event (id UUID NOT NULL, event_type VARCHAR(80) NOT NULL, actor_id VARCHAR(36) DEFAULT NULL, actor_email VARCHAR(180) DEFAULT NULL, subject_id VARCHAR(36) DEFAULT NULL, subject_email VARCHAR(180) DEFAULT NULL, request_id VARCHAR(36) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, context JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_audit_event_created_at ON audit_event (created_at)');
        $this->addSql('CREATE INDEX idx_audit_event_type ON audit_event (event_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_event');
    }
}
