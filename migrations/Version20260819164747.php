<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819164747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du type de contrat et du lieu sur job_post';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post ADD contract_type VARCHAR(50) NOT NULL, ADD location VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post DROP contract_type, DROP location');
    }
}
