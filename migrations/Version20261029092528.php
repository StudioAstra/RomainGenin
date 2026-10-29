<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029092528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fichiers vides enregistrés en chaîne vide : remplacés par NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE project SET image = NULL WHERE image = ''");
        $this->addSql("UPDATE profile SET photo = NULL WHERE photo = ''");
        $this->addSql("UPDATE profile SET cv_file = NULL WHERE cv_file = ''");
    }

    public function down(Schema $schema): void
    {
        // Rien à annuler : NULL est la valeur attendue
    }
}
