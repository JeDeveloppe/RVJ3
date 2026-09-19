<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919142721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute payment.hello_asso_payment_id (numero du paiement chez HelloAsso, unique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment ADD hello_asso_payment_id VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D28840DBCCE7DDB ON payment (hello_asso_payment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_6D28840DBCCE7DDB ON payment');
        $this->addSql('ALTER TABLE payment DROP hello_asso_payment_id');
    }
}
