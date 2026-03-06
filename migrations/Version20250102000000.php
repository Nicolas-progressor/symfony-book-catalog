<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250102000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create author and book tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE author (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, biography TEXT DEFAULT NULL, birth_year INTEGER DEFAULT NULL, death_year INTEGER DEFAULT NULL)');
        $this->addSql('CREATE TABLE book (id SERIAL PRIMARY KEY, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, year INTEGER DEFAULT NULL, description TEXT DEFAULT NULL, isbn VARCHAR(50) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, image_link VARCHAR(500) DEFAULT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE author');
    }
}
