<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260930104249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Profil : disponibilité';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ADD available BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE profile ADD availability_label VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile DROP available');
        $this->addSql('ALTER TABLE profile DROP availability_label');
    }
}
