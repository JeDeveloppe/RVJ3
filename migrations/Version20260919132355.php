<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919132355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute payment.previous_token_payments (anciens identifiants de checkout HelloAsso d\'un meme document)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment ADD previous_token_payments JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP previous_token_payments');
    }
}
