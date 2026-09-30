<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260930103130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Profil : interrupteur Malt et CV PDF';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ADD show_malt BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE profile ADD cv_file VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile DROP show_malt');
        $this->addSql('ALTER TABLE profile DROP cv_file');
    }
}
