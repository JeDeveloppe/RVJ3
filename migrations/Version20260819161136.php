<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819161136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'job_post.end_published devient obligatoire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post CHANGE end_published end_published DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post CHANGE end_published end_published DATETIME DEFAULT NULL');
    }
}
