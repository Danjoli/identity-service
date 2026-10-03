<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003000847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add persisted e-mail verification status and single-use verification tokens.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE email_verification_token (id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_email_verification_token_user ON email_verification_token (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_email_verification_token_hash ON email_verification_token (token_hash)');
        $this->addSql('ALTER TABLE email_verification_token ADD CONSTRAINT FK_C4995C67A76ED395 FOREIGN KEY (user_id) REFERENCES identity_user (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE identity_user ADD email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_verification_token DROP CONSTRAINT FK_C4995C67A76ED395');
        $this->addSql('DROP TABLE email_verification_token');
        $this->addSql('ALTER TABLE identity_user DROP email_verified_at');
    }
}
