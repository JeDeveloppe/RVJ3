<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819170230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du slug sur job_post';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post ADD slug VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post DROP slug');
    }
}
