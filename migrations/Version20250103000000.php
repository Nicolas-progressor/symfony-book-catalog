<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250103000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create author_subscription table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE author_subscription (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, author_id INTEGER NOT NULL, created_at DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE author_subscription');
    }
}
