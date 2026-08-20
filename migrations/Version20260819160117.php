<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819160117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation de la table job_post';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job_post (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_on_line TINYINT NOT NULL, start_published DATETIME NOT NULL, end_published DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_DD461ACCB03A8386 (created_by_id), INDEX IDX_DD461ACC896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_post ADD CONSTRAINT FK_DD461ACCB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE job_post ADD CONSTRAINT FK_DD461ACC896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_post DROP FOREIGN KEY FK_DD461ACCB03A8386');
        $this->addSql('ALTER TABLE job_post DROP FOREIGN KEY FK_DD461ACC896DBBDE');
        $this->addSql('DROP TABLE job_post');
    }
}
