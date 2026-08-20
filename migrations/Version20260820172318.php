<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260820172318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le perimetre (jeu/piece) sur search_boite_log';
    }

    public function up(Schema $schema): void
    {
        //?Defaut 'inconnu' pour les lignes existantes : on ne sait pas retroactivement
        //?si c'etait une recherche "jeu" ou "piece", donc on ne les fait pas passer pour
        //?des recherches de jeu (fausserait le nuage filtre sur ce perimetre).
        $this->addSql("ALTER TABLE search_boite_log ADD search_scope VARCHAR(10) NOT NULL DEFAULT 'inconnu'");
        $this->addSql("ALTER TABLE search_boite_log ALTER search_scope DROP DEFAULT");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE search_boite_log DROP search_scope');
    }
}
