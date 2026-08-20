<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819165150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de site_setting.last_job_post_cleanup_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_setting ADD last_job_post_cleanup_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_setting DROP last_job_post_cleanup_at');
    }
}
