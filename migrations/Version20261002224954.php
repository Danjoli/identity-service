<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002224954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the identity user table with UUID, normalized e-mail, roles and account status.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE identity_user (id UUID NOT NULL, email VARCHAR(180) NOT NULL, display_name VARCHAR(120) NOT NULL, password_hash VARCHAR(255) NOT NULL, roles JSON NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_identity_user_email ON identity_user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE identity_user');
    }
}
