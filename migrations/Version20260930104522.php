<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260930104522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Profil : mode maintenance';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ADD maintenance BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE profile ADD maintenance_message TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile DROP maintenance');
        $this->addSql('ALTER TABLE profile DROP maintenance_message');
    }
}
