<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user and notification tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "user" (id SERIAL PRIMARY KEY, email VARCHAR(180) NOT NULL UNIQUE, username VARCHAR(255) NOT NULL, roles TEXT NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE notification (id SERIAL PRIMARY KEY, user_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, message TEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL, is_read BOOLEAN NOT NULL DEFAULT FALSE)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE user');
    }
}
