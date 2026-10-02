<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002233949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create hashed, expiring and revocable refresh tokens linked to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE refresh_token (id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_refresh_token_user ON refresh_token (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_refresh_token_hash ON refresh_token (token_hash)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES identity_user (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F2195A76ED395');
        $this->addSql('DROP TABLE refresh_token');
    }
}
