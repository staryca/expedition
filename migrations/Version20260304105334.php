<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304105334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dance for markers';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_marker ADD dance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file_marker ADD CONSTRAINT FK_3AD8064165D64EDD FOREIGN KEY (dance_id) REFERENCES dance (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3AD8064165D64EDD ON file_marker (dance_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "file_marker" DROP CONSTRAINT FK_3AD8064165D64EDD');
        $this->addSql('DROP INDEX IDX_3AD8064165D64EDD');
        $this->addSql('ALTER TABLE "file_marker" DROP dance_id');
    }
}
